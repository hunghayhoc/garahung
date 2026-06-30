<?php
// pages/lich_hen.php – Đặt lịch hẹn xem xe
session_start();
require_once '../includes/auth_check.php';
requireLogin('../pages/login.php');
if(empty($_SESSION['user_id'])){header("Location: login.php");exit;}
require_once '../config/db.php';

$page_title='Lịch hẹn xem xe – GaraHung';
require_once '../includes/header.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$id_xe_pre=(int)($_GET['id_xe']??0);
$id_kh=(int)$_SESSION['user_id'];

// Danh sách lịch hẹn đã đặt
$lich_list=$db->prepare("SELECT l.*,x.ten_xe,x.ma_xe,n.ho_ten AS ten_nv FROM lich_hen l LEFT JOIN xe_oto x ON x.id=l.id_xe LEFT JOIN nhan_vien n ON n.id=l.id_nhan_vien WHERE l.id_khach_hang=? ORDER BY l.ngay_gio DESC LIMIT 20");
$lich_list->execute([$id_kh]);$lich_list=$lich_list->fetchAll();

// Danh sách xe còn hàng
$xe_list=$db->query("SELECT x.id,x.ten_xe,h.ten_hang FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang WHERE x.trang_thai='còn hàng' ORDER BY x.ten_xe")->fetchAll();
$tt_map=['chờ xác nhận'=>'badge-yellow','đã xác nhận'=>'badge-blue','hoàn thành'=>'badge-green','hủy'=>'badge-red'];
?>
<div class="container">
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:24px;align-items:start;">
<!-- Danh sách lịch hẹn -->
<div>
<div class="page-header"><h1>📅 Lịch hẹn xem xe</h1></div>
<?php if(empty($lich_list)):?>
<div class="card"><div class="card-body" style="text-align:center;padding:40px;">
    <div style="font-size:3rem;margin-bottom:12px;">📅</div>
    <h3 style="color:var(--primary);margin-bottom:8px;">Chưa có lịch hẹn nào</h3>
    <p style="color:var(--text-light);">Đặt lịch xem xe trực tiếp tại showroom!</p>
</div></div>
<?php else: foreach($lich_list as $l):?>
<div class="card" style="margin-bottom:14px;">
<div class="card-body" style="padding:16px 20px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
        <div>
            <strong style="color:var(--primary);"><?=htmlspecialchars($l['ten_xe']??'—')?></strong>
            <div style="font-size:.85rem;color:var(--text-light);margin-top:3px;">📅 <?=date('d/m/Y H:i',strtotime($l['ngay_gio']))?></div>
            <div style="font-size:.85rem;color:var(--text-light);">📍 <?=htmlspecialchars($l['dia_diem']??'Showroom GaraHung')?></div>
            <?php if($l['ten_nv']):?><div style="font-size:.85rem;color:var(--text-light);">👔 NV: <?=htmlspecialchars($l['ten_nv'])?></div><?php endif;?>
        </div>
        <span class="badge <?=$tt_map[$l['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($l['trang_thai'])?></span>
    </div>
</div>
</div>
<?php endforeach; endif;?>
</div>

<!-- Form đặt lịch -->
<div class="card">
<div class="card-header"><h2>➕ Đặt lịch mới</h2></div>
<div class="card-body">
<form method="POST" action="../api/lich_hen.php?action=create">
    <div class="form-group"><label class="lbl">Xe muốn xem <span class="req">*</span></label>
        <select name="id_xe" required>
            <option value="">-- Chọn xe --</option>
            <?php foreach($xe_list as $xe):?>
            <option value="<?=$xe['id']?>" <?=$id_xe_pre===$xe['id']?'selected':''?>><?=htmlspecialchars($xe['ten_hang'].' – '.$xe['ten_xe'])?></option>
            <?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl">Ngày & giờ hẹn <span class="req">*</span></label>
        <input type="datetime-local" name="ngay_gio" min="<?=date('Y-m-d\TH:i')?>" required></div>
    <div class="form-group"><label class="lbl">Địa điểm</label>
        <input type="text" name="dia_diem" value="Showroom GaraHung – 123 Đường Lê Văn Việt, TP.HCM" placeholder="Địa điểm gặp mặt"></div>
    <div class="form-group"><label class="lbl">Ghi chú</label><textarea name="ghi_chu" placeholder="Yêu cầu hoặc câu hỏi muốn tư vấn..."></textarea></div>
    <button type="submit" class="btn btn-primary btn-full">📅 Đặt lịch hẹn</button>
</form>
</div>
</div>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
