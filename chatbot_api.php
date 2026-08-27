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
 * Gom dữ liệu làm "ngữ cảnh" cho AI trả lời — PHÂN QUYỀN THEO ĐÚNG 5 VAI TRÒ:
 * - ADMIN: toàn bộ dữ liệu công ty.
 * - HR (Nhân sự tổng hợp): hồ sơ nhân viên + hợp đồng + chấm công (KHÔNG có dữ liệu lương).
 * - KETOAN: chấm công + lương + cấu hình lương (KHÔNG có chi tiết hồ sơ cá nhân/liên hệ).
 * - TRUONGPHONG: CHỈ nhân viên/đơn từ/KPI thuộc (các) phòng ban mình quản lý.
 * - NHANVIEN: CHỈ dữ liệu của chính họ.
 */
function gomNguCanh(PDO $pdo, array $currentUser): array {
    $ctx = [];
    $vaiTro = $currentUser['vai_tro'];

    if ($vaiTro === 'ADMIN') {
        // ----- ADMIN: toàn quyền -----
        $ctx['pham_vi'] = 'Toàn công ty (Admin)';
        $ctx['thong_ke_tong_quan'] = $pdo->query("SELECT * FROM vw_thong_ke_tong_quan")->fetch();

        $ctx['danh_sach_nhan_vien'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, nv.sdt, nv.email, nv.trang_thai_lam_viec,
                   pb.ten_phong_ban, cv.ten_chuc_vu, nv.ngay_vao_lam
            FROM nhan_vien nv
            LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
            LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
            ORDER BY nv.ma_nv LIMIT 200
        ")->fetchAll();

        $ctx['don_nghi_phep_gan_day'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, lp.ten_loai_phep, dnp.tu_ngay, dnp.den_ngay, dnp.so_ngay, dnp.trang_thai, dnp.ly_do
            FROM don_nghi_phep dnp JOIN nhan_vien nv ON nv.id = dnp.id_nv JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
            ORDER BY dnp.ngay_tao DESC LIMIT 30
        ")->fetchAll();

        $ctx['quy_phep_nam_hien_tai'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, qp.nam, qp.tong_phep, qp.da_su_dung, qp.con_lai
            FROM quy_phep_nam qp JOIN nhan_vien nv ON nv.id = qp.id_nv
            WHERE qp.nam = YEAR(CURDATE()) ORDER BY nv.ma_nv
        ")->fetchAll();

        $ctx['bang_luong_thang_hien_tai'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, bl.luong_co_ban, bl.ngay_cong_thuc_te, bl.tong_phu_cap, bl.tong_khau_tru, bl.thuc_linh, bl.trang_thai
            FROM bang_luong_thang bl JOIN nhan_vien nv ON nv.id = bl.id_nv
            WHERE bl.thang = MONTH(CURDATE()) AND bl.nam = YEAR(CURDATE()) ORDER BY nv.ma_nv
        ")->fetchAll();

        $ctx['hop_dong_sap_het_han'] = $pdo->query("SELECT * FROM vw_hop_dong_sap_het_han")->fetchAll();
        $ctx['cau_hinh_luong_hien_hanh'] = $pdo->query("SELECT * FROM cau_hinh_luong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();
        $ctx['cau_hinh_he_thong_hien_hanh'] = $pdo->query("SELECT * FROM cau_hinh_he_thong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();

    } elseif ($vaiTro === 'HR') {
        // ----- Nhân sự tổng hợp: hồ sơ nhân viên + hợp đồng + chấm công (KHÔNG có lương) -----
        $ctx['pham_vi'] = 'Hồ sơ nhân viên, hợp đồng, chấm công toàn công ty (KHÔNG bao gồm dữ liệu lương)';
        $ctx['danh_sach_nhan_vien'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, nv.sdt, nv.email, nv.trang_thai_lam_viec,
                   pb.ten_phong_ban, cv.ten_chuc_vu, nv.ngay_vao_lam
            FROM nhan_vien nv
            LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
            LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
            ORDER BY nv.ma_nv LIMIT 200
        ")->fetchAll();
        $ctx['hop_dong_sap_het_han'] = $pdo->query("SELECT * FROM vw_hop_dong_sap_het_han")->fetchAll();
        $ctx['cham_cong_hom_nay'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, bc.gio_vao, bc.gio_ra, bc.trang_thai
            FROM bang_cham_cong_ngay bc JOIN nhan_vien nv ON nv.id = bc.id_nv
            WHERE bc.ngay = CURDATE() ORDER BY nv.ma_nv
        ")->fetchAll();

    } elseif ($vaiTro === 'KETOAN') {
        // ----- Kế toán: chấm công + lương + cấu hình lương (KHÔNG có thông tin liên hệ/hồ sơ cá nhân) -----
        $ctx['pham_vi'] = 'Chấm công, bảng lương, cấu hình lương toàn công ty (KHÔNG bao gồm thông tin liên hệ cá nhân)';
        $ctx['cham_cong_hom_nay'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, bc.gio_vao, bc.gio_ra, bc.trang_thai
            FROM bang_cham_cong_ngay bc JOIN nhan_vien nv ON nv.id = bc.id_nv
            WHERE bc.ngay = CURDATE() ORDER BY nv.ma_nv
        ")->fetchAll();
        $ctx['bang_luong_thang_hien_tai'] = $pdo->query("
            SELECT nv.ma_nv, nv.ho_ten, bl.luong_co_ban, bl.ngay_cong_thuc_te, bl.tong_phu_cap, bl.tong_khau_tru, bl.thuc_linh, bl.trang_thai
            FROM bang_luong_thang bl JOIN nhan_vien nv ON nv.id = bl.id_nv
            WHERE bl.thang = MONTH(CURDATE()) AND bl.nam = YEAR(CURDATE()) ORDER BY nv.ma_nv
        ")->fetchAll();
        $ctx['cau_hinh_luong_hien_hanh'] = $pdo->query("SELECT * FROM cau_hinh_luong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();

    } elseif ($vaiTro === 'TRUONGPHONG') {
        // ----- Trưởng phòng: CHỈ phòng ban mình quản lý -----
        $phongBanQL = $currentUser['phong_ban_quan_ly'];
        $ctx['pham_vi'] = 'CHỈ (các) phòng ban mà người này đang làm Trưởng phòng — KHÔNG được tiết lộ dữ liệu phòng ban khác';

        if (empty($phongBanQL)) {
            $ctx['ghi_chu'] = 'Tài khoản này chưa được gán quản lý phòng ban nào.';
            return $ctx;
        }

        $ph = implode(',', array_fill(0, count($phongBanQL), '?'));

        $s = $pdo->prepare("SELECT ma_nv, ho_ten, sdt, email, trang_thai_lam_viec FROM nhan_vien WHERE id_phong_ban IN ($ph) ORDER BY ma_nv");
        $s->execute($phongBanQL);
        $ctx['nhan_vien_phong_ban_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("
            SELECT nv.ma_nv, nv.ho_ten, lp.ten_loai_phep, dnp.tu_ngay, dnp.den_ngay, dnp.so_ngay, dnp.trang_thai
            FROM don_nghi_phep dnp JOIN nhan_vien nv ON nv.id = dnp.id_nv JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
            WHERE nv.id_phong_ban IN ($ph) ORDER BY dnp.ngay_tao DESC LIMIT 30
        ");
        $s->execute($phongBanQL);
        $ctx['don_nghi_phep_phong_ban_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("
            SELECT nv.ma_nv, nv.ho_ten, qp.tong_phep, qp.da_su_dung, qp.con_lai
            FROM quy_phep_nam qp JOIN nhan_vien nv ON nv.id = qp.id_nv
            WHERE qp.nam = YEAR(CURDATE()) AND nv.id_phong_ban IN ($ph) ORDER BY nv.ma_nv
        ");
        $s->execute($phongBanQL);
        $ctx['quy_phep_phong_ban_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("
            SELECT nv.ma_nv, nv.ho_ten, kpi.ky_danh_gia, kpi.diem_kpi, kpi.nhan_xet
            FROM danh_gia_kpi kpi JOIN nhan_vien nv ON nv.id = kpi.id_nv
            WHERE nv.id_phong_ban IN ($ph) ORDER BY kpi.ngay_tao DESC LIMIT 30
        ");
        $s->execute($phongBanQL);
        $ctx['kpi_phong_ban_cua_toi'] = $s->fetchAll();

    } else {
        // ----- Nhân viên thường: CHỈ dữ liệu của chính họ -----
        $ctx['pham_vi'] = 'Chỉ dữ liệu cá nhân của người đang hỏi (không được tiết lộ dữ liệu nhân viên khác)';
        $idNv = $currentUser['id_nv'];

        if (!$idNv) {
            $ctx['ghi_chu'] = 'Tài khoản này chưa gắn với hồ sơ nhân viên nào.';
            return $ctx;
        }

        $s = $pdo->prepare("
            SELECT nv.ma_nv, nv.ho_ten, nv.sdt, nv.email, nv.trang_thai_lam_viec,
                   pb.ten_phong_ban, cv.ten_chuc_vu, nv.ngay_vao_lam
            FROM nhan_vien nv
            LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
            LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
            WHERE nv.id = :nv
        ");
        $s->execute([':nv' => $idNv]);
        $ctx['ho_so_cua_toi'] = $s->fetch();

        $s = $pdo->prepare("
            SELECT lp.ten_loai_phep, dnp.tu_ngay, dnp.den_ngay, dnp.so_ngay, dnp.trang_thai, dnp.ly_do
            FROM don_nghi_phep dnp
            JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
            WHERE dnp.id_nv = :nv
            ORDER BY dnp.ngay_tao DESC LIMIT 20
        ");
        $s->execute([':nv' => $idNv]);
        $ctx['don_nghi_phep_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("SELECT nam, tong_phep, da_su_dung, con_lai FROM quy_phep_nam WHERE id_nv = :nv ORDER BY nam DESC");
        $s->execute([':nv' => $idNv]);
        $ctx['quy_phep_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("
            SELECT thang, nam, luong_co_ban, ngay_cong_thuc_te, tong_phu_cap, tong_khau_tru, thuc_linh, trang_thai
            FROM bang_luong_thang WHERE id_nv = :nv ORDER BY nam DESC, thang DESC LIMIT 12
        ");
        $s->execute([':nv' => $idNv]);
        $ctx['luong_cua_toi'] = $s->fetchAll();

        $s = $pdo->prepare("
            SELECT ngay, gio_vao, gio_ra, so_gio_lam, so_phut_tre, trang_thai
            FROM bang_cham_cong_ngay WHERE id_nv = :nv ORDER BY ngay DESC LIMIT 31
        ");
        $s->execute([':nv' => $idNv]);
        $ctx['cham_cong_cua_toi_30_ngay_gan_day'] = $s->fetchAll();
    }

    return $ctx;
}

try {
    $nguCanh = gomNguCanh($pdo, $currentUser);

    $systemPrompt = "Bạn là trợ lý ảo của hệ thống quản lý nhân sự NovaHM.\n"
        . "Người đang hỏi là: {$currentUser['ten']} (vai trò: {$currentUser['vai_tro']}).\n"
        . "Dưới đây là dữ liệu THẬT hiện tại, dạng JSON (phạm vi dữ liệu đã được giới hạn đúng theo quyền của người hỏi):\n\n"
        . json_encode($nguCanh, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . "\n\nHãy trả lời câu hỏi của người dùng CHỈ dựa vào dữ liệu trên, bằng tiếng Việt, ngắn gọn, "
        . "rõ ràng, có thể dùng bảng hoặc gạch đầu dòng nếu cần liệt kê nhiều mục. "
        . "TUYỆT ĐỐI không được suy đoán hay tiết lộ thông tin về nhân viên khác nếu dữ liệu không có trong ngữ cảnh trên "
        . "(vì người hỏi có thể không có quyền xem). "
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
