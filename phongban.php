<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauVaiTro('ADMIN');  // Chỉ Admin quản lý danh mục chung
$pageTitle = 'Quản lý phòng ban - NovaHM';

$loi = '';

// Xoá phòng ban
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    try {
        $pdo->prepare("DELETE FROM phong_ban WHERE id = :id")->execute([':id' => $id]);
        ghiNhatKy($pdo, 'DELETE', 'phong_ban', $id, 'Xoá phòng ban');
        header('Location: phongban.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: phongban.php?err=inuse');
        exit;
    }
}

// Thêm / sửa phòng ban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = (int)($_POST['id'] ?? 0);
    $ma_phong_ban  = trim($_POST['ma_phong_ban'] ?? '');
    $ten_phong_ban = trim($_POST['ten_phong_ban'] ?? '');
    $truong_phong  = $_POST['truong_phong_id'] !== '' ? (int)$_POST['truong_phong_id'] : null;
    $mo_ta         = trim($_POST['mo_ta'] ?? '');

    if ($ma_phong_ban === '' || $ten_phong_ban === '') {
        $loi = 'Vui lòng điền mã và tên phòng ban.';
    } else {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE phong_ban SET ma_phong_ban=:ma, ten_phong_ban=:ten, truong_phong_id=:tp, mo_ta=:mt WHERE id=:id");
                $stmt->execute([':ma' => $ma_phong_ban, ':ten' => $ten_phong_ban, ':tp' => $truong_phong, ':mt' => $mo_ta, ':id' => $id]);
                ghiNhatKy($pdo, 'UPDATE', 'phong_ban', $id, 'Cập nhật phòng ban');
            } else {
                $stmt = $pdo->prepare("INSERT INTO phong_ban (ma_phong_ban, ten_phong_ban, truong_phong_id, mo_ta) VALUES (:ma, :ten, :tp, :mt)");
                $stmt->execute([':ma' => $ma_phong_ban, ':ten' => $ten_phong_ban, ':tp' => $truong_phong, ':mt' => $mo_ta]);
                ghiNhatKy($pdo, 'CREATE', 'phong_ban', $pdo->lastInsertId(), 'Tạo phòng ban mới');
            }
            header('Location: phongban.php?msg=saved');
            exit;
        } catch (PDOException $e) {
            $loi = str_contains($e->getMessage(), 'Duplicate') ? 'Mã phòng ban này đã tồn tại.' : 'Lỗi khi lưu: ' . $e->getMessage();
        }
    }
}

$dangSua = null;
if (isset($_GET['sua'])) {
    $s = $pdo->prepare("SELECT * FROM phong_ban WHERE id = :id");
    $s->execute([':id' => (int)$_GET['sua']]);
    $dangSua = $s->fetch();
}

$dsPhongBan = $pdo->query("
    SELECT pb.*, nv.ho_ten AS ten_truong_phong,
           (SELECT COUNT(*) FROM nhan_vien WHERE id_phong_ban = pb.id) AS so_nhan_vien
    FROM phong_ban pb
    LEFT JOIN nhan_vien nv ON nv.id = pb.truong_phong_id
    ORDER BY pb.ten_phong_ban
")->fetchAll();

$dsNhanVienChon = $pdo->query("SELECT id, ma_nv, ho_ten FROM nhan_vien WHERE trang_thai_lam_viec <> 'Nghỉ việc' ORDER BY ho_ten")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Quản lý phòng ban</h3>
                    <button class="btn-primary" onclick="document.getElementById('formPB').style.display='block'">+ Thêm phòng ban</button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?= $_GET['msg'] === 'deleted' ? 'Đã xoá phòng ban.' : 'Đã lưu phòng ban.' ?>
                    </p>
                <?php endif; ?>
                <?php if (isset($_GET['err']) && $_GET['err'] === 'inuse'): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Không thể xoá — phòng ban này đang có nhân viên trực thuộc.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formPB" style="display:<?= ($dangSua || $loi) ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="phongban.php" method="POST" style="display:grid; grid-template-columns: repeat(2,1fr); gap:14px;">
                        <input type="hidden" name="id" value="<?= $dangSua['id'] ?? '' ?>">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Mã phòng ban</label><br>
                            <input type="text" name="ma_phong_ban" value="<?= htmlspecialchars($dangSua['ma_phong_ban'] ?? '') ?>" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Tên phòng ban</label><br>
                            <input type="text" name="ten_phong_ban" value="<?= htmlspecialchars($dangSua['ten_phong_ban'] ?? '') ?>" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Trưởng phòng</label><br>
                            <select name="truong_phong_id" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <option value="">-- Chưa có --</option>
                                <?php foreach ($dsNhanVienChon as $nv): ?>
                                <option value="<?= $nv['id'] ?>" <?= isset($dangSua['truong_phong_id']) && (string)$dangSua['truong_phong_id']===(string)$nv['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nv['ma_nv'].' - '.$nv['ho_ten']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Người được chọn sẽ có quyền duyệt đơn phép/đánh giá KPI cho nhân viên phòng này khi đăng nhập với vai trò "Trưởng phòng".</p>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Mô tả</label><br>
                            <input type="text" name="mo_ta" value="<?= htmlspecialchars($dangSua['mo_ta'] ?? '') ?>" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div style="grid-column: span 2; display:flex; gap:10px; justify-content:flex-end;">
                            <a href="phongban.php" class="btn-secondary" style="background:#94a3b8;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Huỷ</a>
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu phòng ban</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã</th><th>Tên phòng ban</th><th>Trưởng phòng</th><th>Số nhân viên</th><th>Mô tả</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsPhongBan)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Chưa có phòng ban nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsPhongBan as $pb): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pb['ma_phong_ban']) ?></strong></td>
                            <td><?= htmlspecialchars($pb['ten_phong_ban']) ?></td>
                            <td><?= htmlspecialchars($pb['ten_truong_phong'] ?? '--') ?></td>
                            <td><?= (int)$pb['so_nhan_vien'] ?></td>
                            <td><?= htmlspecialchars($pb['mo_ta'] ?? '') ?></td>
                            <td>
                                <a href="phongban.php?sua=<?= $pb['id'] ?>#formPB" onclick="document.getElementById('formPB').style.display='block';" style="color:#3b82f6;margin-right:10px;" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="phongban.php?xoa=<?= $pb['id'] ?>" style="color:#ef4444;" title="Xoá"
                                   onclick="return confirm('Xoá phòng ban <?= htmlspecialchars(addslashes($pb['ten_phong_ban'])) ?>?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
