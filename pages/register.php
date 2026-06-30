<?php
// pages/register.php
session_start();

if (!empty($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
// Giữ lại dữ liệu cũ khi lỗi (qua query string)
$old = [
    'ho_ten'        => htmlspecialchars($_GET['ho_ten']        ?? ''),
    'email'         => htmlspecialchars($_GET['email']         ?? ''),
    'so_dien_thoai' => htmlspecialchars($_GET['so_dien_thoai'] ?? ''),
    'dia_chi'       => htmlspecialchars($_GET['dia_chi']       ?? ''),
    'ngay_sinh'     => htmlspecialchars($_GET['ngay_sinh']     ?? ''),
    'gioi_tinh'     => htmlspecialchars($_GET['gioi_tinh']     ?? ''),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký – Web Bán Xe Ô Tô</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 36px 36px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        h1 { text-align: center; color: #0f3460; margin-bottom: 4px; font-size: 1.5rem; }
        .subtitle { text-align: center; color: #888; margin-bottom: 24px; font-size: .88rem; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: .9rem; }
        .alert-error { background: #fee; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        label { display: block; font-size: .82rem; color: #555; margin-bottom: 5px; font-weight: 600; }
        input, select, textarea {
            width: 100%; padding: 9px 12px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: .92rem; transition: border .2s;
            font-family: inherit;
        }
        textarea { resize: vertical; min-height: 60px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #0f3460; }
        .required { color: #e74c3c; }
        .btn {
            width: 100%; padding: 12px; background: #0f3460;
            color: #fff; border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background .2s; margin-top: 6px;
        }
        .btn:hover { background: #16213e; }
        .footer-link { text-align: center; margin-top: 16px; font-size: .88rem; color: #777; }
        .footer-link a { color: #0f3460; text-decoration: none; font-weight: 600; }
        .password-hint { font-size: .75rem; color: #999; margin-top: 4px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🚗 Đăng ký tài khoản</h1>
    <p class="subtitle">Tạo tài khoản khách hàng mới</p>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" action="../api/khach_hang.php?action=register">
        <div class="row">
            <div class="form-group full">
                <label>Họ và tên <span class="required">*</span></label>
                <input type="text" name="ho_ten" value="<?= $old['ho_ten'] ?>"
                       placeholder="Nguyễn Văn A" required>
            </div>

            <div class="form-group full">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" value="<?= $old['email'] ?>"
                       placeholder="example@gmail.com" required>
            </div>

            <div class="form-group">
                <label>Mật khẩu <span class="required">*</span></label>
                <input type="password" name="mat_khau" placeholder="Tối thiểu 6 ký tự" required>
                <p class="password-hint">Ít nhất 6 ký tự</p>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu <span class="required">*</span></label>
                <input type="password" name="xac_nhan_mk" placeholder="Nhập lại mật khẩu" required>
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="so_dien_thoai" value="<?= $old['so_dien_thoai'] ?>"
                       placeholder="0912345678">
            </div>

            <div class="form-group">
                <label>Ngày sinh</label>
                <input type="date" name="ngay_sinh" value="<?= $old['ngay_sinh'] ?>">
            </div>

            <div class="form-group">
                <label>Giới tính</label>
                <select name="gioi_tinh">
                    <option value="">-- Chọn --</option>
                    <option value="Nam"  <?= $old['gioi_tinh'] === 'Nam'  ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ"   <?= $old['gioi_tinh'] === 'Nữ'   ? 'selected' : '' ?>>Nữ</option>
                    <option value="Khác" <?= $old['gioi_tinh'] === 'Khác' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Địa chỉ</label>
                <textarea name="dia_chi" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành"><?= $old['dia_chi'] ?></textarea>
            </div>
        </div>

        <button class="btn" type="submit">Đăng ký ngay</button>
    </form>

    <div class="footer-link">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </div>
</div>
</body>
</html>
