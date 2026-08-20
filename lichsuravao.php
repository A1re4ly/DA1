<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Lịch sử ra/vào - NovaHM';

$ngayChon = $_GET['ngay'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT ls.thoi_gian, ls.loai_su_kien, ls.trang_thai,
           nv.ma_nv, nv.ho_ten, tb.ten_thiet_bi
    FROM lich_su_ra_vao ls
    JOIN nhan_vien nv ON nv.id = ls.id_nv
    LEFT JOIN thiet_bi_cham_cong tb ON tb.id = ls.id_thiet_bi
    WHERE DATE(ls.thoi_gian) = :ngay
    ORDER BY ls.thoi_gian DESC
");
$stmt->execute([':ngay' => $ngayChon]);
$dsLichSu = $stmt->fetchAll();

function badgeLichSu($tt) {
    if ($tt === 'Hợp lệ') return '<span class="badge badge-success">Hợp lệ</span>';
    if ($tt === 'Muộn')   return '<span class="badge badge-warning">Muộn</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">'.htmlspecialchars($tt).'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Lịch sử quét thẻ / Vân tay</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form action="lichsuravao.php" method="GET">
                            <input type="date" name="ngay" value="<?= htmlspecialchars($ngayChon) ?>" onchange="this.form.submit()"
                                   style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </form>
                        <a href="lichsuravao.php?ngay=<?= urlencode($ngayChon) ?>&xuat=excel" class="btn-primary" style="background-color: #10b981; text-decoration:none;">
                            <i class="fa-solid fa-file-excel"></i> Xuất Excel
                        </a>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Thời gian ghi nhận</th><th>Mã NV</th><th>Họ và tên</th><th>Loại sự kiện</th><th>Thiết bị ghi nhận</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsLichSu)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Không có dữ liệu quét thẻ ngày này</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsLichSu as $ls): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i:s', strtotime($ls['thoi_gian'])) ?></td>
                            <td><?= htmlspecialchars($ls['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($ls['ho_ten']) ?></td>
                            <td>
                                <?php if ($ls['loai_su_kien'] === 'Check-in'): ?>
                                    <span style="color:#10b981;font-weight:600;"><i class="fa-solid fa-right-to-bracket"></i> Check-in</span>
                                <?php else: ?>
                                    <span style="color:#ef4444;font-weight:600;"><i class="fa-solid fa-right-from-bracket"></i> Check-out</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($ls['ten_thiet_bi'] ?? '--') ?></td>
                            <td><?= badgeLichSu($ls['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
