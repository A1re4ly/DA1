<?php
require_once __DIR__ . '/includes/auth.php';
yeuCauQuanTri();  // chỉ Admin/HR/Kế toán/Trưởng phòng mới vào được
$pageTitle = 'Bảng tính lương tháng - NovaHM';

$thangNam = $_GET['thang'] ?? date('Y-m');
[$nam, $thang] = explode('-', $thangNam);

// ---- TÍNH LƯƠNG THÁNG (khi bấm nút "Tính lương tháng") ----
if (isset($_POST['tinh_luong'])) {
    $cfg = $pdo->query("SELECT * FROM cau_hinh_luong ORDER BY ngay_ap_dung DESC, id DESC LIMIT 1")->fetch();
    if (!$cfg) $cfg = ['so_ngay_cong_chuan'=>22,'luong_co_so'=>1800000,'phu_cap_an_trua'=>730000,'phu_cap_di_lai'=>500000,'ty_le_bhxh'=>8,'ty_le_bhyt'=>1.5,'ty_le_bhtn'=>1,'muc_phat_di_muon'=>10000];

    $nhanViens = $pdo->query("SELECT id FROM nhan_vien WHERE trang_thai_lam_viec <> 'Nghỉ việc'")->fetchAll();

    foreach ($nhanViens as $nvRow) {
        $idNv = $nvRow['id'];

        // Lương cơ bản: lấy theo hợp đồng còn hiệu lực gần nhất, nếu không có thì dùng lương cơ sở
        $s = $pdo->prepare("SELECT muc_luong_hd FROM hop_dong_lao_dong WHERE id_nv=:nv AND muc_luong_hd IS NOT NULL ORDER BY ngay_bat_dau DESC LIMIT 1");
        $s->execute([':nv' => $idNv]);
        $luongCoBan = $s->fetchColumn() ?: $cfg['luong_co_so'];

        // Ngày công thực tế + tổng phút trễ trong tháng
        $s = $pdo->prepare("SELECT COUNT(*) AS songay, COALESCE(SUM(so_phut_tre),0) AS tongtre
                             FROM bang_cham_cong_ngay
                             WHERE id_nv=:nv AND MONTH(ngay)=:t AND YEAR(ngay)=:n AND gio_vao IS NOT NULL");
        $s->execute([':nv' => $idNv, ':t' => $thang, ':n' => $nam]);
        $cc = $s->fetch();
        $ngayCongTT = (float)$cc['songay'];
        $tongPhutTre = (float)$cc['tongtre'];

        $ngayCongChuan = (float)$cfg['so_ngay_cong_chuan'];
        $luongTheoCong = $ngayCongChuan > 0 ? round($luongCoBan / $ngayCongChuan * $ngayCongTT) : 0;

        $tongPhuCap = $cfg['phu_cap_an_trua'] + $cfg['phu_cap_di_lai'];

        $bhxh = round($luongCoBan * $cfg['ty_le_bhxh'] / 100);
        $bhyt = round($luongCoBan * $cfg['ty_le_bhyt'] / 100);
        $bhtn = round($luongCoBan * $cfg['ty_le_bhtn'] / 100);
        $phatTre = round($tongPhutTre * $cfg['muc_phat_di_muon']);
        $tongKhauTru = $bhxh + $bhyt + $bhtn + $phatTre;

        $thucLinh = $luongTheoCong + $tongPhuCap - $tongKhauTru;

        $stmt = $pdo->prepare("
            INSERT INTO bang_luong_thang (id_nv, thang, nam, luong_co_ban, ngay_cong_thuc_te, ngay_cong_chuan, tong_phu_cap, tong_khau_tru, thuc_linh, trang_thai)
            VALUES (:nv, :t, :n, :lcb, :ngct, :ngcc, :pc, :kt, :tl, 'Chờ duyệt')
            ON DUPLICATE KEY UPDATE
              luong_co_ban=:lcb2, ngay_cong_thuc_te=:ngct2, ngay_cong_chuan=:ngcc2,
              tong_phu_cap=:pc2, tong_khau_tru=:kt2, thuc_linh=:tl2
        ");
        $stmt->execute([
            ':nv'=>$idNv, ':t'=>$thang, ':n'=>$nam, ':lcb'=>$luongCoBan, ':ngct'=>$ngayCongTT, ':ngcc'=>$ngayCongChuan,
            ':pc'=>$tongPhuCap, ':kt'=>$tongKhauTru, ':tl'=>$thucLinh,
            ':lcb2'=>$luongCoBan, ':ngct2'=>$ngayCongTT, ':ngcc2'=>$ngayCongChuan,
            ':pc2'=>$tongPhuCap, ':kt2'=>$tongKhauTru, ':tl2'=>$thucLinh,
        ]);
    }
    ghiNhatKy($pdo, 'CREATE', 'bang_luong_thang', null, "Tính lương tháng $thang/$nam");
    header("Location: bangtinhluongthang.php?thang=$thangNam&msg=calculated");
    exit;
}

// Duyệt phiếu lương
if (isset($_GET['duyet'])) {
    $id = (int)$_GET['duyet'];
    $pdo->prepare("UPDATE bang_luong_thang SET trang_thai='Đã duyệt', id_nguoi_duyet=:u, ngay_duyet=NOW() WHERE id=:id")
        ->execute([':u' => $currentUser['id'], ':id' => $id]);
    ghiNhatKy($pdo, 'APPROVE', 'bang_luong_thang', $id, 'Duyệt phiếu lương');
    header("Location: bangtinhluongthang.php?thang=$thangNam&msg=approved");
    exit;
}

$stmt = $pdo->prepare("
    SELECT bl.id, bl.luong_co_ban, bl.ngay_cong_thuc_te, bl.ngay_cong_chuan, bl.tong_phu_cap, bl.tong_khau_tru, bl.thuc_linh, bl.trang_thai,
           nv.ma_nv, nv.ho_ten
    FROM bang_luong_thang bl
    JOIN nhan_vien nv ON nv.id = bl.id_nv
    WHERE bl.thang = :t AND bl.nam = :n
    ORDER BY nv.ma_nv
");
$stmt->execute([':t' => $thang, ':n' => $nam]);
$dsLuong = $stmt->fetchAll();

function badgeLuong($tt) {
    $map = ['Đã duyệt' => 'badge-success', 'Chờ duyệt' => 'badge-warning', 'Đã thanh toán' => 'badge-success'];
    return '<span class="badge '.($map[$tt] ?? '').'">'.htmlspecialchars($tt).'</span>';
}

require __DIR__ . '/includes/header.php';
?>
            <section class="data-section">
                <div class="section-header">
                    <h3>Bảng tính lương tháng</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <form action="bangtinhluongthang.php" method="GET" style="display:flex;gap:8px;">
                            <input type="month" name="thang" value="<?= htmlspecialchars($thangNam) ?>" onchange="this.form.submit()"
                                   style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </form>
                        <form action="bangtinhluongthang.php?thang=<?= htmlspecialchars($thangNam) ?>" method="POST">
                            <button type="submit" name="tinh_luong" value="1" class="btn-primary"><i class="fa-solid fa-calculator"></i> Tính lương tháng</button>
                        </form>
                        <a href="bangtinhluongthang.php?thang=<?= htmlspecialchars($thangNam) ?>&xuat=excel" class="btn-primary" style="background-color:#10b981;text-decoration:none;"><i class="fa-solid fa-file-excel"></i> Xuất Excel</a>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <p style="background:#d1fae5;color:#059669;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                        <?= $_GET['msg'] === 'calculated' ? 'Đã tính lương cho tất cả nhân viên trong tháng.' : 'Đã duyệt phiếu lương.' ?>
                    </p>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr><th>Mã NV</th><th>Họ và tên</th><th>Lương cơ bản</th><th>Ngày công</th><th>Phụ cấp</th><th>Khấu trừ</th><th>Thực lĩnh</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsLuong)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#94a3b8;">Chưa có dữ liệu lương — hãy bấm "Tính lương tháng"</td></tr>
                        <?php endif; ?>
                        <?php foreach ($dsLuong as $bl): ?>
                        <tr>
                            <td><?= htmlspecialchars($bl['ma_nv']) ?></td>
                            <td><?= htmlspecialchars($bl['ho_ten']) ?></td>
                            <td><?= formatTien($bl['luong_co_ban']) ?></td>
                            <td><?= rtrim(rtrim($bl['ngay_cong_thuc_te'],'0'),'.') ?>/<?= rtrim(rtrim($bl['ngay_cong_chuan'],'0'),'.') ?></td>
                            <td><?= formatTien($bl['tong_phu_cap']) ?></td>
                            <td><?= formatTien($bl['tong_khau_tru']) ?></td>
                            <td><strong><?= formatTien($bl['thuc_linh']) ?></strong></td>
                            <td><?= badgeLuong($bl['trang_thai']) ?></td>
                            <td>
                                <?php if ($bl['trang_thai'] === 'Chờ duyệt'): ?>
                                <a href="bangtinhluongthang.php?thang=<?= htmlspecialchars($thangNam) ?>&duyet=<?= $bl['id'] ?>" style="color:#10b981;" title="Duyệt phiếu lương"><i class="fa-solid fa-circle-check"></i></a>
                                <?php else: ?>
                                <span style="color:#3b82f6;" title="Xem phiếu lương"><i class="fa-solid fa-eye"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
