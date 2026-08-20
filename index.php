<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Bảng điều khiển - NovaHM';

// 4 thẻ thống kê -> lấy từ view vw_thong_ke_tong_quan
$tk = $pdo->query("SELECT * FROM vw_thong_ke_tong_quan")->fetch();

// Danh sách đơn nghỉ phép mới nhất
$donPhepMoi = $pdo->query("
    SELECT dnp.id, nv.ma_nv, nv.ho_ten, pb.ten_phong_ban, lp.ten_loai_phep,
           dnp.ly_do, dnp.so_ngay, dnp.trang_thai
    FROM don_nghi_phep dnp
    JOIN nhan_vien nv ON nv.id = dnp.id_nv
    LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
    JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
    ORDER BY dnp.ngay_tao DESC
    LIMIT 5
")->fetchAll();

function badgeTrangThai($tt) {
    $map = [
        'Chờ duyệt' => 'badge-warning',
        'Đã duyệt'  => 'badge-success',
    ];
    $cls = $map[$tt] ?? '';
    if ($cls) return "<span class=\"badge $cls\">".htmlspecialchars($tt)."</span>";
    return "<span class=\"badge\" style=\"background-color:#fef2f2;color:#ef4444;\">".htmlspecialchars($tt)."</span>";
}

require __DIR__ . '/includes/header.php';
?>
            <!-- Thẻ thống kê -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= (int)$tk['tong_nhan_vien'] ?></h3>
                        <p>Tổng nhân viên</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3><?= (int)$tk['cham_cong_hom_nay'] ?></h3>
                        <p>Chấm công hôm nay</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3><?= formatTien($tk['quy_luong_thang_nay']) ?></h3>
                        <p>Quỹ lương tháng này</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="stat-info">
                        <h3><?= (int)$tk['don_phep_cho_duyet'] ?></h3>
                        <p>Đơn phép chờ duyệt</p>
                    </div>
                </div>
            </section>

            <!-- Bảng dữ liệu -->
            <section class="data-section">
                <div class="section-header">
                    <h3>Yêu cầu nghỉ phép mới nhất</h3>
                    <a href="duyetdonphep.php" class="btn-primary" style="text-decoration:none;">+ Xem tất cả đơn</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Phòng ban</th>
                            <th>Lý do nghỉ</th>
                            <th>Số ngày</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($donPhepMoi)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Chưa có đơn nghỉ phép nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($donPhepMoi as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($d['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($d['ten_phong_ban'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($d['ly_do'] ?: $d['ten_loai_phep']) ?></td>
                            <td><?= rtrim(rtrim($d['so_ngay'], '0'), '.') ?> ngày</td>
                            <td><?= badgeTrangThai($d['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
