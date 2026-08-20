<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'Hồ sơ của tôi - NovaHM';

$idNv = $currentUser['id_nv'];

$stmt = $pdo->prepare("
    SELECT nv.*, pb.ten_phong_ban, cv.ten_chuc_vu
    FROM nhan_vien nv
    LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
    LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
    WHERE nv.id = :id
");
$stmt->execute([':id' => $idNv]);
$hs = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM hop_dong_lao_dong WHERE id_nv = :id ORDER BY ngay_bat_dau DESC");
$stmt->execute([':id' => $idNv]);
$dsHopDong = $stmt->fetchAll();

function badgeHopDongCN($tt) {
    $map = ['Còn hiệu lực' => 'badge-success', 'Sắp hết hạn' => 'badge-warning'];
    if (isset($map[$tt])) return '<span class="badge '.$map[$tt].'">'.htmlspecialchars($tt).'</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">'.htmlspecialchars($tt).'</span>';
}

function badgeTrangThaiNVCN($tt) {
    if ($tt === 'Chính thức') return '<span class="badge badge-success">Chính thức</span>';
    if ($tt === 'Thử việc')   return '<span class="badge badge-warning">Thử việc</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Nghỉ việc</span>';
}

$extraStyle = '<style>
    .info-grid { display:grid; grid-template-columns: repeat(2,1fr); gap: 18px 30px; margin-top: 10px; }
    .info-item label { display:block; font-size:12px; color:#64748b; font-weight:600; margin-bottom:4px; text-transform:uppercase; letter-spacing:.3px; }
    .info-item div { font-size:15px; color:#0f172a; }
</style>';

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section" style="margin-bottom:24px;">
                <div class="section-header"><h3>Thông tin nhân viên</h3></div>
                <div class="info-grid">
                    <div class="info-item"><label>Mã nhân viên</label><div><?= htmlspecialchars($hs['ma_nv']) ?></div></div>
                    <div class="info-item"><label>Họ và tên</label><div><?= htmlspecialchars($hs['ho_ten']) ?></div></div>
                    <div class="info-item"><label>Phòng ban</label><div><?= htmlspecialchars($hs['ten_phong_ban'] ?? '--') ?></div></div>
                    <div class="info-item"><label>Chức vụ</label><div><?= htmlspecialchars($hs['ten_chuc_vu'] ?? '--') ?></div></div>
                    <div class="info-item"><label>Số điện thoại</label><div><?= htmlspecialchars($hs['sdt'] ?? '--') ?></div></div>
                    <div class="info-item"><label>Email</label><div><?= htmlspecialchars($hs['email']) ?></div></div>
                    <div class="info-item"><label>Ngày vào làm</label><div><?= $hs['ngay_vao_lam'] ? date('d/m/Y', strtotime($hs['ngay_vao_lam'])) : '--' ?></div></div>
                    <div class="info-item"><label>Trạng thái làm việc</label><div><?= badgeTrangThaiNVCN($hs['trang_thai_lam_viec']) ?></div></div>
                </div>
                <p style="margin-top:16px;font-size:13px;color:#94a3b8;">Thông tin sai lệch? Liên hệ bộ phận Nhân sự để được cập nhật.</p>
            </section>

            <section class="data-section">
                <div class="section-header"><h3>Hợp đồng lao động của tôi</h3></div>
                <table class="data-table">
                    <thead>
                        <tr><th>Mã HĐ</th><th>Loại hợp đồng</th><th>Ngày bắt đầu</th><th>Ngày hết hạn</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsHopDong)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;">Chưa có hợp đồng nào được ghi nhận</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsHopDong as $hd): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($hd['ma_hd']) ?></strong></td>
                            <td><?= htmlspecialchars($hd['loai_hop_dong']) ?></td>
                            <td><?= date('d/m/Y', strtotime($hd['ngay_bat_dau'])) ?></td>
                            <td><?= $hd['ngay_ket_thuc'] ? date('d/m/Y', strtotime($hd['ngay_ket_thuc'])) : '--' ?></td>
                            <td><?= badgeHopDongCN($hd['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
