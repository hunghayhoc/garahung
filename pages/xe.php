<?php
// pages/xe.php
$page_title = 'Danh Sách Xe Ô Tô – GaraHung';
require_once '../includes/header.php';
require_once '../config/db.php';

$q          = trim($_GET['q']          ?? '');
$id_hang    = (int)($_GET['hang']      ?? 0);
$id_dm      = (int)($_GET['danh_muc'] ?? 0);
$tt_filter  = trim($_GET['trang_thai'] ?? 'còn hàng');
$sort       = trim($_GET['sort']       ?? 'moi_nhat');
$page       = max(1,(int)($_GET['page'] ?? 1));
$limit = 12; $offset = ($page-1)*$limit;

$where=["1=1"]; $params=[];
if($q!==''){$where[]="(x.ten_xe LIKE ? OR x.ma_xe LIKE ? OR h.ten_hang LIKE ? OR d.ten_danh_muc LIKE ?)";$params[]="%$q%";$params[]="%$q%";$params[]="%$q%";$params[]="%$q%";}
if($id_hang>0){$where[]="x.id_hang=?";$params[]=$id_hang;}
if($id_dm>0){$where[]="x.id_danh_muc=?";$params[]=$id_dm;}
if($tt_filter!=='all'){$where[]="x.trang_thai=?";$params[]=$tt_filter;}
$w='WHERE '.implode(' AND ',$where);
$order_map=['moi_nhat'=>'x.ngay_tao DESC','gia_tang'=>'COALESCE(x.gia_khuyen_mai,x.gia_ban) ASC','gia_giam'=>'COALESCE(x.gia_khuyen_mai,x.gia_ban) DESC','ten_xe'=>'x.ten_xe ASC'];
$order=$order_map[$sort]??'x.ngay_tao DESC';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang LEFT JOIN danh_muc_xe d ON d.id=x.id_danh_muc $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT x.*,h.ten_hang,d.ten_danh_muc FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang LEFT JOIN danh_muc_xe d ON d.id=x.id_danh_muc $w ORDER BY $order LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();
$hang_list=$db->query("SELECT id,ten_hang FROM hang_xe ORDER BY ten_hang")->fetchAll();
$dm_list=$db->query("SELECT id,ten_danh_muc FROM danh_muc_xe WHERE trang_thai='hoạt động' ORDER BY ten_danh_muc")->fetchAll();
?>

<div class="container" style="padding-top:28px;">
<div style="display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:start;">

<!-- Sidebar lọc -->
<div>
<div class="card"><div class="card-header"><h2>🔽 Bộ lọc</h2></div>
<div class="card-body">
<form method="GET">
    <input type="hidden" name="sort" value="<?=htmlspecialchars($sort)?>">
    <div class="form-group"><label class="lbl" style="font-size:.8rem;">🔍 Tìm kiếm</label><input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Tên xe, hãng..."></div>
    <div class="form-group"><label class="lbl" style="font-size:.8rem;">🏭 Hãng xe</label>
        <select name="hang"><option value="">Tất cả hãng</option>
        <?php foreach($hang_list as $h):?><option value="<?=$h['id']?>" <?=$id_hang===$h['id']?'selected':''?>><?=htmlspecialchars($h['ten_hang'])?></option><?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl" style="font-size:.8rem;">📂 Danh mục</label>
        <select name="danh_muc"><option value="">Tất cả loại</option>
        <?php foreach($dm_list as $d):?><option value="<?=$d['id']?>" <?=$id_dm===$d['id']?'selected':''?>><?=htmlspecialchars($d['ten_danh_muc'])?></option><?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl" style="font-size:.8rem;">📦 Trạng thái</label>
        <select name="trang_thai">
            <option value="còn hàng" <?=$tt_filter==='còn hàng'?'selected':''?>>Còn hàng</option>
            <option value="all" <?=$tt_filter==='all'?'selected':''?>>Tất cả</option>
        </select></div>
    <button type="submit" class="btn btn-primary btn-full">🔍 Lọc xe</button>
    <a href="xe.php" class="btn btn-outline btn-full" style="margin-top:8px;">Xóa lọc</a>
</form>
</div></div>
</div>

<!-- Danh sách xe -->
<div>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <div>
        <strong style="color:var(--primary);"><?=number_format($total)?> xe</strong>
        <?php if($q):?> cho "<em><?=htmlspecialchars($q)?></em>"<?php endif;?>
    </div>
    <form method="GET" style="display:flex;gap:8px;">
        <?php foreach(['q'=>$q,'hang'=>$id_hang,'danh_muc'=>$id_dm,'trang_thai'=>$tt_filter] as $k=>$v): if($v): ?><input type="hidden" name="<?=$k?>" value="<?=htmlspecialchars($v)?>"><?php endif; endforeach;?>
        <select name="sort" onchange="this.form.submit()" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;">
            <option value="moi_nhat" <?=$sort==='moi_nhat'?'selected':''?>>Mới nhất</option>
            <option value="gia_tang"  <?=$sort==='gia_tang'?'selected':''?>>Giá tăng dần</option>
            <option value="gia_giam"  <?=$sort==='gia_giam'?'selected':''?>>Giá giảm dần</option>
            <option value="ten_xe"    <?=$sort==='ten_xe'?'selected':''?>>Tên xe A-Z</option>
        </select>
    </form>
</div>

<?php if(empty($list)):?>
<div class="alert alert-info">Không tìm thấy xe phù hợp với tiêu chí lọc.</div>
<?php else:?>
<div class="xe-grid">
<?php foreach($list as $xe):
    $gia=$xe['gia_khuyen_mai']??$xe['gia_ban'];
    $tt_cls=['còn hàng'=>'badge-green','hết hàng'=>'badge-red','đang bảo dưỡng'=>'badge-yellow'];
?>
<a href="xe_detail.php?id=<?=$xe['id']?>" style="text-decoration:none;color:inherit;">
<div class="xe-card">
    <div class="img-wrap">
        <?php if($xe['hinh_anh_chinh']):?><img src="../assets/uploads/<?=htmlspecialchars($xe['hinh_anh_chinh'])?>" alt="<?=htmlspecialchars($xe['ten_xe'])?>">
        <?php else:?><div class="img-placeholder">🚗</div><?php endif;?>
        <?php if($xe['gia_khuyen_mai']):?><span style="position:absolute;top:10px;right:10px;background:var(--accent);color:#fff;padding:3px 10px;border-radius:20px;font-size:.76rem;font-weight:700;">SALE</span><?php endif;?>
        <span style="position:absolute;top:10px;left:10px;" class="badge <?=$tt_cls[$xe['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($xe['trang_thai'])?></span>
    </div>
    <div class="xe-info">
        <div class="xe-name"><?=htmlspecialchars($xe['ten_xe'])?></div>
        <div class="xe-meta">
            <span>🏭 <?=htmlspecialchars($xe['ten_hang']??'—')?></span>
            <span>⛽ <?=htmlspecialchars($xe['nhien_lieu']??'—')?></span>
            <span>👥 <?=$xe['so_cho_ngoi']?> chỗ</span>
            <span>📅 <?=$xe['nam_san_xuat']?></span>
        </div>
        <div class="xe-price">
            <?=number_format($gia)?> ₫
            <?php if($xe['gia_khuyen_mai']):?><span class="original"><?=number_format($xe['gia_ban'])?> ₫</span><?php endif;?>
        </div>
    </div>
</div>
</a>
<?php endforeach;?>
</div>

<!-- Pagination -->
<?php if($pages>1):?>
<div class="pagination" style="margin-top:28px;">
    <?php if($page>1):?><a href="?page=<?=$page-1?>&q=<?=urlencode($q)?>&hang=<?=$id_hang?>&danh_muc=<?=$id_dm?>&trang_thai=<?=urlencode($tt_filter)?>&sort=<?=$sort?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span><?php else:?><a href="?page=<?=$p?>&q=<?=urlencode($q)?>&hang=<?=$id_hang?>&danh_muc=<?=$id_dm?>&trang_thai=<?=urlencode($tt_filter)?>&sort=<?=$sort?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>&q=<?=urlencode($q)?>&hang=<?=$id_hang?>&danh_muc=<?=$id_dm?>&trang_thai=<?=urlencode($tt_filter)?>&sort=<?=$sort?>">»</a><?php endif;?>
</div>
<?php endif;?>
<?php endif;?>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
<!-- Hưng v1: tạo danh sách xe -->
<!-- Hưng v2: thêm filter hãng xe -->
<!-- Hưng v3: thêm filter giá -->
<!-- Hưng v4: fix lỗi hiển thị giá -->
<!-- Hưng v5: cải thiện UI -->
