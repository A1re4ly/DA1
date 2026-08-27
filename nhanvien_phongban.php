<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauVaiTro('TRUONGPHONG', 'ADMIN');
$pageTitle = 'Nhân viên phòng ban - NovaHM';

$laAdmin = laVaiTro('ADMIN');
$phongBanQL = $currentUser['phong_ban_quan_ly'];

if ($laAdmin) {
    // Admin xem được chọn phòng ban bất kỳ để kiểm tra
    $idPhongBanXem = isset($_GET['pb']) ? (int)$_GET['pb'] : null;
    $dsTatCaPhongBan = $pdo->query("SELECT id, ten_phong_ban FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();
} else {
    $idPhongBanXem = $phongBanQL[0] ?? null;
}

$dsNhanVien = [];
if ($idPhongBanXem) {
    $s = $pdo->prepare("
        SELECT nv.ma_nv, nv.ho_ten, nv.sdt, nv.email, nv.trang_thai_lam_viec, cv.ten_chuc_vu, nv.ngay_vao_lam
        FROM nhan_vien nv
        LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
        WHERE nv.id_phong_ban = :pb
        ORDER BY nv.ho_ten
    ");
    $s->execute([':pb' => $idPhongBanXem]);
    $dsNhanVien = $s->fetchAll();
}

function badgeTrangThaiNVPB($tt) {
    if ($tt === 'Chính thức') return '<span class="badge badge-success">Chính thức</span>';
    if ($tt === 'Thử việc')   return '<span class="badge badge-warning">Thử việc</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Nghỉ việc</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Nhân viên phòng ban<?= !$laAdmin ? ' của tôi' : '' ?></h3>
                    <?php if ($laAdmin): ?>
                    <form action="nhanvien_phongban.php" method="GET">
                        <select name="pb" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                            <option value="">-- Chọn phòng ban --</option>
                            <?php foreach ($dsTatCaPhongBan as $pb): ?>
                            <option value="<?= $pb['id'] ?>" <?= $idPhongBanXem===$pb['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pb['ten_phong_ban']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (!$laAdmin && !$idPhongBanXem): ?>
                    <p style="background:#fef3c7;color:#d97706;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        Tài khoản của bạn chưa được gán làm Trưởng phòng của phòng ban nào. Liên hệ Admin để được gán (mục Quản lý phòng ban).
                    </p>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Chức vụ</th><th>SĐT</th><th>Email</th><th>Ngày vào làm</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsNhanVien)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#94a3b8;">Không có nhân viên nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsNhanVien as $nv): ?>
                        <tr>
                            <td><?= htmlspecialchars($nv['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($nv['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($nv['ten_chuc_vu'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($nv['sdt'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($nv['email']) ?></td>
                            <td><?= $nv['ngay_vao_lam'] ? date('d/m/Y', strtotime($nv['ngay_vao_lam'])) : '--' ?></td>
                            <td><?= badgeTrangThaiNVPB($nv['trang_thai_lam_viec']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
