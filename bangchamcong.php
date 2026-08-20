<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Bảng chấm công ngày - NovaHM';

$ngayChon = $_GET['ngay'] ?? date('Y-m-d');

// Chấm công thủ công (thêm/sửa giờ vào-ra cho 1 nhân viên trong ngày)
$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nv  = (int)($_POST['id_nv'] ?? 0);
    $ngay   = $_POST['ngay'] ?? $ngayChon;
    $gioVao = trim($_POST['gio_vao'] ?? '') !== '' ? $_POST['gio_vao'] : null;
    $gioRa  = trim($_POST['gio_ra'] ?? '') !== '' ? $_POST['gio_ra'] : null;

    if (!$id_nv) {
        $loi = 'Vui lòng chọn nhân viên.';
    } else {
        $soGio = ($gioVao && $gioRa) ? round((strtotime($gioRa) - strtotime($gioVao)) / 3600, 2) : 0;
        $soPhutTre = 0;
        $trangThai = 'Vắng mặt';
        if ($gioVao) {
            $trangThai = $gioVao > '08:00:00' ? 'Đi muộn' : 'Đúng giờ';
            if ($gioVao > '08:00:00') {
                $soPhutTre = round((strtotime($gioVao) - strtotime('08:00:00')) / 60);
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO bang_cham_cong_ngay (id_nv, ngay, gio_vao, gio_ra, so_gio_lam, so_phut_tre, trang_thai)
            VALUES (:nv, :ngay, :vao, :ra, :gio, :tre, :tt)
            ON DUPLICATE KEY UPDATE gio_vao=:vao2, gio_ra=:ra2, so_gio_lam=:gio2, so_phut_tre=:tre2, trang_thai=:tt2
        ");
        $stmt->execute([
            ':nv' => $id_nv, ':ngay' => $ngay, ':vao' => $gioVao, ':ra' => $gioRa,
            ':gio' => $soGio, ':tre' => $soPhutTre, ':tt' => $trangThai,
            ':vao2' => $gioVao, ':ra2' => $gioRa, ':gio2' => $soGio, ':tre2' => $soPhutTre, ':tt2' => $trangThai,
        ]);
        ghiNhatKy($pdo, 'UPDATE', 'bang_cham_cong_ngay', $id_nv, "Chấm công thủ công ngày $ngay");
        header("Location: bangchamcong.php?ngay=$ngay&msg=saved");
        exit;
    }
}

// Danh sách chấm công trong ngày (LEFT JOIN để nhân viên chưa chấm công vẫn hiện "Vắng mặt")
$stmt = $pdo->prepare("
    SELECT nv.id AS id_nv, nv.ma_nv, nv.ho_ten, pb.ten_phong_ban,
           bc.gio_vao, bc.gio_ra, bc.trang_thai
    FROM nhan_vien nv
    LEFT JOIN phong_ban pb ON pb.id = nv.id_phong_ban
    LEFT JOIN bang_cham_cong_ngay bc ON bc.id_nv = nv.id AND bc.ngay = :ngay
    WHERE nv.trang_thai_lam_viec <> 'Nghỉ việc'
    ORDER BY nv.ma_nv
");
$stmt->execute([':ngay' => $ngayChon]);
$dsChamCong = $stmt->fetchAll();

$dsNhanVienChon = $pdo->query("SELECT id, ma_nv, ho_ten FROM nhan_vien ORDER BY ho_ten")->fetchAll();

function badgeChamCong($tt) {
    $map = ['Đúng giờ' => 'badge-success', 'Đi muộn' => 'badge-warning', 'Về sớm' => 'badge-warning', 'Nghỉ phép' => 'badge-warning'];
    if (isset($map[$tt])) return '<span class="badge '.$map[$tt].'">'.htmlspecialchars($tt).'</span>';
    return '<span class="badge" style="background-color:#fef2f2;color:#ef4444;">'.htmlspecialchars($tt ?: 'Vắng mặt').'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Bảng chấm công ngày</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form action="bangchamcong.php" method="GET" style="display:flex; gap:8px;">
                            <input type="date" name="ngay" value="<?= htmlspecialchars($ngayChon) ?>"
                                   onchange="this.form.submit()"
                                   style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </form>
                        <button class="btn-primary" onclick="document.getElementById('formCC').style.display='block'">
                            <i class="fa-solid fa-fingerprint"></i> Chấm công thủ công
                        </button>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">Đã lưu chấm công.</p>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($loi) ?></p>
                <?php endif; ?>

                <div id="formCC" style="display:<?= $loi ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                    <form action="bangchamcong.php" method="POST" style="display:grid; grid-template-columns: repeat(4,1fr); gap:14px; align-items:end;">
                        <input type="hidden" name="ngay" value="<?= htmlspecialchars($ngayChon) ?>">
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
                            <label style="font-size:13px;font-weight:600;color:#334155;">Giờ vào</label><br>
                            <input type="time" name="gio_vao" step="1" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:#334155;">Giờ ra</label><br>
                            <input type="time" name="gio_ra" step="1" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                        </div>
                        <div>
                            <button type="submit" class="btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Phòng ban</th><th>Giờ vào</th><th>Giờ ra</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsChamCong as $cc): ?>
                        <tr>
                            <td><?= htmlspecialchars($cc['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($cc['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($cc['ten_phong_ban'] ?? '--') ?></td>
                            <td><?= $cc['gio_vao'] ?: '--:--:--' ?></td>
                            <td><?= $cc['gio_ra'] ?: '--:--:--' ?></td>
                            <td><?= badgeChamCong($cc['trang_thai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
