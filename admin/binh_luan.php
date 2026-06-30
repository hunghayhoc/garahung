<?php
// admin/binh_luan.php
$page_title = 'Quản lý Bình Luận – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$tt_filter=trim($_GET['trang_thai']??'');
$page=max(1,(int)($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
$where=[];$params=[];
if($tt_filter!==''){$where[]="b.trang_thai=?";$params[]=$tt_filter;}
$w=$where?'WHERE '.implode(' AND ',$where):'';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM binh_luan b $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT b.*,k.ho_ten,x.ten_xe FROM binh_luan b LEFT JOIN khach_hang k ON k.id=b.id_khach_hang LEFT JOIN xe_oto x ON x.id=b.id_xe $w ORDER BY b.ngay_tao DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();
$tt_map=['phê duyệt'=>'badge-green','chờ duyệt'=>'badge-yellow','ẩn'=>'badge-gray'];
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title><link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>💬 Quản lý Bình Luận</h1></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<form method="GET" class="search-bar">
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <?php foreach(array_keys($tt_map) as $tt):?><option value="<?=$tt?>" <?=$tt_filter===$tt?'selected':''?>><?=ucfirst($tt)?></option><?php endforeach;?>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Lọc</button>
    <a href="binh_luan.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> bình luận</span>
</form>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Khách hàng</th><th>Xe</th><th>Đánh giá</th><th>Nội dung</th><th>Trạng thái</th><th>Ngày</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-light);">Không có bình luận</td></tr>
    <?php else: foreach($list as $i=>$bl):?>
    <tr>
        <td style="color:var(--text-light);"><?=$offset+$i+1?></td>
        <td><strong><?=htmlspecialchars($bl['ho_ten']??'—')?></strong></td>
        <td style="font-size:.85rem;"><?=htmlspecialchars($bl['ten_xe']??'—')?></td>
        <td style="color:var(--warning);font-size:1rem;"><?=str_repeat('⭐',(int)$bl['danh_gia'])?><span style="font-size:.8rem;color:var(--text-light);margin-left:4px;"><?=$bl['danh_gia']?>/5</span></td>
        <td style="max-width:240px;font-size:.88rem;"><?=htmlspecialchars(mb_substr($bl['noi_dung'],0,100)).(mb_strlen($bl['noi_dung'])>100?'...':'')?></td>
        <td><span class="badge <?=$tt_map[$bl['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($bl['trang_thai'])?></span></td>
        <td style="font-size:.82rem;"><?=date('d/m/Y',strtotime($bl['ngay_tao']))?></td>
        <td><div class="table-actions">
            <?php if($bl['trang_thai']!=='phê duyệt'):?>
            <form method="POST" action="../api/binh_luan.php?action=update_status">
                <input type="hidden" name="id" value="<?=$bl['id']?>"><input type="hidden" name="trang_thai" value="phê duyệt">
                <button class="btn btn-success btn-sm">✓ Duyệt</button>
            </form>
            <?php endif;?>
            <?php if($bl['trang_thai']!=='ẩn'):?>
            <form method="POST" action="../api/binh_luan.php?action=update_status">
                <input type="hidden" name="id" value="<?=$bl['id']?>"><input type="hidden" name="trang_thai" value="ẩn">
                <button class="btn btn-warning btn-sm">👁 Ẩn</button>
            </form>
            <?php endif;?>
            <form method="POST" action="../api/binh_luan.php?action=delete" onsubmit="return confirm('Xóa bình luận này?')">
                <input type="hidden" name="id" value="<?=$bl['id']?>"><button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
</table>
</div></div>
<?php if($pages>1):?>
<div class="pagination">
    <?php if($page>1):?><a href="?page=<?=$page-1?>&trang_thai=<?=urlencode($tt_filter)?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span><?php else:?><a href="?page=<?=$p?>&trang_thai=<?=urlencode($tt_filter)?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>&trang_thai=<?=urlencode($tt_filter)?>">»</a><?php endif;?>
</div>
<?php endif;?>
</div></div>
</body></html>
