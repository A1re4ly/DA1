<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'Lương của tôi - NovaHM';

$idNv = $currentUser['id_nv'];

$stmt = $pdo->prepare("
    SELECT * FROM bang_luong_thang
    WHERE id_nv = :nv
    ORDER BY nam DESC, thang DESC
");
$stmt->execute([':nv' => $idNv]);
$dsLuong = $stmt->fetchAll();

function badgeLuongCN($tt) {
    $map = ['Đã duyệt' => 'badge-success', 'Chờ duyệt' => 'badge-warning', 'Đã thanh toán' => 'badge-success'];
    return '<span class="badge '.($map[$tt] ?? '').'">'.htmlspecialchars($tt).'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header"><h3>Lịch sử phiếu lương</h3></div>
                <table class="data-table">
                    <thead>
                        <tr><th>Tháng</th><th>Lương cơ bản</th><th>Ngày công</th><th>Phụ cấp</th><th>Khấu trừ</th><th>Thực lĩnh</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsLuong)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#94a3b8;">Chưa có phiếu lương nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsLuong as $bl): ?>
                        <tr>
                            <td><strong><?= (int)$bl['thang'] ?>/<?= $bl['nam'] ?></strong></td>
                            <td><?= formatTien($bl['luong_co_ban']) ?></td>
                            <td><?= rtrim(rtrim($bl['ngay_cong_thuc_te'],'0'),'.') ?>/<?= rtrim(rtrim($bl['ngay_cong_chuan'],'0'),'.') ?></td>
                            <td><?= formatTien($bl['tong_phu_cap']) ?></td>
                            <td><?= formatTien($bl['tong_khau_tru']) ?></td>
                            <td><strong><?= formatTien($bl['thuc_linh']) ?></strong></td>
                            <td><?= badgeLuongCN($bl['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:16px;font-size:13px;color:#94a3b8;">Thắc mắc về lương? Liên hệ bộ phận Kế toán / Nhân sự để được giải đáp.</p>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
