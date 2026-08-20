<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();  // tài khoản phải gắn với 1 hồ sơ nhân viên
$pageTitle = 'Trang cá nhân - NovaHM';

$idNv = $currentUser['id_nv'];

// Thông tin cơ bản
$stmt = $pdo->prepare("
    SELECT nv.ho_ten, nv.ma_nv, pb.ten_phong_ban, cv.ten_chuc_vu
    FROM nhan_vien nv
    LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
    LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
    WHERE nv.id = :id
");
$stmt->execute([':id' => $idNv]);
$hoSo = $stmt->fetch();

// Quỹ phép năm nay
$s = $pdo->prepare("SELECT * FROM quy_phep_nam WHERE id_nv = :nv AND nam = YEAR(CURDATE())");
$s->execute([':nv' => $idNv]);
$quyPhep = $s->fetch() ?: ['tong_phep' => 0, 'da_su_dung' => 0, 'con_lai' => 0];

// Chấm công hôm nay
$s = $pdo->prepare("SELECT * FROM bang_cham_cong_ngay WHERE id_nv = :nv AND ngay = CURDATE()");
$s->execute([':nv' => $idNv]);
$ccHomNay = $s->fetch();

// Lương tháng gần nhất đã có
$s = $pdo->prepare("SELECT * FROM bang_luong_thang WHERE id_nv = :nv ORDER BY nam DESC, thang DESC LIMIT 1");
$s->execute([':nv' => $idNv]);
$luongGanNhat = $s->fetch();

// Đơn phép gần đây
$s = $pdo->prepare("
    SELECT dnp.*, lp.ten_loai_phep
    FROM don_nghi_phep dnp
    JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
    WHERE dnp.id_nv = :nv
    ORDER BY dnp.ngay_tao DESC LIMIT 5
");
$s->execute([':nv' => $idNv]);
$donPhepGanDay = $s->fetchAll();

function badgeDonPhepCN($tt) {
    if ($tt === 'Đã duyệt') return '<span class="badge badge-success">Đã duyệt</span>';
    if ($tt === 'Chờ duyệt') return '<span class="badge badge-warning">Chờ duyệt</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Từ chối</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-user"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size:16px;"><?= htmlspecialchars($hoSo['ho_ten'] ?? '') ?></h3>
                        <p><?= htmlspecialchars(($hoSo['ten_chuc_vu'] ?? '--') . ' · ' . ($hoSo['ten_phong_ban'] ?? '--')) ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3><?= $ccHomNay && $ccHomNay['gio_vao'] ? substr($ccHomNay['gio_vao'],0,5) : '--:--' ?></h3>
                        <p>Giờ vào hôm nay</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size:18px;"><?= $luongGanNhat ? formatTien($luongGanNhat['thuc_linh']) : '--' ?></h3>
                        <p>Lương gần nhất (<?= $luongGanNhat ? $luongGanNhat['thang'].'/'.$luongGanNhat['nam'] : '--' ?>)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-mug-hot"></i></div>
                    <div class="stat-info">
                        <h3><?= rtrim(rtrim($quyPhep['con_lai'],'0'),'.') ?> ngày</h3>
                        <p>Phép năm còn lại</p>
                    </div>
                </div>
            </section>

            <section class="data-section">
                <div class="section-header">
                    <h3>Đơn nghỉ phép gần đây của tôi</h3>
                    <a href="phepnam_canhan.php" class="btn-primary" style="text-decoration:none;">+ Tạo đơn nghỉ phép</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Loại phép</th><th>Từ ngày</th><th>Đến ngày</th><th>Số ngày</th><th>Lý do</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($donPhepGanDay)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Bạn chưa có đơn nghỉ phép nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($donPhepGanDay as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['ten_loai_phep']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['tu_ngay'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['den_ngay'])) ?></td>
                            <td><?= rtrim(rtrim($d['so_ngay'],'0'),'.') ?></td>
                            <td><?= htmlspecialchars($d['ly_do']) ?></td>
                            <td><?= badgeDonPhepCN($d['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
