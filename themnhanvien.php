<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được

$dangSua = isset($_GET['id']);
$pageTitle = $dangSua ? 'Sửa nhân viên - NovaHM' : 'Thêm nhân viên mới - NovaHM';

$phongBanList = $pdo->query("SELECT id, ten_phong_ban FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();

$nv = [
    'ma_nv' => '', 'ho_ten' => '', 'sdt' => '', 'email' => '',
    'id_phong_ban' => '', 'id_chuc_vu' => '', 'ten_chuc_vu' => '',
    'ngay_vao_lam' => '', 'trang_thai_lam_viec' => 'Thử việc',
];
$loi = '';

if ($dangSua) {
    $stmt = $pdo->prepare("SELECT nv.*, cv.ten_chuc_vu FROM nhan_vien nv
                            LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
                            WHERE nv.id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $row = $stmt->fetch();
    if ($row) $nv = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nv = array_merge($nv, $_POST);

    if (trim($nv['ma_nv']) === '' || trim($nv['ho_ten']) === '' || trim($nv['email']) === '') {
        $loi = 'Vui lòng điền đầy đủ các trường bắt buộc (*).';
    } else {
        // Tìm hoặc tạo chức vụ theo tên nhập tự do (giữ đúng UX form gốc: input text)
        $idChucVu = null;
        if (trim($nv['ten_chuc_vu'] ?? '') !== '') {
            $s = $pdo->prepare("SELECT id FROM chuc_vu WHERE ten_chuc_vu = :t");
            $s->execute([':t' => trim($nv['ten_chuc_vu'])]);
            $idChucVu = $s->fetchColumn();
            if (!$idChucVu) {
                $pdo->prepare("INSERT INTO chuc_vu (ten_chuc_vu) VALUES (:t)")->execute([':t' => trim($nv['ten_chuc_vu'])]);
                $idChucVu = $pdo->lastInsertId();
            }
        }

        $idPhongBan = $nv['id_phong_ban'] !== '' ? (int)$nv['id_phong_ban'] : null;
        $ngayVaoLam = $nv['ngay_vao_lam'] !== '' ? $nv['ngay_vao_lam'] : null;

        try {
            if ($dangSua) {
                $sql = "UPDATE nhan_vien SET ma_nv=:ma_nv, ho_ten=:ho_ten, sdt=:sdt, email=:email,
                        id_phong_ban=:pb, id_chuc_vu=:cv, ngay_vao_lam=:ngay, trang_thai_lam_viec=:tt
                        WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ma_nv' => $nv['ma_nv'], ':ho_ten' => $nv['ho_ten'], ':sdt' => $nv['sdt'],
                    ':email' => $nv['email'], ':pb' => $idPhongBan, ':cv' => $idChucVu,
                    ':ngay' => $ngayVaoLam, ':tt' => $nv['trang_thai_lam_viec'], ':id' => (int)$_GET['id'],
                ]);
                ghiNhatKy($pdo, 'UPDATE', 'nhan_vien', (int)$_GET['id'], 'Cập nhật thông tin nhân viên');
            } else {
                $sql = "INSERT INTO nhan_vien (ma_nv, ho_ten, sdt, email, id_phong_ban, id_chuc_vu, ngay_vao_lam, trang_thai_lam_viec)
                        VALUES (:ma_nv, :ho_ten, :sdt, :email, :pb, :cv, :ngay, :tt)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ma_nv' => $nv['ma_nv'], ':ho_ten' => $nv['ho_ten'], ':sdt' => $nv['sdt'],
                    ':email' => $nv['email'], ':pb' => $idPhongBan, ':cv' => $idChucVu,
                    ':ngay' => $ngayVaoLam, ':tt' => $nv['trang_thai_lam_viec'],
                ]);
                ghiNhatKy($pdo, 'CREATE', 'nhan_vien', $pdo->lastInsertId(), 'Thêm nhân viên mới');
            }
            header('Location: danhsachnhanvien.php?msg=saved');
            exit;
        } catch (PDOException $e) {
            $loi = str_contains($e->getMessage(), 'Duplicate')
                ? 'Mã nhân viên hoặc email này đã tồn tại trong hệ thống.'
                : 'Có lỗi khi lưu dữ liệu: ' . $e->getMessage();
        }
    }
}

$extraStyle = '<style>
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group.full-width { grid-column: span 2; }
    .form-group label { font-weight: 600; font-size: 14px; color: #334155; }
    .form-group input, .form-group select, .form-group textarea { padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px; }
    .form-group input:focus, .form-group select:focus { border-color: #3b82f6; }
    .form-actions { margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end; }
    .btn-secondary { background-color: #94a3b8; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; font-size: 14px; }
    .btn-secondary:hover { background-color: #64748b; }
</style>';

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3><?= $dangSua ? 'Sửa thông tin nhân viên' : 'Thêm nhân viên mới' ?></h3>
                    <a href="danhsachnhanvien.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                </div>

                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <form action="themnhanvien.php<?= $dangSua ? '?id=' . (int)$_GET['id'] : '' ?>" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ma_nv">Mã nhân viên *</label>
                            <input type="text" id="ma_nv" name="ma_nv" placeholder="Ví dụ: NV004" value="<?= htmlspecialchars($nv['ma_nv']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="ho_ten">Họ và tên *</label>
                            <input type="text" id="ho_ten" name="ho_ten" placeholder="Nhập họ và tên" value="<?= htmlspecialchars($nv['ho_ten']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="sdt">Số điện thoại</label>
                            <input type="tel" id="sdt" name="sdt" placeholder="0901234567" value="<?= htmlspecialchars($nv['sdt'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" placeholder="nguyenvana@novacompany.vn" value="<?= htmlspecialchars($nv['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="id_phong_ban">Phòng ban *</label>
                            <select id="id_phong_ban" name="id_phong_ban" required>
                                <option value="">-- Chọn phòng ban --</option>
                                <?php foreach ($phongBanList as $pb): ?>
                                <option value="<?= $pb['id'] ?>" <?= (string)$nv['id_phong_ban'] === (string)$pb['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pb['ten_phong_ban']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ten_chuc_vu">Chức vụ</label>
                            <input type="text" id="ten_chuc_vu" name="ten_chuc_vu" placeholder="Chuyên viên / Trưởng phòng..." value="<?= htmlspecialchars($nv['ten_chuc_vu'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="ngay_vao_lam">Ngày vào làm</label>
                            <input type="date" id="ngay_vao_lam" name="ngay_vao_lam" value="<?= htmlspecialchars($nv['ngay_vao_lam'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="trang_thai_lam_viec">Trạng thái làm việc</label>
                            <select id="trang_thai_lam_viec" name="trang_thai_lam_viec">
                                <option value="Chính thức" <?= $nv['trang_thai_lam_viec'] === 'Chính thức' ? 'selected' : '' ?>>Chính thức</option>
                                <option value="Thử việc" <?= $nv['trang_thai_lam_viec'] === 'Thử việc' ? 'selected' : '' ?>>Thử việc</option>
                                <option value="Nghỉ việc" <?= $nv['trang_thai_lam_viec'] === 'Nghỉ việc' ? 'selected' : '' ?>>Nghỉ việc</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="danhsachnhanvien.php" class="btn-secondary">Hủy bỏ</a>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu nhân viên</button>
                    </div>
                </form>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
