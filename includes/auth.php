<?php
/**
 * BẢO VỆ PHIÊN ĐĂNG NHẬP + PHÂN QUYỀN CHI TIẾT THEO VAI TRÒ
 * require ở đầu mọi trang cần đăng nhập mới xem được.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Các vai trò dùng chung bộ giao diện "quản trị" (khác giao diện tự phục vụ của Nhân viên).
// Phạm vi/thao tác cụ thể bên trong vẫn bị giới hạn tiếp theo bảng quyền hạn (coQuyen) hoặc theo vai trò (laVaiTro).
const VAI_TRO_QUAN_TRI = ['ADMIN', 'HR', 'KETOAN', 'TRUONGPHONG'];

// Thông tin người dùng hiện tại, dùng lại ở header (avatar, tên, vai trò)
$currentUser = [
    'id'       => $_SESSION['user_id'],
    'ten'      => $_SESSION['user_name']  ?? 'Người dùng',
    'vai_tro'  => $_SESSION['user_role']  ?? 'NHANVIEN',
    'id_nv'    => $_SESSION['id_nv']      ?? null,
    'la_admin' => in_array($_SESSION['user_role'] ?? 'NHANVIEN', VAI_TRO_QUAN_TRI, true),
    'quyen'    => [],                 // ma_quyen chi tiết, nạp bên dưới
    'phong_ban_quan_ly' => [],        // id các phòng ban mà Trưởng phòng đang quản lý
];

// Nạp danh sách quyền hạn chi tiết của vai trò hiện tại (bảng vai_tro_quyen / quyen_han).
// ADMIN luôn coi như có mọi quyền (coQuyen() bypass thẳng), không cần nạp.
if ($currentUser['vai_tro'] !== 'ADMIN') {
    try {
        $s = $pdo->prepare("
            SELECT qh.ma_quyen
            FROM vai_tro_quyen vq
            JOIN quyen_han qh ON qh.id = vq.id_quyen
            JOIN vai_tro vt   ON vt.id = vq.id_vai_tro
            WHERE vt.ma_vai_tro = :vt
        ");
        $s->execute([':vt' => $currentUser['vai_tro']]);
        $currentUser['quyen'] = $s->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Bảng vai_tro_quyen có thể chưa được gán quyền (chưa chạy patch_phanquyen.sql) -> coi như chưa có quyền gì
        $currentUser['quyen'] = [];
    }
}

// Nếu là Trưởng phòng -> nạp danh sách phòng ban mà người này đang phụ trách (cột truong_phong_id ở bảng phong_ban)
if ($currentUser['vai_tro'] === 'TRUONGPHONG' && !empty($currentUser['id_nv'])) {
    try {
        $s = $pdo->prepare("SELECT id FROM phong_ban WHERE truong_phong_id = :id");
        $s->execute([':id' => $currentUser['id_nv']]);
        $currentUser['phong_ban_quan_ly'] = $s->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $currentUser['phong_ban_quan_ly'] = [];
    }
}

/** true nếu vai trò hiện tại nằm trong danh sách vai trò truyền vào */
function laVaiTro(...$dsVaiTro) {
    global $currentUser;
    return in_array($currentUser['vai_tro'] ?? '', $dsVaiTro, true);
}

/** true nếu người dùng có quyền hạn cụ thể (ADMIN luôn true) */
function coQuyen($maQuyen) {
    global $currentUser;
    if (($currentUser['vai_tro'] ?? '') === 'ADMIN') return true;
    return in_array($maQuyen, $currentUser['quyen'] ?? [], true);
}

/** Chặn truy cập nếu vai trò hiện tại KHÔNG nằm trong danh sách cho phép -> đá về trang phù hợp */
function yeuCauVaiTro(...$dsVaiTroChoPhep) {
    global $currentUser;
    if (!laVaiTro(...$dsVaiTroChoPhep)) {
        $trangVe = $currentUser['la_admin'] ? 'index.php' : 'trangcanhan.php';
        header("Location: $trangVe?err=noaccess");
        exit;
    }
}

/** Chặn truy cập nếu không có quyền hạn cụ thể -> đá về trang phù hợp */
function yeuCauQuyen($maQuyen) {
    global $currentUser;
    if (!coQuyen($maQuyen)) {
        $trangVe = $currentUser['la_admin'] ? 'index.php' : 'trangcanhan.php';
        header("Location: $trangVe?err=noaccess");
        exit;
    }
}

/** Chặn truy cập nếu không phải nhóm vai trò quản trị (dùng cho các trang admin dùng chung, ít nhạy cảm) */
function yeuCauQuanTri() {
    global $currentUser;
    if (!$currentUser['la_admin']) {
        header('Location: trangcanhan.php?err=noaccess');
        exit;
    }
}

/** Chặn truy cập nếu tài khoản không gắn với hồ sơ nhân viên nào (vd tài khoản Admin hệ thống) */
function yeuCauCoHoSoNhanVien() {
    global $currentUser;
    if (empty($currentUser['id_nv'])) {
        header('Location: index.php?err=noprofile');
        exit;
    }
}

/** Trợ giúp: định dạng tiền VNĐ */
function formatTien($so) {
    return number_format((float)$so, 0, ',', '.') . ' đ';
}

/** Trả về thông tin chốt bảng công của 1 tháng (null nếu chưa chốt) */
function thongTinChotThang(PDO $pdo, $thang, $nam) {
    try {
        $s = $pdo->prepare("
            SELECT cbc.*, nd.email AS email_nguoi_chot,
                   COALESCE(nv.ho_ten, 'Quản trị viên') AS ten_nguoi_chot
            FROM chot_bang_cong cbc
            LEFT JOIN nguoi_dung nd ON nd.id = cbc.id_nguoi_chot
            LEFT JOIN nhan_vien nv ON nv.id = nd.id_nv
            WHERE cbc.thang = :t AND cbc.nam = :n
        ");
        $s->execute([':t' => $thang, ':n' => $nam]);
        return $s->fetch() ?: null;
    } catch (Exception $e) {
        // Chưa chạy patch_chotbangcong.sql -> coi như chưa có tháng nào được chốt
        return null;
    }
}

/** true nếu tháng đã được chốt */
function thangDaChot(PDO $pdo, $thang, $nam) {
    return thongTinChotThang($pdo, $thang, $nam) !== null;
}

/** Trợ giúp: ghi nhật ký hoạt động (audit log) */
function ghiNhatKy(PDO $pdo, $hanhDong, $doiTuong, $idDoiTuong = null, $chiTiet = '') {
    global $currentUser;
    $stmt = $pdo->prepare("INSERT INTO nhat_ky_hoat_dong (id_nguoi_dung, hanh_dong, doi_tuong, id_doi_tuong, chi_tiet, dia_chi_ip)
                            VALUES (:uid, :hd, :dt, :id, :ct, :ip)");
    $stmt->execute([
        ':uid' => $currentUser['id'] ?? null,
        ':hd'  => $hanhDong,
        ':dt'  => $doiTuong,
        ':id'  => $idDoiTuong,
        ':ct'  => $chiTiet,
        ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}
