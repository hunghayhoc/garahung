<?php
// admin/lich_hen.php
$page_title = 'Quản lý Lịch Hẹn – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$search=trim($_GET['search']??'');$tt_filter=trim($_GET['trang_thai']??'');
$page=max(1,(int)($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
$where=[];$params=[];
if($search!==''){$where[]="(k.ho_ten LIKE ? OR x.ten_xe LIKE ? OR k.so_dien_thoai LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
if($tt_filter!==''){$where[]="l.trang_thai=?";$params[]=$tt_filter;}
$w=$where?'WHERE '.implode(' AND ',$where):'';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM lich_hen l LEFT JOIN khach_hang k ON k.id=l.id_khach_hang LEFT JOIN xe_oto x ON x.id=l.id_xe $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT l.*,k.ho_ten,k.so_dien_thoai,x.ten_xe,n.ho_ten AS ten_nv FROM lich_hen l LEFT JOIN khach_hang k ON k.id=l.id_khach_hang LEFT JOIN xe_oto x ON x.id=l.id_xe LEFT JOIN nhan_vien n ON n.id=l.id_nhan_vien $w ORDER BY l.ngay_gio ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();
$tt_map=['chờ xác nhận'=>'badge-yellow','đã xác nhận'=>'badge-blue','hoàn thành'=>'badge-green','hủy'=>'badge-red'];
$nv_list=$db->query("SELECT id,ho_ten FROM nhan_vien WHERE trang_thai='hoạt động' ORDER BY ho_ten")->fetchAll();
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title><link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>📅 Quản lý Lịch Hẹn</h1></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Tên khách, tên xe, SĐT..." value="<?=htmlspecialchars($search)?>">
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <?php foreach(array_keys($tt_map) as $tt):?><option value="<?=$tt?>" <?=$tt_filter===$tt?'selected':''?>><?=$tt?></option><?php endforeach;?>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
    <a href="lich_hen.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> lịch hẹn</span>
</form>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Khách hàng</th><th>Xe</th><th>Ngày giờ hẹn</th><th>Địa điểm</th><th>Nhân viên</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-light);">Không có lịch hẹn</td></tr>
    <?php else: foreach($list as $i=>$l):?>
    <tr>
        <td style="color:var(--text-light);"><?=$offset+$i+1?></td>
        <td><strong><?=htmlspecialchars($l['ho_ten']??'—')?></strong><div style="font-size:.8rem;color:var(--text-light);"><?=htmlspecialchars($l['so_dien_thoai']??'')?></div></td>
        <td style="font-size:.88rem;"><?=htmlspecialchars($l['ten_xe']??'—')?></td>
        <td style="font-weight:600;white-space:nowrap;"><?=date('d/m/Y H:i',strtotime($l['ngay_gio']))?></td>
        <td style="font-size:.85rem;max-width:160px;"><?=htmlspecialchars($l['dia_diem']??'—')?></td>
        <td style="font-size:.85rem;"><?=($l['ten_nv'] ? htmlspecialchars($l['ten_nv']) : '<i style="color:#aaa">Chưa phân công</i>')?>

        <td><span class="badge <?=$tt_map[$l['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($l['trang_thai'])?></span></td>
        <td><div class="table-actions">
            <?php if($l['trang_thai']==='chờ xác nhận'):?>
            <form method="POST" action="../api/lich_hen.php?action=update_status">
                <input type="hidden" name="id" value="<?=$l['id']?>"><input type="hidden" name="trang_thai" value="đã xác nhận">
                <button class="btn btn-success btn-sm">✓ XN</button>
            </form>
            <?php endif;?>
            <?php if(!in_array($l['trang_thai'],['hủy','hoàn thành'])):?>
            <form method="POST" action="../api/lich_hen.php?action=update_status" onsubmit="return confirm('Hủy lịch hẹn?')">
                <input type="hidden" name="id" value="<?=$l['id']?>"><input type="hidden" name="trang_thai" value="hủy">
                <button class="btn btn-danger btn-sm">✗ Hủy</button>
            </form>
            <?php endif;?>
            <form method="POST" action="../api/lich_hen.php?action=delete" onsubmit="return confirm('Xóa lịch hẹn?')">
                <input type="hidden" name="id" value="<?=$l['id']?>"><button class="btn btn-danger btn-sm">🗑</button>
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
</body></html>
