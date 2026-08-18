<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Quỹ phép năm - NovaHM';

$namChon = $_GET['nam'] ?? date('Y');

// Cập nhật tổng phép năm cho 1 nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $tongPhepMoi = (float)($_POST['tong_phep'] ?? 0);
    if ($id) {
        $pdo->prepare("UPDATE quy_phep_nam SET tong_phep = :tp WHERE id = :id")
            ->execute([':tp' => $tongPhepMoi, ':id' => $id]);
        ghiNhatKy($pdo, 'UPDATE', 'quy_phep_nam', $id, 'Chỉnh sửa quỹ phép năm');
    }
    header("Location: quyphepnam.php?nam=$namChon&msg=saved");
    exit;
}

// Đảm bảo mỗi nhân viên đang làm việc đều có 1 dòng quỹ phép cho năm đang chọn
$nhanViens = $pdo->query("SELECT id FROM nhan_vien WHERE trang_thai_lam_viec <> 'Nghỉ việc'")->fetchAll();
foreach ($nhanViens as $nvRow) {
    $pdo->prepare("INSERT IGNORE INTO quy_phep_nam (id_nv, nam, tong_phep, da_su_dung) VALUES (:nv, :nam, 12, 0)")
        ->execute([':nv' => $nvRow['id'], ':nam' => $namChon]);
}

$stmt = $pdo->prepare("
    SELECT qp.id, qp.tong_phep, qp.da_su_dung, qp.con_lai,
           nv.ma_nv, nv.ho_ten, pb.ten_phong_ban
    FROM quy_phep_nam qp
    JOIN nhan_vien nv ON nv.id = qp.id_nv
    LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
    WHERE qp.nam = :nam
    ORDER BY nv.ma_nv
");
$stmt->execute([':nam' => $namChon]);
$dsQuyPhep = $stmt->fetchAll();

function trangThaiQuyPhep($conLai) {
    if ($conLai <= 0)  return ['Hết phép', '#ef4444', '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Hết phép</span>'];
    if ($conLai <= 4)  return ['Sắp hết', '#f59e0b', '<span class="badge badge-warning">Sắp hết</span>'];
    return ['Dồi dào', '#10b981', '<span class="badge badge-success">Dồi dào</span>'];
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Thống kê quỹ phép năm <?= htmlspecialchars($namChon) ?></h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form action="quyphepnam.php" method="GET">
                            <select name="nam" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                                <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                                <option value="<?= $y ?>" <?= (string)$namChon === (string)$y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                        <a href="quyphepnam.php?nam=<?= htmlspecialchars($namChon) ?>&xuat=excel" class="btn-primary" style="background-color: #10b981; text-decoration:none;"><i class="fa-solid fa-file-excel"></i> Xuất Báo Cáo</a>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã cập nhật quỹ phép.</p>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Phòng ban</th><th>Tổng phép năm</th><th>Đã sử dụng</th><th>Còn lại</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsQuyPhep as $qp): [$nhan, $mau, $badgeHtml] = trangThaiQuyPhep($qp['con_lai']); ?>
                        <tr>
                            <td><?= htmlspecialchars($qp['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($qp['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($qp['ten_phong_ban'] ?? '--') ?></td>
                            <td>
                                <form action="quyphepnam.php?nam=<?= htmlspecialchars($namChon) ?>" method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="id" value="<?= $qp['id'] ?>">
                                    <input type="number" step="0.5" name="tong_phep" value="<?= rtrim(rtrim($qp['tong_phep'],'0'),'.') ?>"
                                           style="width:60px;padding:4px 6px;border:1px solid #cbd5e1;border-radius:4px;"> ngày
                                    <button type="submit" style="border:none;background:none;color:#3b82f6;cursor:pointer;" title="Lưu"><i class="fa-solid fa-floppy-disk"></i></button>
                                </form>
                            </td>
                            <td><?= rtrim(rtrim($qp['da_su_dung'],'0'),'.') ?> ngày</td>
                            <td><strong style="color:<?= $mau ?>;"><?= rtrim(rtrim($qp['con_lai'],'0'),'.') ?> ngày</strong></td>
                            <td><?= $badgeHtml ?></td>
                            <td style="color:#94a3b8;font-size:12px;">Sửa số ở cột "Tổng phép năm"</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
