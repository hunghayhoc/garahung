<!-- Phong: thêm menu Khuyến mãi -->
<!-- Phong: thêm menu Khuyến mãi -->
=======


<!-- Hưng: thêm menu Xe ô tô -->
<!-- Trung: thêm menu Đăng nhập -->
<!-- Minh: thêm menu Khách hàng -->
<!-- Kiên: thêm menu Đơn hàng -->

>>>>>>> a8decabcadf9014fd38ecf23cb7129423ac49cb7
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$is_admin = !empty($_SESSION['nv_id']);
$is_kh    = !empty($_SESSION['user_id']);
$ho_ten   = $_SESSION['ho_ten'] ?? '';
$current  = basename($_SERVER['PHP_SELF']);

// Tự detect thư mục gốc - hoạt động cả localhost lẫn ngrok
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$script   = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$script   = str_replace($doc_root, '', $script);
$parts    = explode('/', trim($script, '/'));
$base = '/garahung/';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'TrungBeo – Showroom Ô Tô' ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <meta name="description" content="TrungBeo – Showroom ô tô uy tín, chất lượng, giá tốt nhất.">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="<?= $base ?>pages/index.php">🚗 TrungBeo</a>
    <nav>
        <a href="<?= $base ?>pages/index.php"      <?= $current==='index.php'?'class="active"':'' ?>>Trang chủ</a>
        <a href="<?= $base ?>pages/xe.php"         <?= $current==='xe.php'?'class="active"':'' ?>>Xe ô tô</a>
        <a href="<?= $base ?>pages/khuyen_mai.php" <?= $current==='khuyen_mai.php'?'class="active"':'' ?>>Khuyến mãi</a>
        <?php if ($is_admin): ?>
            <a href="<?= $base ?>admin/dashboard.php" class="btn-login">⚙️ Quản trị</a>
        <?php elseif ($is_kh): ?>
            <a href="<?= $base ?>pages/lich_su_dh.php" <?= $current==='lich_su_dh.php'?'class="active"':'' ?>>Đơn hàng</a>
            <a href="<?= $base ?>pages/lich_hen.php"   <?= $current==='lich_hen.php'?'class="active"':'' ?>>Lịch hẹn</a>
            <a href="<?= $base ?>pages/profile.php"    <?= $current==='profile.php'?'class="active"':'' ?>>👤 <?= htmlspecialchars($ho_ten) ?></a>
            <a href="<?= $base ?>api/auth.php?action=logout">Đăng xuất</a>
        <?php else: ?>
            <a href="<?= $base ?>pages/login.php" class="btn-login">Đăng nhập</a>
        <?php endif; ?>
    </nav>
</nav>
