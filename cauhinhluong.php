<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Cấu hình lương - NovaHM';

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO cau_hinh_luong
        (so_ngay_cong_chuan, luong_co_so, phu_cap_an_trua, phu_cap_di_lai, ty_le_bhxh, ty_le_bhyt, ty_le_bhtn, muc_phat_di_muon, ngay_ap_dung)
        VALUES (:sn, :lcs, :pcat, :pcdl, :bhxh, :bhyt, :bhtn, :phat, CURDATE())");
    try {
        $stmt->execute([
            ':sn'   => (int)$_POST['work_days'],
            ':lcs'  => (float)str_replace('.', '', $_POST['base_salary']),
            ':pcat' => (float)str_replace('.', '', $_POST['lunch_allowance']),
            ':pcdl' => (float)str_replace('.', '', $_POST['fuel_allowance']),
            ':bhxh' => (float)str_replace('%', '', $_POST['bhxh']),
            ':bhyt' => (float)str_replace('%', '', $_POST['bhyt']),
            ':bhtn' => (float)str_replace('%', '', $_POST['bhtn']),
            ':phat' => (float)str_replace('.', '', $_POST['late_fine']),
        ]);
        ghiNhatKy($pdo, 'UPDATE', 'cau_hinh_luong', $pdo->lastInsertId(), 'Cập nhật cấu hình lương');
        header('Location: cauhinhluong.php?msg=saved');
        exit;
    } catch (PDOException $e) {
        $loi = 'Lỗi khi lưu cấu hình: ' . $e->getMessage();
    }
}

// Lấy cấu hình đang áp dụng gần nhất
$cfg = $pdo->query("SELECT * FROM cau_hinh_luong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();
if (!$cfg) {
    $cfg = ['so_ngay_cong_chuan'=>22,'luong_co_so'=>1800000,'phu_cap_an_trua'=>730000,'phu_cap_di_lai'=>500000,
            'ty_le_bhxh'=>8,'ty_le_bhyt'=>1.5,'ty_le_bhtn'=>1,'muc_phat_di_muon'=>10000];
}

$extraStyle = '<style>
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-weight: 600; font-size: 14px; color: #334155; }
    .form-group input, .form-group select { padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px; }
    .form-group input:focus { border-color: #3b82f6; }
    .section-title { grid-column: span 2; font-size: 16px; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 10px; }
    .form-actions { margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; }
    .btn-secondary { background-color: #94a3b8; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; font-size: 14px; }
</style>';

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header"><h3>Cấu hình tham số tính lương</h3></div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã lưu cấu hình lương mới. Cấu hình này sẽ áp dụng cho các lần tính lương kể từ hôm nay.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <form action="cauhinhluong.php" method="POST">
                    <div class="form-grid">
                        <div class="section-title">1. Quy định công & Lương chuẩn</div>
                        <div class="form-group">
                            <label for="work-days">Số ngày công chuẩn / tháng</label>
                            <input type="number" id="work-days" name="work_days" value="<?= (int)$cfg['so_ngay_cong_chuan'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="base-salary">Mức lương cơ sở (VNĐ)</label>
                            <input type="text" id="base-salary" name="base_salary" value="<?= number_format($cfg['luong_co_so'],0,',','.') ?>">
                        </div>

                        <div class="section-title">2. Phụ cấp cố định</div>
                        <div class="form-group">
                            <label for="lunch-allowance">Phụ cấp ăn trưa (VNĐ/tháng)</label>
                            <input type="text" id="lunch-allowance" name="lunch_allowance" value="<?= number_format($cfg['phu_cap_an_trua'],0,',','.') ?>">
                        </div>
                        <div class="form-group">
                            <label for="fuel-allowance">Phụ cấp đi lại / Xăng xe (VNĐ/tháng)</label>
                            <input type="text" id="fuel-allowance" name="fuel_allowance" value="<?= number_format($cfg['phu_cap_di_lai'],0,',','.') ?>">
                        </div>

                        <div class="section-title">3. Tỷ lệ đóng Bảo hiểm (%)</div>
                        <div class="form-group">
                            <label for="bhxh">BHXH (Trừ vào lương NV)</label>
                            <input type="text" id="bhxh" name="bhxh" value="<?= rtrim(rtrim($cfg['ty_le_bhxh'],'0'),'.') ?>%">
                        </div>
                        <div class="form-group">
                            <label for="bhyt">BHYT (Trừ vào lương NV)</label>
                            <input type="text" id="bhyt" name="bhyt" value="<?= rtrim(rtrim($cfg['ty_le_bhyt'],'0'),'.') ?>%">
                        </div>
                        <div class="form-group">
                            <label for="bhtn">BHTN (Trừ vào lương NV)</label>
                            <input type="text" id="bhtn" name="bhtn" value="<?= rtrim(rtrim($cfg['ty_le_bhtn'],'0'),'.') ?>%">
                        </div>
                        <div class="form-group">
                            <label for="late-fine">Mức phạt đi muộn (VNĐ/phút)</label>
                            <input type="text" id="late-fine" name="late_fine" value="<?= number_format($cfg['muc_phat_di_muon'],0,',','.') ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình</button>
                    </div>
                </form>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
