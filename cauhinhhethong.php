<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauVaiTro('ADMIN');
$pageTitle = 'Cấu hình hệ thống - NovaHM';

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO cau_hinh_he_thong
            (gio_vao_chuan, gio_ra_chuan, so_phut_cho_phep_tre, so_ngay_phep_mac_dinh, so_ngay_phep_toi_da_chuyen, ngay_ap_dung, id_nguoi_cap_nhat)
            VALUES (:gv, :gr, :pt, :pmd, :ptdc, CURDATE(), :nd)");
        $stmt->execute([
            ':gv'   => $_POST['gio_vao_chuan'],
            ':gr'   => $_POST['gio_ra_chuan'],
            ':pt'   => (int)$_POST['so_phut_cho_phep_tre'],
            ':pmd'  => (float)$_POST['so_ngay_phep_mac_dinh'],
            ':ptdc' => (float)$_POST['so_ngay_phep_toi_da_chuyen'],
            ':nd'   => $currentUser['id'],
        ]);
        ghiNhatKy($pdo, 'UPDATE', 'cau_hinh_he_thong', $pdo->lastInsertId(), 'Cập nhật cấu hình hệ thống');
        header('Location: cauhinhhethong.php?msg=saved');
        exit;
    } catch (PDOException $e) {
        $loi = 'Lỗi khi lưu cấu hình: ' . $e->getMessage();
    }
}

$cfg = $pdo->query("SELECT * FROM cau_hinh_he_thong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();
if (!$cfg) {
    $cfg = ['gio_vao_chuan'=>'08:00:00','gio_ra_chuan'=>'17:00:00','so_phut_cho_phep_tre'=>0,'so_ngay_phep_mac_dinh'=>12,'so_ngay_phep_toi_da_chuyen'=>5];
}

$extraStyle = '<style>
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-weight: 600; font-size: 14px; color: #334155; }
    .form-group input { padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px; }
    .form-group input:focus { border-color: #3b82f6; }
    .section-title { grid-column: span 2; font-size: 16px; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 10px; }
    .form-group small { color:#94a3b8; font-size:12px; }
    .form-actions { margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; }
</style>';

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header"><h3>Cấu hình hệ thống</h3></div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã lưu cấu hình hệ thống mới, áp dụng từ hôm nay.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <form action="cauhinhhethong.php" method="POST">
                    <div class="form-grid">
                        <div class="section-title">1. Luật chấm công</div>
                        <div class="form-group">
                            <label>Giờ vào chuẩn</label>
                            <input type="time" name="gio_vao_chuan" value="<?= substr($cfg['gio_vao_chuan'],0,5) ?>">
                            <small>Nhân viên chấm công sau giờ này (+ số phút cho phép trễ) sẽ bị tính "Đi muộn".</small>
                        </div>
                        <div class="form-group">
                            <label>Giờ ra chuẩn</label>
                            <input type="time" name="gio_ra_chuan" value="<?= substr($cfg['gio_ra_chuan'],0,5) ?>">
                        </div>
                        <div class="form-group">
                            <label>Số phút cho phép đi trễ (không tính vi phạm)</label>
                            <input type="number" min="0" name="so_phut_cho_phep_tre" value="<?= (int)$cfg['so_phut_cho_phep_tre'] ?>">
                        </div>

                        <div class="section-title">2. Quy tắc tính phép</div>
                        <div class="form-group">
                            <label>Số ngày phép mặc định cấp mỗi năm</label>
                            <input type="number" step="0.5" name="so_ngay_phep_mac_dinh" value="<?= rtrim(rtrim($cfg['so_ngay_phep_mac_dinh'],'0'),'.') ?>">
                            <small>Áp dụng khi tạo quỹ phép năm mới cho nhân viên.</small>
                        </div>
                        <div class="form-group">
                            <label>Số ngày phép tối đa được chuyển sang năm sau</label>
                            <input type="number" step="0.5" name="so_ngay_phep_toi_da_chuyen" value="<?= rtrim(rtrim($cfg['so_ngay_phep_toi_da_chuyen'],'0'),'.') ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình</button>
                    </div>
                </form>

                <p style="margin-top:20px;font-size:13px;color:#94a3b8;">
                    Cấu hình lương (mức lương cơ sở, phụ cấp, tỷ lệ BHXH/BHYT/BHTN, mức phạt đi muộn) nằm ở trang riêng
                    <a href="cauhinhluong.php" style="color:#3b82f6;">Cấu hình lương</a>.
                </p>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
