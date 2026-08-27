<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauVaiTro('TRUONGPHONG', 'ADMIN');
$pageTitle = 'Đánh giá KPI - NovaHM';

$laAdmin = laVaiTro('ADMIN');
$laTruongPhong = laVaiTro('TRUONGPHONG');
$phongBanQL = $currentUser['phong_ban_quan_ly'];

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nv = (int)($_POST['id_nv'] ?? 0);
    $ky    = trim($_POST['ky_danh_gia'] ?? '');
    $diem  = $_POST['diem_kpi'] !== '' ? (float)$_POST['diem_kpi'] : null;
    $nx    = trim($_POST['nhan_xet'] ?? '');

    $nvHopLe = $laAdmin;
    if ($laTruongPhong && $id_nv) {
        $ph = empty($phongBanQL) ? [0] : $phongBanQL;
        $inClause = implode(',', array_fill(0, count($ph), '?'));
        $s = $pdo->prepare("SELECT COUNT(*) FROM nhan_vien WHERE id = ? AND id_phong_ban IN ($inClause)");
        $s->execute(array_merge([$id_nv], $ph));
        $nvHopLe = (int)$s->fetchColumn() > 0;
    }

    if (!$id_nv || $ky === '') {
        $loi = 'Vui lòng chọn nhân viên và nhập kỳ đánh giá (vd: 2026-Q3).';
    } elseif (!$nvHopLe) {
        $loi = 'Bạn chỉ được đánh giá KPI cho nhân viên thuộc phòng ban mình quản lý.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO danh_gia_kpi (id_nv, id_nguoi_danh_gia, ky_danh_gia, diem_kpi, nhan_xet)
            VALUES (:nv, :nd, :ky, :diem, :nx)
            ON DUPLICATE KEY UPDATE diem_kpi = :diem2, nhan_xet = :nx2, id_nguoi_danh_gia = :nd2
        ");
        $stmt->execute([
            ':nv' => $id_nv, ':nd' => $currentUser['id'], ':ky' => $ky, ':diem' => $diem, ':nx' => $nx,
            ':diem2' => $diem, ':nx2' => $nx, ':nd2' => $currentUser['id'],
        ]);
        ghiNhatKy($pdo, 'UPDATE', 'danh_gia_kpi', $id_nv, "Đánh giá KPI kỳ $ky");
        header('Location: kpi.php?msg=saved');
        exit;
    }
}

// Danh sách nhân viên trong phạm vi được đánh giá
if ($laTruongPhong) {
    if (empty($phongBanQL)) {
        $dsNhanVienChon = [];
    } else {
        $ph = implode(',', array_fill(0, count($phongBanQL), '?'));
        $s = $pdo->prepare("SELECT id, ma_nv, ho_ten FROM nhan_vien WHERE id_phong_ban IN ($ph) ORDER BY ho_ten");
        $s->execute($phongBanQL);
        $dsNhanVienChon = $s->fetchAll();
    }
} else {
    $dsNhanVienChon = $pdo->query("SELECT id, ma_nv, ho_ten FROM nhan_vien ORDER BY ho_ten")->fetchAll();
}

// Lịch sử đánh giá trong phạm vi
$sql = "SELECT kpi.ky_danh_gia, kpi.diem_kpi, kpi.nhan_xet, kpi.ngay_tao,
               nv.ma_nv, nv.ho_ten, pb.ten_phong_ban
        FROM danh_gia_kpi kpi
        JOIN nhan_vien nv ON nv.id = kpi.id_nv
        LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban";
$params = [];
if ($laTruongPhong) {
    if (empty($phongBanQL)) {
        $sql .= " WHERE 1=0";
    } else {
        $phKeys = [];
        foreach ($phongBanQL as $i => $pbId) { $key = ":pb$i"; $phKeys[] = $key; $params[$key] = $pbId; }
        $sql .= " WHERE nv.id_phong_ban IN (" . implode(',', $phKeys) . ")";
    }
}
$sql .= " ORDER BY kpi.ngay_tao DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsKpi = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Đánh giá hiệu suất (KPI)<?= $laTruongPhong ? ' — phòng ban của tôi' : '' ?></h3>
                    <button class="btn-primary" onclick="document.getElementById('formKpi').style.display='block'"><i class="fa-solid fa-plus"></i> Chấm KPI</button>
                </div>

                <?php if ($laTruongPhong && empty($phongBanQL)): ?>
                    <p style="background:#fef3c7;color:#d97706;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Tài khoản của bạn chưa được gán làm Trưởng phòng của phòng ban nào.</p>
                <?php endif; ?>
                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã lưu đánh giá KPI.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formKpi" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="kpi.php" method="POST" style="display:grid; grid-template-columns: repeat(4,1fr); gap:14px; align-items:end;">
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
                            <label style="font-size:13px;font-weight:600;color:#334155;">Kỳ đánh giá</label><br>
                            <input type="text" name="ky_danh_gia" placeholder="2026-Q3" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Điểm KPI (0-100)</label><br>
                            <input type="number" step="0.1" min="0" max="100" name="diem_kpi" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <button type="submit" class="btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
                        </div>
                        <div style="grid-column: span 4;">
                            <label style="font-size:13px;font-weight:600;color:#334155;">Nhận xét</label><br>
                            <input type="text" name="nhan_xet" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                    </form>
                    <p style="font-size:12px;color:#94a3b8;margin-top:8px;">Nếu nhân viên đã có đánh giá cùng kỳ, lưu lại sẽ ghi đè điểm/nhận xét cũ.</p>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Phòng ban</th><th>Kỳ đánh giá</th><th>Điểm KPI</th><th>Nhận xét</th><th>Ngày đánh giá</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsKpi)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#94a3b8;">Chưa có đánh giá KPI nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsKpi as $k): ?>
                        <tr>
                            <td><?= htmlspecialchars($k['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($k['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($k['ten_phong_ban'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($k['ky_danh_gia']) ?></td>
                            <td><strong><?= $k['diem_kpi'] !== null ? rtrim(rtrim($k['diem_kpi'],'0'),'.') : '--' ?></strong></td>
                            <td><?= htmlspecialchars($k['nhan_xet'] ?? '') ?></td>
                            <td><?= date('d/m/Y', strtotime($k['ngay_tao'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
