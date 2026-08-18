<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Hợp đồng lao động - NovaHM';

// Xoá hợp đồng
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $pdo->prepare("DELETE FROM hop_dong_lao_dong WHERE id = :id")->execute([':id' => $id]);
    ghiNhatKy($pdo, 'DELETE', 'hop_dong_lao_dong', $id, 'Xoá hợp đồng');
    header('Location: hopdong.php?msg=deleted');
    exit;
}

// Thêm hợp đồng mới
$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma_hd    = trim($_POST['ma_hd'] ?? '');
    $id_nv    = (int)($_POST['id_nv'] ?? 0);
    $loai     = trim($_POST['loai_hop_dong'] ?? '');
    $batDau   = $_POST['ngay_bat_dau'] ?? '';
    $ketThuc  = trim($_POST['ngay_ket_thuc'] ?? '') !== '' ? $_POST['ngay_ket_thuc'] : null;

    if ($ma_hd === '' || !$id_nv || $loai === '' || $batDau === '') {
        $loi = 'Vui lòng điền đầy đủ thông tin hợp đồng.';
    } else {
        try {
            $trangThai = 'Còn hiệu lực';
            if ($ketThuc) {
                $con = (strtotime($ketThuc) - time()) / 86400;
                if ($con < 0) $trangThai = 'Hết hạn';
                elseif ($con <= 30) $trangThai = 'Sắp hết hạn';
            }
            $stmt = $pdo->prepare("INSERT INTO hop_dong_lao_dong (ma_hd, id_nv, loai_hop_dong, ngay_bat_dau, ngay_ket_thuc, trang_thai)
                                    VALUES (:ma_hd, :id_nv, :loai, :bd, :kt, :tt)");
            $stmt->execute([
                ':ma_hd' => $ma_hd, ':id_nv' => $id_nv, ':loai' => $loai,
                ':bd' => $batDau, ':kt' => $ketThuc, ':tt' => $trangThai,
            ]);
            ghiNhatKy($pdo, 'CREATE', 'hop_dong_lao_dong', $pdo->lastInsertId(), 'Tạo hợp đồng mới');
            header('Location: hopdong.php?msg=saved');
            exit;
        } catch (PDOException $e) {
            $loi = str_contains($e->getMessage(), 'Duplicate') ? 'Mã hợp đồng này đã tồn tại.' : 'Lỗi khi lưu: ' . $e->getMessage();
        }
    }
}

$dsHopDong = $pdo->query("
    SELECT hd.id, hd.ma_hd, hd.loai_hop_dong, hd.ngay_bat_dau, hd.ngay_ket_thuc, hd.trang_thai,
           nv.ma_nv, nv.ho_ten
    FROM hop_dong_lao_dong hd
    JOIN nhan_vien nv ON nv.id = hd.id_nv
    ORDER BY hd.id DESC
")->fetchAll();

$dsNhanVienChon = $pdo->query("SELECT id, ma_nv, ho_ten FROM nhan_vien ORDER BY ho_ten")->fetchAll();

function badgeHopDong($tt) {
    $map = ['Còn hiệu lực' => 'badge-success', 'Sắp hết hạn' => 'badge-warning'];
    if (isset($map[$tt])) return '<span class="badge '.$map[$tt].'">'.htmlspecialchars($tt).'</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">'.htmlspecialchars($tt).'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Quản lý hợp đồng lao động</h3>
                    <button class="btn-primary" onclick="document.getElementById('formHopDong').style.display='block'">+ Tạo hợp đồng mới</button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?= $_GET['msg'] === 'deleted' ? 'Đã xoá hợp đồng.' : 'Đã lưu hợp đồng mới.' ?>
                    </p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formHopDong" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="hopdong.php" method="POST" style="display:grid; grid-template-columns: repeat(3,1fr); gap:14px; align-items:end;">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Mã hợp đồng</label><br>
                            <input type="text" name="ma_hd" placeholder="HD-2026-004" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
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
                            <label style="font-size:13px;font-weight:600;color:#334155;">Loại hợp đồng</label><br>
                            <input type="text" name="loai_hop_dong" placeholder="Thử việc (2 tháng)" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Ngày bắt đầu</label><br>
                            <input type="date" name="ngay_bat_dau" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Ngày hết hạn (để trống nếu vô thời hạn)</label><br>
                            <input type="date" name="ngay_ket_thuc" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <button type="submit" class="btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Lưu hợp đồng</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã HĐ</th><th>Mã NV</th><th>Họ và tên</th><th>Loại hợp đồng</th>
                            <th>Ngày bắt đầu</th><th>Ngày hết hạn</th><th>Trạng thái</th><th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsHopDong)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#94a3b8;">Chưa có hợp đồng nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsHopDong as $hd): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($hd['ma_hd']) ?></strong></td>
                            <td><?= htmlspecialchars($hd['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($hd['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($hd['loai_hop_dong']) ?></td>
                            <td><?= date('d/m/Y', strtotime($hd['ngay_bat_dau'])) ?></td>
                            <td><?= $hd['ngay_ket_thuc'] ? date('d/m/Y', strtotime($hd['ngay_ket_thuc'])) : '--' ?></td>
                            <td><?= badgeHopDong($hd['trang_thai']) ?></td>
                            <td>
                                <a href="hopdong.php?xoa=<?= $hd['id'] ?>" style="color:#ef4444;" title="Xoá"
                                   onclick="return confirm('Xoá hợp đồng <?= htmlspecialchars(addslashes($hd['ma_hd'])) ?>?');">
                                   <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
