<?php
/**
 * KẾT NỐI CƠ SỞ DỮ LIỆU - NovaHM
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307'); // Cấu hình cổng 3307 theo XAMPP trên máy bạn
define('DB_NAME', 'novahm_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;color:#ef4444">
        <h2>Không kết nối được cơ sở dữ liệu</h2>
        <p>Hãy kiểm tra: (1) MySQL/XAMPP đã bật chưa, (2) đã import file <b>novahm_database.sql</b>
        vào phpMyAdmin chưa, (3) thông tin đăng nhập trong <b>includes/db.php</b> đã đúng chưa.</p>
        <p style="color:#94a3b8">Chi tiết lỗi: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
}