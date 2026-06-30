<?php
// includes/admin_sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="menu-section">Tổng quan</div>
    <a href="../admin/dashboard.php" <?= $current==='dashboard.php'?'class="active"':'' ?>>📊 Dashboard</a>

    <div class="menu-section">Quản lý</div>
    <a href="../admin/khach_hang.php"  <?= $current==='khach_hang.php'?'class="active"':'' ?>>👥 Khách hàng</a>
    <a href="../admin/nhan_vien.php"   <?= $current==='nhan_vien.php'?'class="active"':'' ?>>👔 Nhân viên</a>

    <div class="menu-section">Xe & Danh mục</div>
    <a href="../admin/xe_oto.php"      <?= $current==='xe_oto.php'?'class="active"':'' ?>>🚗 Xe ô tô</a>
    <a href="../admin/hang_xe.php"     <?= $current==='hang_xe.php'?'class="active"':'' ?>>🏭 Hãng xe</a>
    <a href="../admin/danh_muc_xe.php" <?= $current==='danh_muc_xe.php'?'class="active"':'' ?>>📂 Danh mục xe</a>

    <div class="menu-section">Đơn hàng</div>
    <a href="../admin/don_hang.php"    <?= $current==='don_hang.php'?'class="active"':'' ?>>🛒 Đơn hàng</a>
    <a href="../admin/lich_hen.php"    <?= $current==='lich_hen.php'?'class="active"':'' ?>>📅 Lịch hẹn</a>

    <div class="menu-section">Marketing</div>
    <a href="../admin/khuyen_mai.php"  <?= $current==='khuyen_mai.php'?'class="active"':'' ?>>🎁 Khuyến mãi</a>
    <a href="../admin/binh_luan.php"   <?= $current==='binh_luan.php'?'class="active"':'' ?>>💬 Bình luận</a>

    <div class="menu-section" style="margin-top:24px;"></div>
    <a href="../pages/index.php">🌐 Xem trang web</a>
    <a href="../api/auth.php?action=logout" style="color:#e94560;">🚪 Đăng xuất</a>
</aside>
