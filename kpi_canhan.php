<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'KPI của tôi - NovaHM';

$idNv = $currentUser['id_nv'];
$stmt = $pdo->prepare("SELECT * FROM danh_gia_kpi WHERE id_nv = :nv ORDER BY ky_danh_gia DESC");
$stmt->execute([':nv' => $idNv]);
$dsKpi = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header"><h3>Đánh giá hiệu suất (KPI) của tôi</h3></div>
                <table class="data-table">
                    <thead>
                        <tr><th>Kỳ đánh giá</th><th>Điểm KPI</th><th>Nhận xét</th><th>Ngày đánh giá</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsKpi)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#94a3b8;">Bạn chưa có đánh giá KPI nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsKpi as $k): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($k['ky_danh_gia']) ?></strong></td>
                            <td><?= $k['diem_kpi'] !== null ? rtrim(rtrim($k['diem_kpi'],'0'),'.') : '--' ?></td>
                            <td><?= htmlspecialchars($k['nhan_xet'] ?? '') ?></td>
                            <td><?= date('d/m/Y', strtotime($k['ngay_tao'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
