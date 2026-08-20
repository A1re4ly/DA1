<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Duyệt đơn nghỉ phép - NovaHM';

// Duyệt / Từ chối đơn
if (isset($_GET['duyet']) || isset($_GET['tuchoi'])) {
    $id = (int)($_GET['duyet'] ?? $_GET['tuchoi']);
    $trangThaiMoi = isset($_GET['duyet']) ? 'Đã duyệt' : 'Từ chối';
    $pdo->prepare("UPDATE don_nghi_phep SET trang_thai=:tt, id_nguoi_duyet=:u, ngay_duyet=NOW() WHERE id=:id")
        ->execute([':tt' => $trangThaiMoi, ':u' => $currentUser['id'], ':id' => $id]);
    ghiNhatKy($pdo, 'APPROVE', 'don_nghi_phep', $id, $trangThaiMoi);
    header('Location: duyetdonphep.php?msg=' . ($trangThaiMoi === 'Đã duyệt' ? 'approved' : 'rejected'));
    exit;
}

// Tạo đơn nghỉ phép mới
$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nv   = (int)($_POST['id_nv'] ?? 0);
    $id_loai = (int)($_POST['id_loai_phep'] ?? 0);
    $tuNgay  = $_POST['tu_ngay'] ?? '';
    $denNgay = $_POST['den_ngay'] ?? '';
    $lyDo    = trim($_POST['ly_do'] ?? '');

    if (!$id_nv || !$id_loai || !$tuNgay || !$denNgay) {
        $loi = 'Vui lòng điền đầy đủ thông tin đơn nghỉ phép.';
    } else {
        $soNgay = (strtotime($denNgay) - strtotime($tuNgay)) / 86400 + 1;
        $stmt = $pdo->prepare("INSERT INTO don_nghi_phep (id_nv, id_loai_phep, tu_ngay, den_ngay, so_ngay, ly_do, trang_thai)
                                VALUES (:nv, :loai, :tu, :den, :sn, :ld, 'Chờ duyệt')");
        $stmt->execute([':nv' => $id_nv, ':loai' => $id_loai, ':tu' => $tuNgay, ':den' => $denNgay, ':sn' => $soNgay, ':ld' => $lyDo]);
        ghiNhatKy($pdo, 'CREATE', 'don_nghi_phep', $pdo->lastInsertId(), 'Tạo đơn nghỉ phép mới');
        header('Location: duyetdonphep.php?msg=created');
        exit;
    }
}

$locTrangThai = $_GET['trang_thai'] ?? 'Chờ duyệt';
$sql = "SELECT dnp.id, dnp.tu_ngay, dnp.den_ngay, dnp.so_ngay, dnp.ly_do, dnp.trang_thai,
               nv.ma_nv, nv.ho_ten, lp.ten_loai_phep
        FROM don_nghi_phep dnp
        JOIN nhan_vien nv ON nv.id = dnp.id_nv
        JOIN loai_phep lp ON lp.id = dnp.id_loai_phep";
$params = [];
if ($locTrangThai !== 'all') {
    $sql .= " WHERE dnp.trang_thai = :tt";
    $params[':tt'] = $locTrangThai;
}
$sql .= " ORDER BY dnp.ngay_tao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsDon = $stmt->fetchAll();

$dsNhanVienChon = $pdo->query("SELECT id, ma_nv, ho_ten FROM nhan_vien ORDER BY ho_ten")->fetchAll();
$dsLoaiPhep = $pdo->query("SELECT id, ten_loai_phep FROM loai_phep ORDER BY id")->fetchAll();

function badgeDonPhep($tt) {
    if ($tt === 'Đã duyệt') return '<span class="badge badge-success">Đã duyệt</span>';
    if ($tt === 'Chờ duyệt') return '<span class="badge badge-warning">Chờ duyệt</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Từ chối</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Duyệt đơn nghỉ phép</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form action="duyetdonphep.php" method="GET">
                            <select name="trang_thai" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                                <option value="all" <?= $locTrangThai==='all'?'selected':'' ?>>Tất cả trạng thái</option>
                                <option value="Chờ duyệt" <?= $locTrangThai==='Chờ duyệt'?'selected':'' ?>>Chờ duyệt</option>
                                <option value="Đã duyệt" <?= $locTrangThai==='Đã duyệt'?'selected':'' ?>>Đã duyệt</option>
                                <option value="Từ chối" <?= $locTrangThai==='Từ chối'?'selected':'' ?>>Từ chối</option>
                            </select>
                        </form>
                        <button class="btn-primary" onclick="document.getElementById('formDon').style.display='block'"><i class="fa-solid fa-plus"></i> Tạo đơn nghỉ phép</button>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?php
                        $mmap = ['approved'=>'Đã duyệt đơn nghỉ phép.', 'rejected'=>'Đã từ chối đơn nghỉ phép.', 'created'=>'Đã tạo đơn nghỉ phép mới.'];
                        echo $mmap[$_GET['msg']] ?? '';
                        ?>
                    </p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formDon" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="duyetdonphep.php" method="POST" style="display:grid; grid-template-columns: repeat(3,1fr); gap:14px; align-items:end;">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Nhân viên</label><br>
                            <select name="id_nv" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach ($dsNhanVienChon as $nv): ?>
                                <option value="<?= $nv['id'] ?>"><?= htmlspecialchars($nv['ma_nv'].' - '.$nv['ho_ten']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Loại phép</label><br>
                            <select name="id_loai_phep" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <?php foreach ($dsLoaiPhep as $lp): ?>
                                <option value="<?= $lp['id'] ?>"><?= htmlspecialchars($lp['ten_loai_phep']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Lý do</label><br>
                            <input type="text" name="ly_do" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
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
                            <button type="submit" class="btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Gửi đơn</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Loại phép</th><th>Từ ngày</th><th>Đến ngày</th><th>Số ngày</th><th>Lý do</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsDon)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#94a3b8;">Không có đơn nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsDon as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($d['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($d['ten_loai_phep']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['tu_ngay'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['den_ngay'])) ?></td>
                            <td><?= rtrim(rtrim($d['so_ngay'],'0'),'.') ?></td>
                            <td><?= htmlspecialchars($d['ly_do']) ?></td>
                            <td><?= badgeDonPhep($d['trang_thai']) ?></td>
                            <td>
                                <?php if ($d['trang_thai'] === 'Chờ duyệt'): ?>
                                <a href="duyetdonphep.php?duyet=<?= $d['id'] ?>" style="color: #10b981; margin-right: 10px; font-size: 1.1rem;" title="Duyệt"><i class="fa-solid fa-circle-check"></i></a>
                                <a href="duyetdonphep.php?tuchoi=<?= $d['id'] ?>" style="color: #ef4444; font-size: 1.1rem;" title="Từ chối"><i class="fa-solid fa-circle-xmark"></i></a>
                                <?php else: ?>
                                <span style="color: #3b82f6;" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
