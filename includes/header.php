<?php
/**
 * HEADER + SIDEBAR DÙNG CHUNG
 * $pageTitle : tiêu đề tab trình duyệt (đặt trước khi include file này)
 * Menu hiển thị khác nhau tuỳ 5 vai trò: ADMIN, HR, KETOAN, TRUONGPHONG, NHANVIEN.
 */
$currentFile = basename($_SERVER['PHP_SELF']);

function isActive($file, $current) {
    return $file === $current ? ' active' : '';
}
function isOpenIf($dsFile, $current) {
    return in_array($current, $dsFile, true) ? ' open' : '';
}

// Số đơn (phép + đơn từ khác) chờ duyệt -> hiện lên chuông thông báo, phạm vi tuỳ vai trò
$soDonChoDuyet = 0;
try {
    if (laVaiTro('ADMIN')) {
        $soDonChoDuyet = (int)$pdo->query("SELECT
            (SELECT COUNT(*) FROM don_nghi_phep WHERE trang_thai='Chờ duyệt') +
            (SELECT COUNT(*) FROM don_tu_khac WHERE trang_thai='Chờ duyệt')
        ")->fetchColumn();
    } elseif (laVaiTro('TRUONGPHONG') && !empty($currentUser['phong_ban_quan_ly'])) {
        $ph = implode(',', array_fill(0, count($currentUser['phong_ban_quan_ly']), '?'));
        $s = $pdo->prepare("SELECT COUNT(*) FROM don_nghi_phep dnp JOIN nhan_vien nv ON nv.id=dnp.id_nv
                             WHERE dnp.trang_thai='Chờ duyệt' AND nv.id_phong_ban IN ($ph)");
        $s->execute($currentUser['phong_ban_quan_ly']);
        $soDonChoDuyet = (int)$s->fetchColumn();
    } elseif (!$currentUser['la_admin'] && !empty($currentUser['id_nv'])) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM don_nghi_phep WHERE trang_thai='Chờ duyệt' AND id_nv = :nv");
        $s->execute([':nv' => $currentUser['id_nv']]);
        $soDonChoDuyet = (int)$s->fetchColumn();
    }
} catch (Exception $e) { /* im lặng nếu bảng chưa có dữ liệu (chưa chạy patch_phanquyen.sql) */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'NovaHM') ?></title>
    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (!empty($extraStyle)) echo $extraStyle; ?>
</head>
<body>

    <div class="layout-container">
        <!-- 1. LOGO -->
        <a href="<?= $currentUser['la_admin'] ? 'index.php' : 'trangcanhan.php' ?>" class="area-logo">
            <span class="logo-icon">❖</span> NovaHM
        </a>

        <!-- 2. HEADER -->
        <header class="area-header">
            <div class="header-title">
                <h2>HỆ THỐNG QUẢN LÝ NHÂN SỰ</h2>
            </div>
            <div class="user-profile">
                <i class="fa-regular fa-bell icon-btn" title="<?= $soDonChoDuyet ?> đơn chờ duyệt"></i>
                <?php if ($soDonChoDuyet > 0): ?>
                    <span class="badge" style="background:#fef2f2;color:#ef4444;margin-left:-10px;"><?= $soDonChoDuyet ?></span>
                <?php endif; ?>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['ten']) ?>&background=3b82f6&color=fff" alt="Avatar" class="avatar">
                <span class="user-name"><?= htmlspecialchars($currentUser['ten']) ?></span>
            </div>
        </header>

        <!-- 3. MENU -->
        <aside class="area-left">
            <ul class="menu-list">

            <?php if (laVaiTro('ADMIN')): ?>
                <!-- ================= MENU: ADMIN (toàn quyền) ================= -->
                <li class="menu-item<?= isActive('index.php', $currentFile) ?>">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> <span>Tổng quan</span></a>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['danhsachnhanvien.php','themnhanvien.php','hopdong.php','taikhoan.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-users"></i><span>Hồ sơ nhân viên</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('danhsachnhanvien.php', $currentFile)) ?>"><a href="danhsachnhanvien.php">Danh sách nhân viên</a></li>
                        <li class="<?= trim(isActive('themnhanvien.php', $currentFile)) ?>"><a href="themnhanvien.php">Thêm nhân viên mới</a></li>
                        <li class="<?= trim(isActive('hopdong.php', $currentFile)) ?>"><a href="hopdong.php">Hợp đồng lao động</a></li>
                        <li class="<?= trim(isActive('taikhoan.php', $currentFile)) ?>"><a href="taikhoan.php">Quản lý tài khoản</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['bangchamcong.php','lichsuravao.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calendar-check"></i><span>Chấm công</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangchamcong.php', $currentFile)) ?>"><a href="bangchamcong.php">Bảng chấm công ngày</a></li>
                        <li class="<?= trim(isActive('lichsuravao.php', $currentFile)) ?>"><a href="lichsuravao.php">Lịch sử ra/vào</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['bangtinhluongthang.php','cauhinhluong.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calculator"></i><span>Tính lương</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangtinhluongthang.php', $currentFile)) ?>"><a href="bangtinhluongthang.php">Bảng tính lương tháng</a></li>
                        <li class="<?= trim(isActive('cauhinhluong.php', $currentFile)) ?>"><a href="cauhinhluong.php">Cấu hình lương</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['duyetdonphep.php','quyphepnam.php','duyetdontukhac.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-mug-hot"></i><span>Ngày phép & Đơn từ</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('duyetdonphep.php', $currentFile)) ?>"><a href="duyetdonphep.php">Duyệt đơn nghỉ phép</a></li>
                        <li class="<?= trim(isActive('duyetdontukhac.php', $currentFile)) ?>"><a href="duyetdontukhac.php">Duyệt đơn từ khác</a></li>
                        <li class="<?= trim(isActive('quyphepnam.php', $currentFile)) ?>"><a href="quyphepnam.php">Quỹ phép năm</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['nhanvien_phongban.php','kpi.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-chart-line"></i><span>Phòng ban & KPI</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('nhanvien_phongban.php', $currentFile)) ?>"><a href="nhanvien_phongban.php">Xem theo phòng ban</a></li>
                        <li class="<?= trim(isActive('kpi.php', $currentFile)) ?>"><a href="kpi.php">Đánh giá KPI</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpenIf(['phongban.php','chucvu.php','cauhinhhethong.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-gears"></i><span>Danh mục & Cấu hình</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('phongban.php', $currentFile)) ?>"><a href="phongban.php">Quản lý phòng ban</a></li>
                        <li class="<?= trim(isActive('chucvu.php', $currentFile)) ?>"><a href="chucvu.php">Quản lý chức vụ</a></li>
                        <li class="<?= trim(isActive('cauhinhhethong.php', $currentFile)) ?>"><a href="cauhinhhethong.php">Cấu hình hệ thống</a></li>
                    </ul>
                </li>

            <?php elseif (laVaiTro('HR')): ?>
                <!-- ================= MENU: Nhân viên Hành chính - Nhân sự tổng hợp ================= -->
                <li class="menu-item<?= isActive('index.php', $currentFile) ?>">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> <span>Tổng quan</span></a>
                </li>
                <li class="menu-item has-submenu<?= isOpenIf(['danhsachnhanvien.php','themnhanvien.php','hopdong.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-users"></i><span>Hồ sơ nhân viên</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('danhsachnhanvien.php', $currentFile)) ?>"><a href="danhsachnhanvien.php">Danh sách nhân viên</a></li>
                        <li class="<?= trim(isActive('themnhanvien.php', $currentFile)) ?>"><a href="themnhanvien.php">Thêm nhân viên mới</a></li>
                        <li class="<?= trim(isActive('hopdong.php', $currentFile)) ?>"><a href="hopdong.php">Hợp đồng lao động</a></li>
                    </ul>
                </li>
                <li class="menu-item has-submenu<?= isOpenIf(['bangchamcong.php','lichsuravao.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calendar-check"></i><span>Chấm công</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangchamcong.php', $currentFile)) ?>"><a href="bangchamcong.php">Bảng chấm công ngày</a></li>
                        <li class="<?= trim(isActive('lichsuravao.php', $currentFile)) ?>"><a href="lichsuravao.php">Lịch sử ra/vào</a></li>
                    </ul>
                </li>

            <?php elseif (laVaiTro('KETOAN')): ?>
                <!-- ================= MENU: Kế toán ================= -->
                <li class="menu-item<?= isActive('index.php', $currentFile) ?>">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> <span>Tổng quan</span></a>
                </li>
                <li class="menu-item<?= isActive('bangchamcong.php', $currentFile) ?>">
                    <a href="bangchamcong.php"><i class="fa-solid fa-calendar-check"></i> <span>Chấm công (xem)</span></a>
                </li>
                <li class="menu-item has-submenu<?= isOpenIf(['bangtinhluongthang.php','cauhinhluong.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calculator"></i><span>Tính lương</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangtinhluongthang.php', $currentFile)) ?>"><a href="bangtinhluongthang.php">Bảng tính lương tháng</a></li>
                        <li class="<?= trim(isActive('cauhinhluong.php', $currentFile)) ?>"><a href="cauhinhluong.php">Cấu hình lương (BH, phụ cấp)</a></li>
                    </ul>
                </li>

            <?php elseif (laVaiTro('TRUONGPHONG')): ?>
                <!-- ================= MENU: Trưởng phòng ================= -->
                <li class="menu-item<?= isActive('index.php', $currentFile) ?>">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> <span>Tổng quan</span></a>
                </li>
                <li class="menu-item<?= isActive('nhanvien_phongban.php', $currentFile) ?>">
                    <a href="nhanvien_phongban.php"><i class="fa-solid fa-users"></i> <span>Nhân viên phòng tôi</span></a>
                </li>
                <li class="menu-item has-submenu<?= isOpenIf(['duyetdonphep.php','duyetdontukhac.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-file-signature"></i><span>Duyệt đơn từ</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('duyetdonphep.php', $currentFile)) ?>"><a href="duyetdonphep.php">Đơn nghỉ phép</a></li>
                        <li class="<?= trim(isActive('duyetdontukhac.php', $currentFile)) ?>"><a href="duyetdontukhac.php">Đi muộn / Tăng ca</a></li>
                    </ul>
                </li>
                <li class="menu-item<?= isActive('quyphepnam.php', $currentFile) ?>">
                    <a href="quyphepnam.php"><i class="fa-solid fa-calendar-days"></i> <span>Quỹ phép phòng tôi</span></a>
                </li>
                <li class="menu-item<?= isActive('kpi.php', $currentFile) ?>">
                    <a href="kpi.php"><i class="fa-solid fa-chart-line"></i> <span>Đánh giá KPI</span></a>
                </li>

            <?php else: ?>
                <!-- ================= MENU: Nhân viên (self-service) ================= -->
                <li class="menu-item<?= isActive('trangcanhan.php', $currentFile) ?>">
                    <a href="trangcanhan.php"><i class="fa-solid fa-house"></i> <span>Trang cá nhân</span></a>
                </li>
                <li class="menu-item<?= isActive('hoso_canhan.php', $currentFile) ?>">
                    <a href="hoso_canhan.php"><i class="fa-solid fa-id-card"></i> <span>Hồ sơ của tôi</span></a>
                </li>
                <li class="menu-item<?= isActive('chamcong_canhan.php', $currentFile) ?>">
                    <a href="chamcong_canhan.php"><i class="fa-solid fa-calendar-check"></i> <span>Chấm công của tôi</span></a>
                </li>
                <li class="menu-item<?= isActive('luong_canhan.php', $currentFile) ?>">
                    <a href="luong_canhan.php"><i class="fa-solid fa-sack-dollar"></i> <span>Lương của tôi</span></a>
                </li>
                <li class="menu-item has-submenu<?= isOpenIf(['phepnam_canhan.php','dontukhac_canhan.php'], $currentFile) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-mug-hot"></i><span>Đơn từ của tôi</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('phepnam_canhan.php', $currentFile)) ?>"><a href="phepnam_canhan.php">Nghỉ phép</a></li>
                        <li class="<?= trim(isActive('dontukhac_canhan.php', $currentFile)) ?>"><a href="dontukhac_canhan.php">Đi muộn / Tăng ca</a></li>
                    </ul>
                </li>
                <li class="menu-item<?= isActive('kpi_canhan.php', $currentFile) ?>">
                    <a href="kpi_canhan.php"><i class="fa-solid fa-chart-line"></i> <span>KPI của tôi</span></a>
                </li>
            <?php endif; ?>

                <li class="menu-item border-top">
                    <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> <span>Đăng xuất</span></a>
                </li>
            </ul>
        </aside>

        <!-- 4. MAIN -->
        <main class="area-main">
