<?php
/**
 * CHẠY FILE NÀY 1 LẦN DUY NHẤT sau khi import novahm_database.sql,
 * để tạo mật khẩu thật (bcrypt) cho tài khoản admin mẫu, vì file .sql
 * chỉ chèn sẵn một chuỗi giả '$2y$10$REPLACE_WITH_REAL_BCRYPT_HASH'.
 *
 * Sau khi chạy xong (thấy dòng "Đã cập nhật"), hãy XOÁ file này đi
 * để không ai khác truy cập được.
 *
 * Đăng nhập mặc định sau khi chạy:
 *   Email:     admin@novacompany.vn
 *   Mật khẩu:  123456
 */
require_once __DIR__ . '/includes/db.php';

$emailAdmin  = 'admin@novacompany.vn';
$mkMoi       = '123456';
$hash        = password_hash($mkMoi, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau_hash = :hash WHERE email = :email");
$stmt->execute([':hash' => $hash, ':email' => $emailAdmin]);

if ($stmt->rowCount() > 0) {
    echo "<p style='font-family:sans-serif;color:#059669'>✅ Đã cập nhật mật khẩu cho $emailAdmin thành '$mkMoi'. 
    Bây giờ hãy XOÁ file cap_nhat_mk_admin.php này rồi đăng nhập tại login.php.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#ef4444'>⚠️ Không tìm thấy tài khoản $emailAdmin trong bảng nguoi_dung.
    Kiểm tra lại bạn đã import đúng file novahm_database.sql chưa.</p>";
}
