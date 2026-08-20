<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'Chấm công của tôi - NovaHM';

$idNv = $currentUser['id_nv'];
$thangNam = $_GET['thang'] ?? date('Y-m');
[$nam, $thang] = explode('-', $thangNam);

$stmt = $pdo->prepare("
    SELECT ngay, gio_vao, gio_ra, so_gio_lam, so_phut_tre, trang_thai
    FROM bang_cham_cong_ngay
    WHERE id_nv = :nv AND MONTH(ngay) = :t AND YEAR(ngay) = :n
    ORDER BY ngay DESC
");
$stmt->execute([':nv' => $idNv, ':t' => $thang, ':n' => $nam]);
$dsChamCong = $stmt->fetchAll();

$soNgayDungGio = 0; $soNgayTre = 0; $tongGio = 0;
foreach ($dsChamCong as $cc) {
    if ($cc['trang_thai'] === 'Đúng giờ') $soNgayDungGio++;
    if ($cc['trang_thai'] === 'Đi muộn') $soNgayTre++;
    $tongGio += (float)$cc['so_gio_lam'];
}

function badgeChamCongCN($tt) {
    $map = ['Đúng giờ' => 'badge-success', 'Đi muộn' => 'badge-warning', 'Về sớm' => 'badge-warning'];
    if (isset($map[$tt])) return '<span class="badge '.$map[$tt].'">'.htmlspecialchars($tt).'</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">'.htmlspecialchars($tt ?: 'Vắng mặt').'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="stat-info"><h3><?= count($dsChamCong) ?></h3><p>Ngày có chấm công</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-info"><h3><?= $soNgayDungGio ?></h3><p>Ngày đúng giờ</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info"><h3><?= $soNgayTre ?></h3><p>Ngày đi muộn</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-business-time"></i></div>
                    <div class="stat-info"><h3><?= rtrim(rtrim(number_format($tongGio,1),'0'),'.') ?>h</h3><p>Tổng giờ làm</p></div>
                </div>
            </section>

            <section class="data-section">
                <div class="section-header">
                    <h3>Chi tiết chấm công tháng <?= (int)$thang ?>/<?= $nam ?></h3>
                    <form action="chamcong_canhan.php" method="GET">
                        <input type="month" name="thang" value="<?= htmlspecialchars($thangNam) ?>" onchange="this.form.submit()"
                               style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                    </form>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Ngày</th><th>Giờ vào</th><th>Giờ ra</th><th>Số giờ làm</th><th>Phút trễ</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsChamCong)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Không có dữ liệu chấm công trong tháng này</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsChamCong as $cc): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($cc['ngay'])) ?></td>
                            <td><?= $cc['gio_vao'] ?: '--:--:--' ?></td>
                            <td><?= $cc['gio_ra'] ?: '--:--:--' ?></td>
                            <td><?= $cc['so_gio_lam'] ? rtrim(rtrim($cc['so_gio_lam'],'0'),'.').'h' : '--' ?></td>
                            <td><?= (int)$cc['so_phut_tre'] ?: '--' ?></td>
                            <td><?= badgeChamCongCN($cc['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
