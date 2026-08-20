<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'Nghỉ phép của tôi - NovaHM';

$idNv = $currentUser['id_nv'];
$loi = '';

// Nhân viên tự tạo đơn nghỉ phép (chỉ cho chính mình)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_loai = (int)($_POST['id_loai_phep'] ?? 0);
    $tuNgay  = $_POST['tu_ngay'] ?? '';
    $denNgay = $_POST['den_ngay'] ?? '';
    $lyDo    = trim($_POST['ly_do'] ?? '');

    if (!$id_loai || !$tuNgay || !$denNgay) {
        $loi = 'Vui lòng điền đầy đủ thông tin đơn nghỉ phép.';
    } elseif (strtotime($denNgay) < strtotime($tuNgay)) {
        $loi = 'Ngày kết thúc phải sau ngày bắt đầu.';
    } else {
        $soNgay = (strtotime($denNgay) - strtotime($tuNgay)) / 86400 + 1;
        $stmt = $pdo->prepare("INSERT INTO don_nghi_phep (id_nv, id_loai_phep, tu_ngay, den_ngay, so_ngay, ly_do, trang_thai)
                                VALUES (:nv, :loai, :tu, :den, :sn, :ld, 'Chờ duyệt')");
        $stmt->execute([':nv' => $idNv, ':loai' => $id_loai, ':tu' => $tuNgay, ':den' => $denNgay, ':sn' => $soNgay, ':ld' => $lyDo]);
        ghiNhatKy($pdo, 'CREATE', 'don_nghi_phep', $pdo->lastInsertId(), 'Nhân viên tự tạo đơn nghỉ phép');
        header('Location: phepnam_canhan.php?msg=created');
        exit;
    }
}

$s = $pdo->prepare("SELECT * FROM quy_phep_nam WHERE id_nv = :nv AND nam = YEAR(CURDATE())");
$s->execute([':nv' => $idNv]);
$quyPhep = $s->fetch() ?: ['tong_phep' => 0, 'da_su_dung' => 0, 'con_lai' => 0];

$dsLoaiPhep = $pdo->query("SELECT id, ten_loai_phep FROM loai_phep ORDER BY id")->fetchAll();

$stmt = $pdo->prepare("
    SELECT dnp.*, lp.ten_loai_phep
    FROM don_nghi_phep dnp
    JOIN loai_phep lp ON lp.id = dnp.id_loai_phep
    WHERE dnp.id_nv = :nv
    ORDER BY dnp.ngay_tao DESC
");
$stmt->execute([':nv' => $idNv]);
$dsDon = $stmt->fetchAll();

function badgeDonPhepCN2($tt) {
    if ($tt === 'Đã duyệt') return '<span class="badge badge-success">Đã duyệt</span>';
    if ($tt === 'Chờ duyệt') return '<span class="badge badge-warning">Chờ duyệt</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Từ chối</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="stat-info"><h3><?= rtrim(rtrim($quyPhep['tong_phep'],'0'),'.') ?> ngày</h3><p>Tổng phép năm <?= date('Y') ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-calendar-minus"></i></div>
                    <div class="stat-info"><h3><?= rtrim(rtrim($quyPhep['da_su_dung'],'0'),'.') ?> ngày</h3><p>Đã sử dụng</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= rtrim(rtrim($quyPhep['con_lai'],'0'),'.') ?> ngày</h3><p>Còn lại</p></div>
                </div>
            </section>

            <section class="data-section">
                <div class="section-header">
                    <h3>Đơn nghỉ phép của tôi</h3>
                    <button class="btn-primary" onclick="document.getElementById('formDonCN').style.display='block'"><i class="fa-solid fa-plus"></i> Tạo đơn nghỉ phép</button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã gửi đơn nghỉ phép, chờ quản lý duyệt.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formDonCN" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="phepnam_canhan.php" method="POST" style="display:grid; grid-template-columns: repeat(4,1fr); gap:14px; align-items:end;">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Loại phép</label><br>
                            <select name="id_loai_phep" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <?php foreach ($dsLoaiPhep as $lp): ?>
                                <option value="<?= $lp['id'] ?>"><?= htmlspecialchars($lp['ten_loai_phep']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Từ ngày</label><br>
                            <input type="date" name="tu_ngay" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Đến ngày</label><br>
                            <input type="date" name="den_ngay" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Lý do</label><br>
                            <input type="text" name="ly_do" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div style="grid-column: span 4;">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> Gửi đơn</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Loại phép</th><th>Từ ngày</th><th>Đến ngày</th><th>Số ngày</th><th>Lý do</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsDon)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;">Bạn chưa gửi đơn nghỉ phép nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsDon as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['ten_loai_phep']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['tu_ngay'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['den_ngay'])) ?></td>
                            <td><?= rtrim(rtrim($d['so_ngay'],'0'),'.') ?></td>
                            <td><?= htmlspecialchars($d['ly_do']) ?></td>
                            <td><?= badgeDonPhepCN2($d['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
