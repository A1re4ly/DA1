<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Nếu đã đăng nhập rồi thì chuyển thẳng vào dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mk    = trim($_POST['password'] ?? '');

    if ($email === '' || $mk === '') {
        $loi = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT nd.id, nd.mat_khau_hash, nd.trang_thai, nd.id_nv,
                                       vt.ma_vai_tro, COALESCE(nv.ho_ten, 'Quản trị viên') AS ho_ten
                                FROM nguoi_dung nd
                                JOIN vai_tro vt ON vt.id = nd.id_vai_tro
                                LEFT JOIN nhan_vien nv ON nv.id = nd.id_nv
                                WHERE nd.email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $loi = 'Tài khoản hoặc mật khẩu không chính xác!';
        } elseif ($user['trang_thai'] !== 'Hoạt động') {
            $loi = 'Tài khoản của bạn đã bị khoá.';
        } elseif (!password_verify($mk, $user['mat_khau_hash'])) {
            $loi = 'Tài khoản hoặc mật khẩu không chính xác!';
        } else {
            // Đăng nhập thành công
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['ho_ten'];
            $_SESSION['user_role'] = $user['ma_vai_tro'];
            $_SESSION['id_nv']     = $user['id_nv'];

            $pdo->prepare("UPDATE nguoi_dung SET lan_dang_nhap_cuoi = NOW() WHERE id = :id")
                ->execute([':id' => $user['id']]);

            $vaiTroQuanTri = ['ADMIN', 'HR', 'KETOAN', 'TRUONGPHONG'];
            if (in_array($user['ma_vai_tro'], $vaiTroQuanTri, true)) {
                header('Location: index.php');
            } else {
                header('Location: trangcanhan.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - NovaHM</title>
    <link rel="stylesheet" href="asset/login.css">
</head>
<body>

    <div class="logo-top-left">
        <span class="logo-icon">❖</span> NovaHM
    </div>

    <div class="form-container">
        <p class="subtitle">Vui lòng điền thông tin đăng nhập</p>
        <h2>Welcome back</h2>

        <?php if ($loi): ?>
            <p style="background:#fef2f2;color:#ef4444;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <?= htmlspecialchars($loi) ?>
            </p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="name@company.vn"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Nhớ mật khẩu
                </label>
                <a href="#" class="forgot-password">Quên mật khẩu</a>
            </div>

            <button type="submit" class="btn-submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
