<?php
// pages/don_hang_ct.php – Chi tiết đơn hàng sau khi đặt
session_start();
require_once '../includes/auth_check.php';
requireLogin('../pages/login.php');
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$msg = htmlspecialchars($_GET['msg'] ?? '');
if($id<=0){header("Location: lich_su_dh.php");exit;}

$dh=$db->prepare("SELECT d.*,k.ho_ten,k.email,k.so_dien_thoai FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang WHERE d.id=? LIMIT 1");
$dh->execute([$id]);$dh=$dh->fetch();
if(!$dh||(int)$dh['id_khach_hang']!==(int)($_SESSION['user_id']??0)){header("Location: lich_su_dh.php?error=Không tìm thấy đơn hàng.");exit;}
$ct=$db->prepare("SELECT c.*,x.ten_xe,x.ma_xe,x.hinh_anh_chinh FROM chi_tiet_don_hang c LEFT JOIN xe_oto x ON x.id=c.id_xe WHERE c.id_don_hang=?");
$ct->execute([$id]);$ct=$ct->fetchAll();
$page_title='Chi tiết đơn hàng '.htmlspecialchars($dh['ma_don_hang']).' – GaraHung';
require_once '../includes/header.php';
$tt_map=['chờ xác nhận'=>'badge-yellow','đã xác nhận'=>'badge-blue','đang giao'=>'badge-purple','hoàn thành'=>'badge-green','hủy'=>'badge-red'];
?>
<div class="container" style="max-width:760px;">
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<div class="page-header">
    <h1>📋 Chi tiết đơn hàng</h1>
    <a href="lich_su_dh.php" class="btn btn-outline">← Lịch sử đơn hàng</a>
</div>

<div class="card">
<div class="card-header">
    <h2><?=htmlspecialchars($dh['ma_don_hang'])?></h2>
    <span class="badge <?=$tt_map[$dh['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($dh['trang_thai'])?></span>
</div>
<div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Khách hàng</label><p style="font-weight:700;"><?=htmlspecialchars($dh['ho_ten']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Email</label><p><?=htmlspecialchars($dh['email']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Thanh toán</label><p><?=htmlspecialchars($dh['phuong_thuc_thanh_toan']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Ngày đặt</label><p><?=date('d/m/Y H:i',strtotime($dh['ngay_dat_hang']))?></p></div>
        <?php if($dh['ghi_chu']):?><div style="grid-column:span 2;"><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Ghi chú</label><p><?=htmlspecialchars($dh['ghi_chu'])?></p></div><?php endif;?>
    </div>

    <h4 style="margin-bottom:12px;color:var(--primary);">🚗 Xe đặt mua</h4>
    <?php foreach($ct as $c):?>
    <div style="display:flex;gap:14px;align-items:center;padding:12px;background:#f7faff;border-radius:10px;margin-bottom:10px;">
        <div style="width:80px;height:60px;background:#eee;border-radius:8px;overflow:hidden;flex-shrink:0;">
            <?php if($c['hinh_anh_chinh']):?><img src="../assets/uploads/<?=htmlspecialchars($c['hinh_anh_chinh'])?>" style="width:100%;height:100%;object-fit:cover;" alt=""><?php else:?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;">🚗</div><?php endif;?>
        </div>
        <div style="flex:1;"><div style="font-weight:700;"><?=htmlspecialchars($c['ten_xe']??'—')?></div><div style="font-size:.82rem;color:var(--text-light);">Mã: <?=htmlspecialchars($c['ma_xe']??'')?></div></div>
        <div style="text-align:right;"><div style="font-weight:800;color:var(--accent);"><?=number_format($c['gia_ban'])?> ₫</div><div style="font-size:.82rem;color:var(--text-light);">SL: <?=$c['so_luong']?></div></div>
    </div>
    <?php endforeach;?>

    <div style="text-align:right;padding-top:14px;border-top:1px dashed var(--border);margin-top:10px;">
        <span style="font-size:.9rem;color:var(--text-light);">Tổng cộng: </span>
        <span style="font-size:1.4rem;font-weight:900;color:var(--accent);"><?=number_format($dh['tong_tien'])?> ₫</span>
    </div>

    <?php if($dh['trang_thai']==='chờ xác nhận'):?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        <form method="POST" action="../api/don_hang.php?action=update_status" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
            <input type="hidden" name="id" value="<?=$dh['id']?>">
            <input type="hidden" name="trang_thai" value="hủy">
            <input type="hidden" name="ref" value="user">
            <button type="submit" class="btn btn-danger">✗ Hủy đơn hàng</button>
        </form>
    </div>
    <?php endif;?>
</div>
</div>

<div style="text-align:center;margin-top:20px;">
    <a href="xe.php" class="btn btn-outline">🚗 Tiếp tục xem xe</a>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
