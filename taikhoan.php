<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Quản lý tài khoản - NovaHM';

$loi = '';

// Xoá tài khoản
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    if ($id === (int)$currentUser['id']) {
        header('Location: taikhoan.php?err=selfdelete');
        exit;
    }
    $pdo->prepare("DELETE FROM nguoi_dung WHERE id = :id")->execute([':id' => $id]);
    ghiNhatKy($pdo, 'DELETE', 'nguoi_dung', $id, 'Xoá tài khoản');
    header('Location: taikhoan.php?msg=deleted');
    exit;
}

// Khoá / Mở khoá tài khoản
if (isset($_GET['khoa']) || isset($_GET['mokhoa'])) {
    $id = (int)($_GET['khoa'] ?? $_GET['mokhoa']);
    $trangThaiMoi = isset($_GET['khoa']) ? 'Khoá' : 'Hoạt động';
    if ($id === (int)$currentUser['id'] && $trangThaiMoi === 'Khoá') {
        header('Location: taikhoan.php?err=selflock');
        exit;
    }
    $pdo->prepare("UPDATE nguoi_dung SET trang_thai = :tt WHERE id = :id")->execute([':tt' => $trangThaiMoi, ':id' => $id]);
    ghiNhatKy($pdo, 'UPDATE', 'nguoi_dung', $id, $trangThaiMoi);
    header('Location: taikhoan.php?msg=updated');
    exit;
}

// Đặt lại mật khẩu (admin đặt mật khẩu mới cho nhân viên)
if (isset($_POST['reset_id'])) {
    $id = (int)$_POST['reset_id'];
    $mkMoi = trim($_POST['mk_moi'] ?? '');
    if (strlen($mkMoi) < 6) {
        $loi = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } else {
        $pdo->prepare("UPDATE nguoi_dung SET mat_khau_hash = :h WHERE id = :id")
            ->execute([':h' => password_hash($mkMoi, PASSWORD_BCRYPT), ':id' => $id]);
        ghiNhatKy($pdo, 'UPDATE', 'nguoi_dung', $id, 'Đặt lại mật khẩu');
        header('Location: taikhoan.php?msg=reset');
        exit;
    }
}

// Tạo tài khoản mới cho nhân viên
if (isset($_POST['tao_moi'])) {
    $id_nv   = (int)($_POST['id_nv'] ?? 0);
    $email   = trim($_POST['email'] ?? '');
    $mk      = trim($_POST['mat_khau'] ?? '');
    $id_vt   = (int)($_POST['id_vai_tro'] ?? 0);

    if (!$id_nv || $email === '' || strlen($mk) < 6 || !$id_vt) {
        $loi = 'Vui lòng điền đầy đủ thông tin, mật khẩu tối thiểu 6 ký tự.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO nguoi_dung (id_nv, email, mat_khau_hash, id_vai_tro, trang_thai)
                                    VALUES (:nv, :email, :mk, :vt, 'Hoạt động')");
            $stmt->execute([
                ':nv' => $id_nv, ':email' => $email,
                ':mk' => password_hash($mk, PASSWORD_BCRYPT), ':vt' => $id_vt,
            ]);
            ghiNhatKy($pdo, 'CREATE', 'nguoi_dung', $pdo->lastInsertId(), 'Tạo tài khoản đăng nhập cho nhân viên');
            header('Location: taikhoan.php?msg=created');
            exit;
        } catch (PDOException $e) {
            $loi = str_contains($e->getMessage(), 'Duplicate')
                ? 'Email này đã được dùng cho một tài khoản khác.'
                : 'Lỗi khi tạo tài khoản: ' . $e->getMessage();
        }
    }
}

// Danh sách tài khoản hiện có
$dsTaiKhoan = $pdo->query("
    SELECT nd.id, nd.email, nd.trang_thai, nd.lan_dang_nhap_cuoi,
           vt.ten_vai_tro, nv.ma_nv, nv.ho_ten
    FROM nguoi_dung nd
    JOIN vai_tro vt ON vt.id = nd.id_vai_tro
    LEFT JOIN nhan_vien nv ON nv.id = nd.id_nv
    ORDER BY nd.id DESC
")->fetchAll();

// Nhân viên chưa có tài khoản -> để hiện trong dropdown tạo mới
$nhanVienChuaCoTK = $pdo->query("
    SELECT nv.id, nv.ma_nv, nv.ho_ten, nv.email
    FROM nhan_vien nv
    WHERE nv.id NOT IN (SELECT id_nv FROM nguoi_dung WHERE id_nv IS NOT NULL)
      AND nv.trang_thai_lam_viec <> 'Nghỉ việc'
    ORDER BY nv.ho_ten
")->fetchAll();

$dsVaiTro = $pdo->query("SELECT id, ten_vai_tro FROM vai_tro ORDER BY id")->fetchAll();

function badgeTaiKhoan($tt) {
    return $tt === 'Hoạt động'
        ? '<span class="badge badge-success">Hoạt động</span>'
        : '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Khoá</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Quản lý tài khoản đăng nhập</h3>
                    <button class="btn-primary" onclick="document.getElementById('formTaoTK').style.display='block'">
                        <i class="fa-solid fa-user-plus"></i> Tạo tài khoản cho nhân viên
                    </button>
                </div>

                <?php if (isset($_GET['msg'])):
                    $mmap = ['created'=>'Đã tạo tài khoản mới.', 'deleted'=>'Đã xoá tài khoản.', 'updated'=>'Đã cập nhật trạng thái tài khoản.', 'reset'=>'Đã đặt lại mật khẩu.'];
                ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= $mmap[$_GET['msg']] ?? '' ?></p>
                <?php endif; ?>
                <?php if (isset($_GET['err'])):
                    $emap = ['selfdelete'=>'Không thể tự xoá tài khoản đang đăng nhập.', 'selflock'=>'Không thể tự khoá tài khoản đang đăng nhập.'];
                ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= $emap[$_GET['err']] ?? '' ?></p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <!-- FORM TẠO TÀI KHOẢN MỚI -->
                <div id="formTaoTK" style="display:<?= $loi && isset($_POST['tao_moi']) ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <?php if (empty($nhanVienChuaCoTK)): ?>
                        <p style="color:#94a3b8;">Tất cả nhân viên đang làm việc đã có tài khoản.</p>
                    <?php else: ?>
                    <form action="taikhoan.php" method="POST" style="display:grid; grid-template-columns: repeat(4,1fr); gap:14px; align-items:end;">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Nhân viên</label><br>
                            <select id="chonNV" name="id_nv" required onchange="document.getElementById('emailTK').value = this.options[this.selectedIndex].dataset.email || '';" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach ($nhanVienChuaCoTK as $nv): ?>
                                <option value="<?= $nv['id'] ?>" data-email="<?= htmlspecialchars($nv['email']) ?>"><?= htmlspecialchars($nv['ma_nv'].' - '.$nv['ho_ten']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Email đăng nhập</label><br>
                            <input type="email" id="emailTK" name="email" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Mật khẩu ban đầu</label><br>
                            <input type="text" name="mat_khau" placeholder="Tối thiểu 6 ký tự" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Vai trò hệ thống</label><br>
                            <select name="id_vai_tro" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <?php foreach ($dsVaiTro as $vt): ?>
                                <option value="<?= $vt['id'] ?>"><?= htmlspecialchars($vt['ten_vai_tro']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="grid-column: span 4;">
                            <button type="submit" name="tao_moi" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Tạo tài khoản</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Email</th><th>Nhân viên liên kết</th><th>Vai trò</th><th>Đăng nhập cuối</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsTaiKhoan)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Chưa có tài khoản nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsTaiKhoan as $tk): ?>
                        <tr>
                            <td><?= htmlspecialchars($tk['email']) ?></td>
                            <td><?= $tk['ma_nv'] ? htmlspecialchars($tk['ma_nv'].' - '.$tk['ho_ten']) : '<span style="color:#94a3b8;">Tài khoản hệ thống</span>' ?></td>
                            <td><?= htmlspecialchars($tk['ten_vai_tro']) ?></td>
                            <td><?= $tk['lan_dang_nhap_cuoi'] ? date('d/m/Y H:i', strtotime($tk['lan_dang_nhap_cuoi'])) : '--' ?></td>
                            <td><?= badgeTaiKhoan($tk['trang_thai']) ?></td>
                            <td style="white-space:nowrap;">
                                <button type="button" onclick="document.getElementById('reset-<?= $tk['id'] ?>').style.display='flex'"
                                        style="border:none;background:none;color:#3b82f6;cursor:pointer;margin-right:8px;" title="Đặt lại mật khẩu">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <?php if ($tk['trang_thai'] === 'Hoạt động'): ?>
                                <a href="taikhoan.php?khoa=<?= $tk['id'] ?>" style="color:#f59e0b;margin-right:8px;" title="Khoá tài khoản"
                                   onclick="return confirm('Khoá tài khoản này?');"><i class="fa-solid fa-lock"></i></a>
                                <?php else: ?>
                                <a href="taikhoan.php?mokhoa=<?= $tk['id'] ?>" style="color:#10b981;margin-right:8px;" title="Mở khoá"><i class="fa-solid fa-lock-open"></i></a>
                                <?php endif; ?>
                                <a href="taikhoan.php?xoa=<?= $tk['id'] ?>" style="color:#ef4444;" title="Xoá tài khoản"
                                   onclick="return confirm('Xoá tài khoản <?= htmlspecialchars(addslashes($tk['email'])) ?>?');"><i class="fa-solid fa-trash"></i></a>

                                <div id="reset-<?= $tk['id'] ?>" style="display:none;align-items:center;gap:6px;margin-top:8px;">
                                    <form action="taikhoan.php" method="POST" style="display:flex;gap:6px;">
                                        <input type="hidden" name="reset_id" value="<?= $tk['id'] ?>">
                                        <input type="text" name="mk_moi" placeholder="Mật khẩu mới" required style="padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;font-size:13px;">
                                        <button type="submit" class="btn-primary" style="padding:4px 10px;font-size:13px;">Lưu</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
