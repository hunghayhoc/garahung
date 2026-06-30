<?php
// pages/profile.php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';

requireLogin('../pages/login.php');

// Chỉ khách hàng mới xem profile này
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?error=Trang này dành cho khách hàng.");
    exit;
}

$id = (int)$_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM khach_hang WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$kh = $stmt->fetch();

if (!$kh) {
    session_destroy();
    header("Location: login.php?error=Tài khoản không tồn tại.");
    exit;
}

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân – <?= htmlspecialchars($kh['ho_ten']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6fb; color: #333; }
        .navbar {
            background: #0f3460; color: #fff;
            padding: 14px 30px; display: flex;
            align-items: center; justify-content: space-between;
        }
        .navbar .brand { font-size: 1.2rem; font-weight: 700; }
        .navbar .nav-right a {
            color: #fff; text-decoration: none;
            margin-left: 20px; font-size: .9rem;
        }
        .navbar .nav-right a:hover { text-decoration: underline; }
        .container { max-width: 860px; margin: 36px auto; padding: 0 16px; }
        .card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden; margin-bottom: 24px;
        }
        .card-header {
            background: linear-gradient(90deg, #0f3460, #16213e);
            color: #fff; padding: 20px 28px;
        }
        .card-header h2 { font-size: 1.2rem; }
        .card-body { padding: 28px; }
        .profile-info { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .info-item label { font-size: .8rem; color: #999; text-transform: uppercase; letter-spacing: .5px; }
        .info-item p { font-size: .98rem; font-weight: 600; color: #222; margin-top: 3px; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: .78rem; font-weight: 700;
        }
        .badge-active { background: #e8f5e9; color: #2e7d32; }
        .badge-locked { background: #fee; color: #c0392b; }
        .alert { padding: 11px 16px; border-radius: 7px; margin-bottom: 20px; font-size: .92rem; }
        .alert-error { background: #fee; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 24px; }
        .tab {
            padding: 10px 20px; cursor: pointer; font-weight: 600;
            color: #888; border-bottom: 3px solid transparent;
            margin-bottom: -2px; transition: all .2s;
        }
        .tab.active { color: #0f3460; border-bottom-color: #0f3460; }
        .panel { display: none; }
        .panel.active { display: block; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        label.field { display: block; font-size: .83rem; color: #555; margin-bottom: 5px; font-weight: 600; }
        input, select, textarea {
            width: 100%; padding: 9px 12px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: .93rem; font-family: inherit;
            transition: border .2s;
        }
        textarea { resize: vertical; min-height: 68px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #0f3460; }
        .divider { border: none; border-top: 1px dashed #e0e0e0; margin: 20px 0; }
        .btn-save {
            padding: 10px 28px; background: #0f3460; color: #fff;
            border: none; border-radius: 8px; font-size: .95rem;
            font-weight: 700; cursor: pointer; transition: background .2s;
        }
        .btn-save:hover { background: #16213e; }
        .points-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fff8e1; color: #f57f17; border: 1px solid #ffe082;
            padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: .95rem;
        }
    </style>
</head>
<body>
<div class="navbar">
    <span class="brand">🚗 Web Bán Xe Ô Tô</span>
    <div class="nav-right">
        <a href="index.php">Trang chủ</a>
<a href="xe.php">Xe ô tô</a>
<a href="lich_su_dh.php">Đơn hàng</a>
        <a href="../api/auth.php?action=logout">Đăng xuất (<?= htmlspecialchars($kh['ho_ten']) ?>)</a>
    </div>
</div>

<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= $msg ?></div>
    <?php endif; ?>

    <!-- Thông tin nhanh -->
    <div class="card">
        <div class="card-header">
            <h2>👤 Hồ sơ cá nhân</h2>
        </div>
        <div class="card-body">
            <div class="profile-info">
                <div class="info-item">
                    <label>Họ và tên</label>
                    <p><?= htmlspecialchars($kh['ho_ten']) ?></p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p><?= htmlspecialchars($kh['email']) ?></p>
                </div>
                <div class="info-item">
                    <label>Số điện thoại</label>
                    <p><?= htmlspecialchars($kh['so_dien_thoai'] ?? '—') ?></p>
                </div>
                <div class="info-item">
                    <label>Ngày sinh</label>
                    <p><?= $kh['ngay_sinh'] ? date('d/m/Y', strtotime($kh['ngay_sinh'])) : '—' ?></p>
                </div>
                <div class="info-item">
                    <label>Giới tính</label>
                    <p><?= htmlspecialchars($kh['gioi_tinh'] ?? '—') ?></p>
                </div>
                <div class="info-item">
                    <label>Trạng thái</label>
                    <p>
                        <span class="badge <?= $kh['trang_thai'] === 'hoạt động' ? 'badge-active' : 'badge-locked' ?>">
                            <?= htmlspecialchars($kh['trang_thai']) ?>
                        </span>
                    </p>
                </div>
                <div class="info-item full">
                    <label>Địa chỉ</label>
                    <p><?= htmlspecialchars($kh['dia_chi'] ?? '—') ?></p>
                </div>
                <div class="info-item">
                    <label>Điểm tích lũy</label>
                    <p><span class="points-badge">⭐ <?= number_format($kh['diem_tich_luy']) ?> điểm</span></p>
                </div>
                <div class="info-item">
                    <label>Ngày đăng ký</label>
                    <p><?= date('d/m/Y H:i', strtotime($kh['ngay_dang_ky'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form chỉnh sửa -->
    <div class="card">
        <div class="card-header">
            <h2>✏️ Cập nhật thông tin</h2>
        </div>
        <div class="card-body">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('info')">Thông tin cá nhân</div>
                <div class="tab" onclick="switchTab('pass')">Đổi mật khẩu</div>
            </div>

            <form method="POST" action="../api/khach_hang.php?action=update_profile">

                <!-- Tab thông tin -->
                <div id="panel-info" class="panel active">
                    <div class="row">
                        <div class="form-group">
                            <label class="field">Họ và tên *</label>
                            <input type="text" name="ho_ten"
                                   value="<?= htmlspecialchars($kh['ho_ten']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="field">Số điện thoại</label>
                            <input type="text" name="so_dien_thoai"
                                   value="<?= htmlspecialchars($kh['so_dien_thoai'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="field">Ngày sinh</label>
                            <input type="date" name="ngay_sinh"
                                   value="<?= htmlspecialchars($kh['ngay_sinh'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="field">Giới tính</label>
                            <select name="gioi_tinh">
                                <option value="">-- Chọn --</option>
                                <?php foreach (['Nam', 'Nữ', 'Khác'] as $gt): ?>
                                    <option value="<?= $gt ?>"
                                        <?= ($kh['gioi_tinh'] ?? '') === $gt ? 'selected' : '' ?>>
                                        <?= $gt ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="field">Địa chỉ</label>
                            <textarea name="dia_chi"><?= htmlspecialchars($kh['dia_chi'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tab đổi mật khẩu -->
                <div id="panel-pass" class="panel">
                    <div class="row">
                        <div class="form-group full">
                            <label class="field">Mật khẩu hiện tại</label>
                            <input type="password" name="mat_khau_cu" placeholder="Nhập mật khẩu hiện tại">
                        </div>
                        <div class="form-group">
                            <label class="field">Mật khẩu mới</label>
                            <input type="password" name="mat_khau_moi" placeholder="Tối thiểu 6 ký tự">
                        </div>
                        <div class="form-group">
                            <label class="field">Xác nhận mật khẩu mới</label>
                            <input type="password" name="xac_nhan_mk" placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                </div>

                <hr class="divider">
                <button class="btn-save" type="submit">💾 Lưu thay đổi</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach((t, i) => {
        t.classList.toggle('active', (i === 0 && tab === 'info') || (i === 1 && tab === 'pass'));
    });
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
}
</script>
</body>
</html>
