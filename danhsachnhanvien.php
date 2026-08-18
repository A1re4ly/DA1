<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Danh sách nhân viên - NovaHM';

// Xử lý xoá nhân viên
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $pdo->prepare("DELETE FROM nhan_vien WHERE id = :id")->execute([':id' => $id]);
    ghiNhatKy($pdo, 'DELETE', 'nhan_vien', $id, 'Xoá nhân viên');
    header('Location: danhsachnhanvien.php?msg=deleted');
    exit;
}

// Tìm kiếm đơn giản theo tên/mã NV
$tuKhoa = trim($_GET['q'] ?? '');
$sql = "SELECT nv.id, nv.ma_nv, nv.ho_ten, nv.sdt, nv.trang_thai_lam_viec,
               cv.ten_chuc_vu, pb.ten_phong_ban
        FROM nhan_vien nv
        LEFT JOIN chuc_vu cv ON cv.id = nv.id_chuc_vu
        LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban";
$params = [];
if ($tuKhoa !== '') {
    $sql .= " WHERE nv.ho_ten LIKE :kw OR nv.ma_nv LIKE :kw";
    $params[':kw'] = "%$tuKhoa%";
}
$sql .= " ORDER BY nv.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsNhanVien = $stmt->fetchAll();

function badgeTrangThaiNV($tt) {
    if ($tt === 'Chính thức') return '<span class="badge badge-success">Chính thức</span>';
    if ($tt === 'Thử việc')   return '<span class="badge badge-warning">Thử việc</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Nghỉ việc</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Danh sách nhân viên</h3>
                    <div style="display:flex; gap:10px;">
                        <form action="danhsachnhanvien.php" method="GET" style="display:flex; gap:8px;">
                            <input type="text" name="q" placeholder="Tìm theo mã hoặc tên..." value="<?= htmlspecialchars($tuKhoa) ?>"
                                   style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; outline:none;">
                            <button type="submit" class="btn-secondary" style="background:#3b82f6;border-radius:6px;padding:8px 14px;color:#fff;border:none;cursor:pointer;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>
                        <a href="themnhanvien.php" class="btn-primary" style="text-decoration: none; display: inline-block;">+ Thêm nhân viên mới</a>
                    </div>
                </div>

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã xoá nhân viên thành công.</p>
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã lưu thông tin nhân viên thành công.</p>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ và tên</th>
                            <th>Chức vụ</th>
                            <th>Phòng ban</th>
                            <th>Số điện thoại</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
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
                            <td><?= htmlspecialchars($nv['ten_phong_ban'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($nv['sdt'] ?? '--') ?></td>
                            <td><?= badgeTrangThaiNV($nv['trang_thai_lam_viec']) ?></td>
                            <td>
                                <a href="themnhanvien.php?id=<?= $nv['id'] ?>" style="color: #3b82f6; margin-right: 10px;" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="danhsachnhanvien.php?xoa=<?= $nv['id'] ?>" style="color: #ef4444;" title="Xoá"
                                   onclick="return confirm('Bạn có chắc muốn xoá nhân viên <?= htmlspecialchars(addslashes($nv['ho_ten'])) ?>?');">
                                   <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
