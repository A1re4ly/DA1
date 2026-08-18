<?php
/**
 * BACKEND CHATBOT AI
 * Nhận câu hỏi (POST 'cau_hoi'), gom dữ liệu thật từ CSDL làm ngữ cảnh,
 * gửi cho Google Gemini API, trả lời JSON {reply: "..."} cho JS phía trước xử lý.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/auth.php';       // bắt buộc đăng nhập mới hỏi được
require_once __DIR__ . '/includes/ai_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không hợp lệ']);
    exit;
}

$cauHoi = trim($_POST['cau_hoi'] ?? '');
if ($cauHoi === '') {
    echo json_encode(['error' => 'Vui lòng nhập câu hỏi']);
    exit;
}

if (GEMINI_API_KEY === 'AIzaSy_DAN_KEY_CUA_BAN_VAO_DAY' || GEMINI_API_KEY === '') {
    echo json_encode(['error' => 'Chưa cấu hình API key. Mở includes/ai_config.php và dán Gemini API key của bạn vào.']);
    exit;
}

/**
 * Gom dữ liệu thật hiện tại của công ty làm "ngữ cảnh" cho AI trả lời.
 * Giới hạn số dòng để tránh prompt quá dài / tốn phí.
 */
function gomNguCanh(PDO $pdo): array {
    $ctx = [];

    $ctx['thong_ke_tong_quan'] = $pdo->query("SELECT * FROM vw_thong_ke_tong_quan")->fetch();

    $ctx['danh_sach_nhan_vien'] = $pdo->query("
        SELECT nv.ma_nv, nv.ho_ten, nv.sdt, nv.email, nv.trang_thai_lam_viec,
               pb.ten_phong_ban, cv.ten_chuc_vu, nv.ngay_vao_lam
        FROM nhan_vien nv
        LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
        LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
        ORDER BY nv.ma_nv
        LIMIT 200
    ")->fetchAll();

    $ctx['don_nghi_phep_gan_day'] = $pdo->query("
        SELECT nv.ma_nv, nv.ho_ten, lp.ten_loai_phep, dnp.tu_ngay, dnp.den_ngay, dnp.so_ngay, dnp.trang_thai, dnp.ly_do
        FROM don_nghi_phep dnp
        JOIN nhan_vien nv ON nv.id = dnp.id_nv
        JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
        ORDER BY dnp.ngay_tao DESC
        LIMIT 30
    ")->fetchAll();

    $ctx['quy_phep_nam_hien_tai'] = $pdo->query("
        SELECT nv.ma_nv, nv.ho_ten, qp.nam, qp.tong_phep, qp.da_su_dung, qp.con_lai
        FROM quy_phep_nam qp
        JOIN nhan_vien nv ON nv.id = qp.id_nv
        WHERE qp.nam = YEAR(CURDATE())
        ORDER BY nv.ma_nv
    ")->fetchAll();

    $ctx['bang_luong_thang_hien_tai'] = $pdo->query("
        SELECT nv.ma_nv, nv.ho_ten, bl.luong_co_ban, bl.ngay_cong_thuc_te, bl.tong_phu_cap, bl.tong_khau_tru, bl.thuc_linh, bl.trang_thai
        FROM bang_luong_thang bl
        JOIN nhan_vien nv ON nv.id = bl.id_nv
        WHERE bl.thang = MONTH(CURDATE()) AND bl.nam = YEAR(CURDATE())
        ORDER BY nv.ma_nv
    ")->fetchAll();

    $ctx['hop_dong_sap_het_han'] = $pdo->query("SELECT * FROM vw_hop_dong_sap_het_han")->fetchAll();

    $ctx['cau_hinh_luong_hien_hanh'] = $pdo->query("
        SELECT * FROM cau_hinh_luong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1
    ")->fetch();

    return $ctx;
}

try {
    $nguCanh = gomNguCanh($pdo);

    $systemPrompt = "Bạn là trợ lý ảo của hệ thống quản lý nhân sự NovaHM.\n"
        . "Người đang hỏi là: {$currentUser['ten']} (vai trò: {$currentUser['vai_tro']}).\n"
        . "Dưới đây là dữ liệu THẬT hiện tại của công ty, dạng JSON:\n\n"
        . json_encode($nguCanh, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . "\n\nHãy trả lời câu hỏi của người dùng CHỈ dựa vào dữ liệu trên, bằng tiếng Việt, ngắn gọn, "
        . "rõ ràng, có thể dùng bảng hoặc gạch đầu dòng nếu cần liệt kê nhiều mục. "
        . "Nếu dữ liệu không đủ để trả lời, hãy nói rõ là hệ thống chưa có dữ liệu đó, TUYỆT ĐỐI không bịa số liệu. "
        . "Số tiền hiển thị theo định dạng VNĐ có dấu chấm ngăn cách hàng nghìn.";

    $payload = [
        'system_instruction' => [
            'parts' => [['text' => $systemPrompt]],
        ],
        'contents' => [
            [
                'role'  => 'user',
                'parts' => [['text' => $cauHoi]],
            ],
        ],
        'generationConfig' => [
            'maxOutputTokens' => 1024,
            'temperature'     => 0.4,
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT    => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['error' => 'Lỗi kết nối tới Gemini API: ' . $curlErr]);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? 'Không rõ nguyên nhân';
        echo json_encode(['error' => "Gemini API báo lỗi ($httpCode): $msg"]);
        exit;
    }

    $traLoi = '';
    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    foreach ($parts as $p) {
        if (isset($p['text'])) $traLoi .= $p['text'];
    }

    if ($traLoi === '') {
        $traLoi = 'Xin lỗi, mình chưa tạo được câu trả lời. Bạn thử hỏi lại nhé.';
    }

    ghiNhatKy($pdo, 'AI_QUERY', 'chatbot', null, mb_substr($cauHoi, 0, 200));

    echo json_encode(['reply' => $traLoi]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
