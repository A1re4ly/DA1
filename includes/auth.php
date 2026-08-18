<?php
/**
 * BẢO VỆ PHIÊN ĐĂNG NHẬP
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

// Thông tin người dùng hiện tại, dùng lại ở header (avatar, tên, vai trò)
$currentUser = [
    'id'       => $_SESSION['user_id'],
    'ten'      => $_SESSION['user_name']  ?? 'Người dùng',
    'vai_tro'  => $_SESSION['user_role']  ?? 'NHANVIEN',
    'id_nv'    => $_SESSION['id_nv']      ?? null,
];

/** Trợ giúp: định dạng tiền VNĐ */
function formatTien($so) {
    return number_format((float)$so, 0, ',', '.') . ' đ';
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
