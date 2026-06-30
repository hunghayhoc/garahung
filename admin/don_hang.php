<?php
// admin/don_hang.php
$page_title = 'Quản lý Đơn Hàng – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$search=trim($_GET['search']??'');$tt_filter=trim($_GET['trang_thai']??'');
$page=max(1,(int)($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
$where=[];$params=[];
if($search!==''){$where[]="(k.ho_ten LIKE ? OR d.ma_don_hang LIKE ? OR k.so_dien_thoai LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
if($tt_filter!==''){$where[]="d.trang_thai=?";$params[]=$tt_filter;}
$w=$where?'WHERE '.implode(' AND ',$where):'';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT d.*,k.ho_ten,k.email,k.so_dien_thoai,GROUP_CONCAT(x.ten_xe SEPARATOR ', ') AS ten_xe_list FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang LEFT JOIN chi_tiet_don_hang c ON c.id_don_hang=d.id LEFT JOIN xe_oto x ON x.id=c.id_xe $w GROUP BY d.id ORDER BY d.ngay_dat_hang DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();

$tt_map=['chờ xác nhận'=>'badge-yellow','đã xác nhận'=>'badge-blue','đang giao'=>'badge-purple','hoàn thành'=>'badge-green','hủy'=>'badge-red'];
$pttt_list=['tiền mặt','chuyển khoản','thẻ tín dụng'];

// View chi tiết
$view_dh=null;$view_ct=[];
if(!empty($_GET['view'])){
    $s=$db->prepare("SELECT d.*,k.ho_ten,k.email,k.so_dien_thoai FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang WHERE d.id=? LIMIT 1");
    $s->execute([(int)$_GET['view']]);$view_dh=$s->fetch();
    if($view_dh){$s2=$db->prepare("SELECT c.*,x.ten_xe,x.ma_xe FROM chi_tiet_don_hang c LEFT JOIN xe_oto x ON x.id=c.id_xe WHERE c.id_don_hang=?");$s2->execute([$view_dh['id']]);$view_ct=$s2->fetchAll();}
}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title><link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>🛒 Quản lý Đơn Hàng</h1></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Mã đơn, tên khách, SĐT..." value="<?=htmlspecialchars($search)?>">
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <?php foreach(array_keys($tt_map) as $tt):?><option value="<?=$tt?>" <?=$tt_filter===$tt?'selected':''?>><?=$tt?></option><?php endforeach;?>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
    <a href="don_hang.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> đơn</span>
</form>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Xe</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-light);">Không có đơn hàng</td></tr>
    <?php else: foreach($list as $dh):?>
    <tr>
        <td><strong><?=htmlspecialchars($dh['ma_don_hang'])?></strong></td>
        <td><div><?=htmlspecialchars($dh['ho_ten']??'—')?></div><div style="font-size:.8rem;color:var(--text-light);"><?=htmlspecialchars($dh['so_dien_thoai']??'')?></div></td>
        <td style="font-size:.85rem;max-width:180px;"><?=htmlspecialchars($dh['ten_xe_list']??'—')?></td>
        <td style="font-weight:700;color:var(--accent);"><?=number_format($dh['tong_tien'])?> ₫</td>
        <td style="font-size:.85rem;"><?=htmlspecialchars($dh['phuong_thuc_thanh_toan']??'—')?></td>
        <td><span class="badge <?=$tt_map[$dh['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($dh['trang_thai'])?></span></td>
        <td style="font-size:.82rem;"><?=date('d/m/Y H:i',strtotime($dh['ngay_dat_hang']))?></td>
        <td><div class="table-actions">
            <a href="?view=<?=$dh['id']?>" class="btn btn-outline btn-sm">👁 Chi tiết</a>
            <?php if($dh['trang_thai']==='chờ xác nhận'):?>
            <form method="POST" action="../api/don_hang.php?action=update_status">
                <input type="hidden" name="id" value="<?=$dh['id']?>"><input type="hidden" name="trang_thai" value="đã xác nhận">
                <button class="btn btn-success btn-sm">✓ Xác nhận</button>
            </form>
            <?php endif;?>
            <?php if(!in_array($dh['trang_thai'],['hoàn thành','hủy'])):?>
            <form method="POST" action="../api/don_hang.php?action=update_status" onsubmit="return confirm('Hủy đơn hàng này?')">
                <input type="hidden" name="id" value="<?=$dh['id']?>"><input type="hidden" name="trang_thai" value="hủy">
                <button class="btn btn-danger btn-sm">✗ Hủy</button>
            </form>
            <?php endif;?>
            <form method="POST" action="../api/don_hang.php?action=delete" onsubmit="return confirm('Xóa đơn hàng này vĩnh viễn?')">
                <input type="hidden" name="id" value="<?=$dh['id']?>">
                <button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
</table>
</div></div>

<?php if($pages>1):?>
<div class="pagination">
    <?php if($page>1):?><a href="?page=<?=$page-1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span><?php else:?><a href="?page=<?=$p?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>">»</a><?php endif;?>
</div>
<?php endif;?>
</div></div>

<!-- Modal Chi Tiết -->
<?php if($view_dh):?>
<div id="modal-view" class="modal-overlay" style="align-items:flex-start;padding:30px 16px;">
<div class="modal" style="max-width:660px;">
<div class="modal-header"><h3>📋 Chi tiết đơn hàng: <?=htmlspecialchars($view_dh['ma_don_hang'])?></h3>
<a href="don_hang.php?search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>" class="modal-close">×</a></div>
<div class="modal-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Khách hàng</label><p style="font-weight:700;"><?=htmlspecialchars($view_dh['ho_ten']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Email</label><p><?=htmlspecialchars($view_dh['email']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">SĐT</label><p><?=htmlspecialchars($view_dh['so_dien_thoai']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Ngày đặt</label><p><?=date('d/m/Y H:i',strtotime($view_dh['ngay_dat_hang']))?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Thanh toán</label><p><?=htmlspecialchars($view_dh['phuong_thuc_thanh_toan']??'—')?></p></div>
        <div><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Trạng thái</label>
            <p><span class="badge <?=$tt_map[$view_dh['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($view_dh['trang_thai'])?></span></p></div>
        <?php if($view_dh['ghi_chu']):?><div style="grid-column:span 2;"><label style="font-size:.78rem;color:#999;text-transform:uppercase;font-weight:700;">Ghi chú</label><p><?=htmlspecialchars($view_dh['ghi_chu'])?></p></div><?php endif;?>
    </div>
    <h4 style="margin-bottom:10px;color:var(--primary);">🚗 Chi tiết xe</h4>
    <table style="width:100%;font-size:.9rem;">
        <thead><tr style="background:#f0f4f8;"><th style="padding:8px 12px;text-align:left;">Xe</th><th style="padding:8px 12px;text-align:left;">Mã</th><th style="padding:8px 12px;text-align:right;">Đơn giá</th><th style="padding:8px 12px;text-align:right;">SL</th></tr></thead>
        <tbody>
        <?php foreach($view_ct as $ct):?>
        <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:8px 12px;font-weight:600;"><?=htmlspecialchars($ct['ten_xe']??'—')?></td>
            <td style="padding:8px 12px;"><code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;font-size:.8rem;"><?=htmlspecialchars($ct['ma_xe']??'')?></code></td>
            <td style="padding:8px 12px;text-align:right;font-weight:700;color:var(--accent);"><?=number_format($ct['gia_ban'])?> ₫</td>
            <td style="padding:8px 12px;text-align:right;"><?=$ct['so_luong']?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
        <tfoot><tr style="background:#f0f4f8;font-weight:800;"><td colspan="2" style="padding:10px 12px;">TỔNG TIỀN</td><td colspan="2" style="padding:10px 12px;text-align:right;color:var(--accent);font-size:1.1rem;"><?=number_format($view_dh['tong_tien'])?> ₫</td></tr></tfoot>
    </table>
    <div style="margin-top:16px;">
        <h4 style="margin-bottom:10px;color:var(--primary);">Cập nhật trạng thái</h4>
        <form method="POST" action="../api/don_hang.php?action=update_status" style="display:flex;gap:8px;">
            <input type="hidden" name="id" value="<?=$view_dh['id']?>">
            <select name="trang_thai" style="flex:1;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;">
                <?php foreach(array_keys($tt_map) as $tt):?><option value="<?=$tt?>" <?=$view_dh['trang_thai']===$tt?'selected':''?>><?=$tt?></option><?php endforeach;?>
            </select>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>
</div>
<div class="modal-footer"><a href="don_hang.php" class="btn btn-outline">← Quay lại</a></div>
</div></div>
<script>document.getElementById('modal-view').classList.add('open');</script>
<?php endif;?>
<script>function openModal(id){document.getElementById(id).classList.add('open');}function closeModal(id){document.getElementById(id).classList.remove('open');}</script>
</body></html>
