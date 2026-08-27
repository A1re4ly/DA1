<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuyen('PHEP_DUYET');  // dùng chung quyền duyệt đơn từ với đơn phép: Admin + Trưởng phòng
$pageTitle = 'Duyệt đơn từ khác - NovaHM';

$laTruongPhong = laVaiTro('TRUONGPHONG');
$phongBanQL = $currentUser['phong_ban_quan_ly'];

function dtkThuocPhongBanQuanLy(PDO $pdo, int $idDon, array $phongBanQL): bool {
    if (empty($phongBanQL)) return false;
    $ph = implode(',', array_fill(0, count($phongBanQL), '?'));
    $s = $pdo->prepare("SELECT COUNT(*) FROM don_tu_khac dtk JOIN nhan_vien nv ON nv.id = dtk.id_nv
                         WHERE dtk.id = ? AND nv.id_phong_ban IN ($ph)");
    $s->execute(array_merge([$idDon], $phongBanQL));
    return (int)$s->fetchColumn() > 0;
}

if (isset($_GET['duyet']) || isset($_GET['tuchoi'])) {
    $id = (int)($_GET['duyet'] ?? $_GET['tuchoi']);
    if ($laTruongPhong && !dtkThuocPhongBanQuanLy($pdo, $id, $phongBanQL)) {
        header('Location: duyetdontukhac.php?err=khongthuocphong');
        exit;
    }
    $trangThaiMoi = isset($_GET['duyet']) ? 'Đã duyệt' : 'Từ chối';
    $pdo->prepare("UPDATE don_tu_khac SET trang_thai=:tt, id_nguoi_duyet=:u, ngay_duyet=NOW() WHERE id=:id")
        ->execute([':tt' => $trangThaiMoi, ':u' => $currentUser['id'], ':id' => $id]);
    ghiNhatKy($pdo, 'APPROVE', 'don_tu_khac', $id, $trangThaiMoi);
    header('Location: duyetdontukhac.php?msg=' . ($trangThaiMoi === 'Đã duyệt' ? 'approved' : 'rejected'));
    exit;
}

$locTrangThai = $_GET['trang_thai'] ?? 'Chờ duyệt';
$sql = "SELECT dtk.id, dtk.loai_don, dtk.ngay, dtk.gio_bat_dau, dtk.gio_ket_thuc, dtk.ly_do, dtk.trang_thai,
               nv.ma_nv, nv.ho_ten, pb.ten_phong_ban
        FROM don_tu_khac dtk
        JOIN nhan_vien nv ON nv.id = dtk.id_nv
        LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
        WHERE 1=1";
$params = [];
if ($locTrangThai !== 'all') {
    $sql .= " AND dtk.trang_thai = :tt";
    $params[':tt'] = $locTrangThai;
}
if ($laTruongPhong) {
    if (empty($phongBanQL)) {
        $sql .= " AND 1=0";
    } else {
        $phKeys = [];
        foreach ($phongBanQL as $i => $pbId) { $key = ":pb$i"; $phKeys[] = $key; $params[$key] = $pbId; }
        $sql .= " AND nv.id_phong_ban IN (" . implode(',', $phKeys) . ")";
    }
}
$sql .= " ORDER BY dtk.ngay_tao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsDon = $stmt->fetchAll();

function badgeDonTuKhac2($tt) {
    if ($tt === 'Đã duyệt') return '<span class="badge badge-success">Đã duyệt</span>';
    if ($tt === 'Chờ duyệt') return '<span class="badge badge-warning">Chờ duyệt</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Từ chối</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Duyệt đơn từ khác<?= $laTruongPhong ? ' — phòng ban của tôi' : '' ?></h3>
                    <form action="duyetdontukhac.php" method="GET">
                        <select name="trang_thai" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                            <option value="all" <?= $locTrangThai==='all'?'selected':'' ?>>Tất cả trạng thái</option>
                            <option value="Chờ duyệt" <?= $locTrangThai==='Chờ duyệt'?'selected':'' ?>>Chờ duyệt</option>
                            <option value="Đã duyệt" <?= $locTrangThai==='Đã duyệt'?'selected':'' ?>>Đã duyệt</option>
                            <option value="Từ chối" <?= $locTrangThai==='Từ chối'?'selected':'' ?>>Từ chối</option>
                        </select>
                    </form>
                </div>

                <?php if ($laTruongPhong && empty($phongBanQL)): ?>
                    <p style="background:#fef3c7;color:#d97706;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Tài khoản của bạn chưa được gán làm Trưởng phòng của phòng ban nào.</p>
                <?php endif; ?>
                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?= $_GET['msg'] === 'approved' ? 'Đã duyệt đơn.' : 'Đã từ chối đơn.' ?>
                    </p>
                <?php endif; ?>
                <?php if (isset($_GET['err']) && $_GET['err'] === 'khongthuocphong'): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đơn này không thuộc phòng ban bạn quản lý.</p>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Phòng ban</th><th>Loại đơn</th><th>Ngày</th><th>Giờ</th><th>Lý do</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsDon)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#94a3b8;">Không có đơn nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsDon as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($d['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($d['ten_phong_ban'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($d['loai_don']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['ngay'])) ?></td>
                            <td><?= $d['gio_bat_dau'] ? substr($d['gio_bat_dau'],0,5).' - '.substr($d['gio_ket_thuc'],0,5) : '--' ?></td>
                            <td><?= htmlspecialchars($d['ly_do']) ?></td>
                            <td><?= badgeDonTuKhac2($d['trang_thai']) ?></td>
                            <td>
                                <?php if ($d['trang_thai'] === 'Chờ duyệt'): ?>
                                <a href="duyetdontukhac.php?duyet=<?= $d['id'] ?>" style="color: #10b981; margin-right: 10px; font-size: 1.1rem;" title="Duyệt"><i class="fa-solid fa-circle-check"></i></a>
                                <a href="duyetdontukhac.php?tuchoi=<?= $d['id'] ?>" style="color: #ef4444; font-size: 1.1rem;" title="Từ chối"><i class="fa-solid fa-circle-xmark"></i></a>
                                <?php else: ?>
                                <span style="color:#3b82f6;"><i class="fa-solid fa-eye"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
