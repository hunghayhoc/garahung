<?php
// pages/login.php
session_start();

// Nếu đã đăng nhập → redirect
if (!empty($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}
if (!empty($_SESSION['nv_id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
$redirect = htmlspecialchars($_GET['redirect'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập – Web Bán Xe Ô Tô</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        h1 { text-align: center; color: #0f3460; margin-bottom: 6px; font-size: 1.6rem; }
        .subtitle { text-align: center; color: #888; margin-bottom: 28px; font-size: .9rem; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: .9rem; }
        .alert-error { background: #fee; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .hint { text-align:center; color:#666; margin-bottom: 18px; font-size:.86rem; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: .85rem; color: #555; margin-bottom: 6px; font-weight: 600; }
        input, select {
            width: 100%; padding: 10px 12px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: .95rem; transition: border .2s;
        }
        input:focus, select:focus { outline: none; border-color: #0f3460; }
        .btn {
            width: 100%; padding: 12px; background: #0f3460;
            color: #fff; border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background .2s;
        }
        .btn:hover { background: #16213e; }
        .footer-link { text-align: center; margin-top: 18px; font-size: .88rem; color: #777; }
        .footer-link a { color: #0f3460; text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <h1>🚗 Xe Ô Tô</h1>
    <p class="subtitle">Đăng nhập vào hệ thống</p>
    <p class="hint">Tài khoản quản trị sẽ tự vào trang quản trị, tài khoản khách hàng sẽ vào trang chủ.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" action="../api/auth.php?action=login">
        <input type="hidden" name="redirect" value="<?= $redirect ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="email@example.com" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="mat_khau" placeholder="••••••••" required>
        </div>
        <button class="btn" type="submit">Đăng nhập</button>
    </form>

    <div class="footer-link">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </div>
</div>
</body>
</html>
<!-- Trung v1: tạo form đăng nhập -->
<!-- Trung v2: thêm validation -->
<!-- Trung v3: thêm remember me -->
