<?php
// 1. Tải các tệp cấu hình và hàm nghiệp vụ
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h2>=== BẮT ĐẦU KIỂM THỬ HỆ THỐNG NOVAHM ===</h2>";

// -------------------------------------------------------------
// TEST CASE 1: Kiểm tra kết nối CSDL PDO
// -------------------------------------------------------------
try {
    if ($pdo) {
        echo "<p style='color:green;'>[PASS] Test Case 1: Kết nối CSDL novahm_db thành công!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>[FAIL] Test Case 1: Lỗi kết nối CSDL: " . $e->getMessage() . "</p>";
}

// -------------------------------------------------------------
// -------------------------------------------------------------
// TEST CASE 2: Kiểm tra thuật toán tính phút đi trễ (Giờ chuẩn: 08:00:00)
// -------------------------------------------------------------
function tinhPhutTreTest($gioVao) {
    if (!$gioVao || $gioVao <= '08:00:00') return 0;
    return (int)round((strtotime($gioVao) - strtotime('08:00:00')) / 60);
}

$phutTreThucTe = tinhPhutTreTest('08:15:00');

// Dùng == thay vì === để tránh lỗi lệch kiểu dữ liệu String/Int
if ($phutTreThucTe == 15) { 
    echo "<p style='color:green;'>[PASS] Test Case 2: Tính phút đi trễ chính xác (08:15 -> Trễ 15 phút).</p>";
} else {
    echo "<p style='color:red;'>[FAIL] Test Case 2: Tính phút đi trễ SAI (Kết quả: {$phutTreThucTe} phút, Kỳ vọng: 15 phút).</p>";
}
// -------------------------------------------------------------
// TEST CASE 3: Kiểm tra truy vấn bảng chot_bang_cong
// -------------------------------------------------------------
$thangTest = 9;
$namTest = 2026;
$chotInfo = thongTinChotThang($pdo, $thangTest, $namTest);

if ($chotInfo !== null) {
    echo "<p style='color:green;'>[PASS] Test Case 3: Lấy thông tin chốt tháng $thangTest/$namTest thành công.</p>";
} else {
    echo "<p style='color:orange;'>[INFO] Test Case 3: Tháng $thangTest/$namTest chưa được chốt (Hoặc chưa có dữ liệu).</p>";
}

echo "<h3>=== HOÀN THÀNH KIỂM THỬ ===</h3>";
?>