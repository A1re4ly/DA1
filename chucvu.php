<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauVaiTro('ADMIN');
$pageTitle = 'Quản lý chức vụ - NovaHM';

$loi = '';

if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    try {
        $pdo->prepare("DELETE FROM chuc_vu WHERE id = :id")->execute([':id' => $id]);
        ghiNhatKy($pdo, 'DELETE', 'chuc_vu', $id, 'Xoá chức vụ');
        header('Location: chucvu.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: chucvu.php?err=inuse');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $ten_chuc_vu = trim($_POST['ten_chuc_vu'] ?? '');
    $cap_bac     = (int)($_POST['cap_bac'] ?? 1);
    $mo_ta       = trim($_POST['mo_ta'] ?? '');

    if ($ten_chuc_vu === '') {
        $loi = 'Vui lòng nhập tên chức vụ.';
    } else {
        try {
            if ($id) {
                $pdo->prepare("UPDATE chuc_vu SET ten_chuc_vu=:ten, cap_bac=:cb, mo_ta=:mt WHERE id=:id")
                    ->execute([':ten' => $ten_chuc_vu, ':cb' => $cap_bac, ':mt' => $mo_ta, ':id' => $id]);
                ghiNhatKy($pdo, 'UPDATE', 'chuc_vu', $id, 'Cập nhật chức vụ');
            } else {
                $pdo->prepare("INSERT INTO chuc_vu (ten_chuc_vu, cap_bac, mo_ta) VALUES (:ten, :cb, :mt)")
                    ->execute([':ten' => $ten_chuc_vu, ':cb' => $cap_bac, ':mt' => $mo_ta]);
                ghiNhatKy($pdo, 'CREATE', 'chuc_vu', $pdo->lastInsertId(), 'Tạo chức vụ mới');
            }
            header('Location: chucvu.php?msg=saved');
            exit;
        } catch (PDOException $e) {
            $loi = str_contains($e->getMessage(), 'Duplicate') ? 'Chức vụ này đã tồn tại.' : 'Lỗi khi lưu: ' . $e->getMessage();
        }
    }
}

$dangSua = null;
if (isset($_GET['sua'])) {
    $s = $pdo->prepare("SELECT * FROM chuc_vu WHERE id = :id");
    $s->execute([':id' => (int)$_GET['sua']]);
    $dangSua = $s->fetch();
}

$dsChucVu = $pdo->query("
    SELECT cv.*, (SELECT COUNT(*) FROM nhan_vien WHERE id_chuc_vu = cv.id) AS so_nhan_vien
    FROM chuc_vu cv ORDER BY cv.cap_bac DESC, cv.ten_chuc_vu
")->fetchAll();

$tenCapBac = [1 => 'Nhân viên', 2 => 'Trưởng nhóm', 3 => 'Trưởng phòng', 4 => 'Ban giám đốc'];

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Quản lý chức vụ</h3>
                    <button class="btn-primary" onclick="document.getElementById('formCV').style.display='block'">+ Thêm chức vụ</button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?= $_GET['msg'] === 'deleted' ? 'Đã xoá chức vụ.' : 'Đã lưu chức vụ.' ?>
                    </p>
                <?php endif; ?>
                <?php if (isset($_GET['err']) && $_GET['err'] === 'inuse'): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Không thể xoá — chức vụ này đang có nhân viên đang giữ.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formCV" style="display:<?= ($dangSua || $loi) ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="chucvu.php" method="POST" style="display:grid; grid-template-columns: repeat(3,1fr); gap:14px; align-items:end;">
                        <input type="hidden" name="id" value="<?= $dangSua['id'] ?? '' ?>">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Tên chức vụ</label><br>
                            <input type="text" name="ten_chuc_vu" value="<?= htmlspecialchars($dangSua['ten_chuc_vu'] ?? '') ?>" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Cấp bậc</label><br>
                            <select name="cap_bac" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <?php foreach ($tenCapBac as $cb => $ten): ?>
                                <option value="<?= $cb ?>" <?= isset($dangSua['cap_bac']) && (int)$dangSua['cap_bac']===$cb ? 'selected' : '' ?>><?= $cb ?> - <?= $ten ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Mô tả</label><br>
                            <input type="text" name="mo_ta" value="<?= htmlspecialchars($dangSua['mo_ta'] ?? '') ?>" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div style="grid-column: span 3; display:flex; gap:10px; justify-content:flex-end;">
                            <a href="chucvu.php" class="btn-secondary" style="background:#94a3b8;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Huỷ</a>
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu chức vụ</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Tên chức vụ</th><th>Cấp bậc</th><th>Số nhân viên</th><th>Mô tả</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsChucVu)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;">Chưa có chức vụ nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsChucVu as $cv): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cv['ten_chuc_vu']) ?></strong></td>
                            <td><?= $tenCapBac[(int)$cv['cap_bac']] ?? $cv['cap_bac'] ?></td>
                            <td><?= (int)$cv['so_nhan_vien'] ?></td>
                            <td><?= htmlspecialchars($cv['mo_ta'] ?? '') ?></td>
                            <td>
                                <a href="chucvu.php?sua=<?= $cv['id'] ?>#formCV" onclick="document.getElementById('formCV').style.display='block';" style="color:#3b82f6;margin-right:10px;" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="chucvu.php?xoa=<?= $cv['id'] ?>" style="color:#ef4444;" title="Xoá"
                                   onclick="return confirm('Xoá chức vụ <?= htmlspecialchars(addslashes($cv['ten_chuc_vu'])) ?>?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
