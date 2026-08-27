<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauCoHoSoNhanVien();
$pageTitle = 'Đơn từ khác - NovaHM';

$idNv = $currentUser['id_nv'];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loaiDon = $_POST['loai_don'] ?? '';
    $ngay    = $_POST['ngay'] ?? '';
    $gioBD   = trim($_POST['gio_bat_dau'] ?? '') !== '' ? $_POST['gio_bat_dau'] : null;
    $gioKT   = trim($_POST['gio_ket_thuc'] ?? '') !== '' ? $_POST['gio_ket_thuc'] : null;
    $lyDo    = trim($_POST['ly_do'] ?? '');

    if (!in_array($loaiDon, ['Giải trình đi muộn', 'Đăng ký tăng ca'], true) || !$ngay) {
        $loi = 'Vui lòng chọn loại đơn và ngày.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO don_tu_khac (id_nv, loai_don, ngay, gio_bat_dau, gio_ket_thuc, ly_do, trang_thai)
                                VALUES (:nv, :loai, :ngay, :bd, :kt, :ld, 'Chờ duyệt')");
        $stmt->execute([':nv' => $idNv, ':loai' => $loaiDon, ':ngay' => $ngay, ':bd' => $gioBD, ':kt' => $gioKT, ':ld' => $lyDo]);
        ghiNhatKy($pdo, 'CREATE', 'don_tu_khac', $pdo->lastInsertId(), 'Nhân viên tạo đơn từ khác');
        header('Location: dontukhac_canhan.php?msg=created');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM don_tu_khac WHERE id_nv = :nv ORDER BY ngay_tao DESC");
$stmt->execute([':nv' => $idNv]);
$dsDon = $stmt->fetchAll();

function badgeDonTuKhac($tt) {
    if ($tt === 'Đã duyệt') return '<span class="badge badge-success">Đã duyệt</span>';
    if ($tt === 'Chờ duyệt') return '<span class="badge badge-warning">Chờ duyệt</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">Từ chối</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Đơn từ khác của tôi</h3>
                    <button class="btn-primary" onclick="document.getElementById('formDTK').style.display='block'"><i class="fa-solid fa-plus"></i> Tạo đơn mới</button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã gửi đơn, chờ Trưởng phòng duyệt.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formDTK" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="dontukhac_canhan.php" method="POST" style="display:grid; grid-template-columns: repeat(4,1fr); gap:14px; align-items:end;">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Loại đơn</label><br>
                            <select name="loai_don" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                                <option value="Giải trình đi muộn">Giải trình đi muộn</option>
                                <option value="Đăng ký tăng ca">Đăng ký tăng ca</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Ngày</label><br>
                            <input type="date" name="ngay" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Giờ bắt đầu</label><br>
                            <input type="time" name="gio_bat_dau" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Giờ kết thúc</label><br>
                            <input type="time" name="gio_ket_thuc" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div style="grid-column: span 4;">
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
                        <tr><th>Loại đơn</th><th>Ngày</th><th>Giờ</th><th>Lý do</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsDon)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;">Bạn chưa có đơn nào</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsDon as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['loai_don']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['ngay'])) ?></td>
                            <td><?= $d['gio_bat_dau'] ? substr($d['gio_bat_dau'],0,5).' - '.substr($d['gio_ket_thuc'],0,5) : '--' ?></td>
                            <td><?= htmlspecialchars($d['ly_do']) ?></td>
                            <td><?= badgeDonTuKhac($d['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
