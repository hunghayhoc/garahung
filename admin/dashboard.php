<?php
// admin/dashboard.php
$page_title = 'Dashboard – GaraHung Admin';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

// Lấy stats
$tong_don    = $db->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
$dt_thang    = $db->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai='hoàn thành' AND MONTH(ngay_dat_hang)=MONTH(CURDATE()) AND YEAR(ngay_dat_hang)=YEAR(CURDATE())")->fetchColumn();
$xe_con_hang = $db->query("SELECT COUNT(*) FROM xe_oto WHERE trang_thai='còn hàng'")->fetchColumn();
$tong_kh     = $db->query("SELECT COUNT(*) FROM khach_hang")->fetchColumn();
$cho_xn      = $db->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='chờ xác nhận'")->fetchColumn();
$lich_cho    = $db->query("SELECT COUNT(*) FROM lich_hen WHERE trang_thai='chờ xác nhận'")->fetchColumn();

// 10 đơn hàng gần nhất
$don_recent  = $db->query("SELECT d.*,k.ho_ten,k.so_dien_thoai FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang ORDER BY d.ngay_dat_hang DESC LIMIT 10")->fetchAll();
// 5 lịch hẹn chờ
$lich_recent = $db->query("SELECT l.*,k.ho_ten,k.so_dien_thoai,x.ten_xe FROM lich_hen l LEFT JOIN khach_hang k ON k.id=l.id_khach_hang LEFT JOIN xe_oto x ON x.id=l.id_xe WHERE l.trang_thai='chờ xác nhận' ORDER BY l.ngay_gio ASC LIMIT 5")->fetchAll();
// Doanh thu 6 tháng
$doanh_thu_6 = $db->query("SELECT DATE_FORMAT(ngay_dat_hang,'%m/%Y') AS thang, COALESCE(SUM(tong_tien),0) AS dt FROM don_hang WHERE trang_thai='hoàn thành' AND ngay_dat_hang>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(ngay_dat_hang,'%Y-%m') ORDER BY thang")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-layout" style="flex-direction:row;">
<?php // sidebar already echoed above ?>
<div class="admin-content" style="margin-left:240px;">

<div class="page-header">
    <h1>📊 Dashboard Tổng Quan</h1>
    <span style="font-size:.88rem;color:var(--text-light);">Cập nhật: <?= date('d/m/Y H:i') ?></span>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">🛒</div>
        <div class="stat-info">
            <div class="label">Tổng đơn hàng</div>
            <div class="value"><?= number_format($tong_don) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <div class="label">Doanh thu tháng này</div>
            <div class="value"><?= number_format($dt_thang/1000000) ?>M ₫</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🚗</div>
        <div class="stat-info">
            <div class="label">Xe còn hàng</div>
            <div class="value"><?= number_format($xe_con_hang) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <div class="label">Khách hàng</div>
            <div class="value"><?= number_format($tong_kh) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⏳</div>
        <div class="stat-info">
            <div class="label">Đơn chờ xác nhận</div>
            <div class="value"><?= $cho_xn ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📅</div>
        <div class="stat-info">
            <div class="label">Lịch hẹn chờ duyệt</div>
            <div class="value"><?= $lich_cho ?></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;">

<!-- Đơn hàng gần nhất -->
<div class="card">
    <div class="card-header">
        <h2>🛒 Đơn hàng gần nhất</h2>
        <a href="don_hang.php" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,.5);color:#fff;">Xem tất cả</a>
    </div>
    <div class="card-body p0 table-wrap">
        <table>
            <thead><tr>
                <th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày</th>
            </tr></thead>
            <tbody>
            <?php foreach ($don_recent as $d): ?>
            <tr>
                <td><strong><?= htmlspecialchars($d['ma_don_hang']) ?></strong></td>
                <td><?= htmlspecialchars($d['ho_ten']) ?></td>
                <td style="font-weight:700;color:var(--accent);"><?= number_format($d['tong_tien']) ?> ₫</td>
                <td><?php
                    $map=['chờ xác nhận'=>'yellow','đã xác nhận'=>'blue','đang giao'=>'purple','hoàn thành'=>'green','hủy'=>'red'];
                    $cls=$map[$d['trang_thai']]??'gray';
                    echo "<span class='badge badge-$cls'>".htmlspecialchars($d['trang_thai'])."</span>";
                ?></td>
                <td style="font-size:.82rem;"><?= date('d/m H:i',strtotime($d['ngay_dat_hang'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lịch hẹn chờ -->
<div class="card">
    <div class="card-header">
        <h2>📅 Lịch hẹn chờ duyệt</h2>
        <a href="lich_hen.php" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,.5);color:#fff;">Xem tất cả</a>
    </div>
    <div class="card-body">
        <?php if (empty($lich_recent)): ?>
            <div class="alert alert-info">Không có lịch hẹn nào đang chờ.</div>
        <?php else: ?>
        <?php foreach ($lich_recent as $l): ?>
        <div style="border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:10px;">
            <div style="font-weight:700;"><?= htmlspecialchars($l['ho_ten']) ?></div>
            <div style="font-size:.85rem;color:var(--text-light);margin:2px 0;">🚗 <?= htmlspecialchars($l['ten_xe']) ?></div>
            <div style="font-size:.85rem;">📅 <?= date('d/m/Y H:i',strtotime($l['ngay_gio'])) ?></div>
            <div style="margin-top:8px;display:flex;gap:8px;">
                <form method="POST" action="../api/lich_hen.php?action=update_status" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                    <input type="hidden" name="trang_thai" value="đã xác nhận">
                    <button class="btn btn-success btn-sm">✓ Xác nhận</button>
                </form>
                <form method="POST" action="../api/lich_hen.php?action=update_status" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                    <input type="hidden" name="trang_thai" value="hủy">
                    <button class="btn btn-danger btn-sm">✗ Hủy</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
</div>
</body></html>
