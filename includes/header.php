<?php
/**
 * HEADER + SIDEBAR DÙNG CHUNG
 * $pageTitle : tiêu đề tab trình duyệt (đặt trước khi include file này)
 * Tự động highlight menu đang active theo tên file hiện tại.
 */
$currentFile = basename($_SERVER['PHP_SELF']);

// Nhóm menu con -> để biết submenu nào cần "open" theo trang hiện tại
$menuGroups = [
    'nhanvien' => ['danhsachnhanvien.php', 'themnhanvien.php', 'hopdong.php', 'taikhoan.php'],
    'chamcong' => ['bangchamcong.php', 'lichsuravao.php'],
    'luong'    => ['bangtinhluongthang.php', 'cauhinhluong.php'],
    'phep'     => ['duyetdonphep.php', 'quyphepnam.php'],
];
function isOpen($group, $file, $groups) {
    return in_array($file, $groups[$group]) ? ' open' : '';
}
function isActive($file, $current) {
    return $file === $current ? ' active' : '';
}

// Số đơn phép chờ duyệt -> hiện lên chuông thông báo
$soDonChoDuyet = 0;
try {
    $soDonChoDuyet = (int)$pdo->query("SELECT COUNT(*) FROM don_nghi_phep WHERE trang_thai='Chờ duyệt'")->fetchColumn();
} catch (Exception $e) { /* im lặng nếu bảng chưa có dữ liệu */ }
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
        <a href="index.php" class="area-logo">
            <span class="logo-icon">❖</span> NovaHM
        </a>

        <!-- 2. HEADER -->
        <header class="area-header">
            <div class="header-title">
                <h2>HỆ THỐNG QUẢN LÝ NHÂN SỰ</h2>
            </div>
            <div class="user-profile">
                <i class="fa-regular fa-bell icon-btn" title="<?= $soDonChoDuyet ?> đơn phép chờ duyệt"></i>
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
                <li class="menu-item<?= isActive('index.php', $currentFile) ?>">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> <span>Tổng quan</span></a>
                </li>

                <li class="menu-item has-submenu<?= isOpen('nhanvien', $currentFile, $menuGroups) ?>">
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

                <li class="menu-item has-submenu<?= isOpen('chamcong', $currentFile, $menuGroups) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calendar-check"></i><span>Chấm công</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangchamcong.php', $currentFile)) ?>"><a href="bangchamcong.php">Bảng chấm công ngày</a></li>
                        <li class="<?= trim(isActive('lichsuravao.php', $currentFile)) ?>"><a href="lichsuravao.php">Lịch sử ra/vào</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpen('luong', $currentFile, $menuGroups) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-calculator"></i><span>Tính lương cơ bản</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('bangtinhluongthang.php', $currentFile)) ?>"><a href="bangtinhluongthang.php">Bảng tính lương tháng</a></li>
                        <li class="<?= trim(isActive('cauhinhluong.php', $currentFile)) ?>"><a href="cauhinhluong.php">Cấu hình lương</a></li>
                    </ul>
                </li>

                <li class="menu-item has-submenu<?= isOpen('phep', $currentFile, $menuGroups) ?>">
                    <a href="javascript:void(0)" class="menu-toggle">
                        <i class="fa-solid fa-mug-hot"></i><span>Quản lý ngày phép</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="<?= trim(isActive('duyetdonphep.php', $currentFile)) ?>"><a href="duyetdonphep.php">Duyệt đơn nghỉ phép</a></li>
                        <li class="<?= trim(isActive('quyphepnam.php', $currentFile)) ?>"><a href="quyphepnam.php">Quỹ phép năm</a></li>
                    </ul>
                </li>

                <li class="menu-item border-top">
                    <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> <span>Đăng xuất</span></a>
                </li>
            </ul>
        </aside>

        <!-- 4. MAIN -->
        <main class="area-main">
