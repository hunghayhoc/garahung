<?php
// pages/xe_detail.php
$id = (int)($_GET['id'] ?? 0);
require_once '../config/db.php';
if($id<=0){header("Location: xe.php?error=Xe không tồn tại.");exit;}
$stmt=$db->prepare("SELECT x.*,h.ten_hang,h.quoc_gia,d.ten_danh_muc FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang LEFT JOIN danh_muc_xe d ON d.id=x.id_danh_muc WHERE x.id=? LIMIT 1");
$stmt->execute([$id]);$xe=$stmt->fetch();
if(!$xe){header("Location: xe.php?error=Xe không tồn tại.");exit;}
$gallery = [];
try {
    $db->query("SELECT 1 FROM xe_oto_anh LIMIT 1");
    $img_stmt = $db->prepare("SELECT filename, is_chinh FROM xe_oto_anh WHERE id_xe=? ORDER BY is_chinh DESC, thu_tu ASC, id ASC");
    $img_stmt->execute([$id]);
    $gallery = $img_stmt->fetchAll();
} catch (Throwable $e) {
    $gallery = [];
}
if (empty($xe['hinh_anh_chinh']) && !empty($gallery)) {
    $xe['hinh_anh_chinh'] = $gallery[0]['filename'] ?? null;
}
$page_title = htmlspecialchars($xe['ten_xe']).' – GaraHung';
require_once '../includes/header.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
// Bình luận
$bl_stmt=$db->prepare("SELECT b.*,k.ho_ten FROM binh_luan b LEFT JOIN khach_hang k ON k.id=b.id_khach_hang WHERE b.id_xe=? AND b.trang_thai='phê duyệt' ORDER BY b.ngay_tao DESC LIMIT 10");
$bl_stmt->execute([$id]);$bl_list=$bl_stmt->fetchAll();
$avg_stmt=$db->prepare("SELECT AVG(danh_gia) FROM binh_luan WHERE id_xe=? AND trang_thai='phê duyệt'");
$avg_stmt->execute([$id]);$avg_dg=round((float)$avg_stmt->fetchColumn(),1);
// Xe liên quan
$lq=$db->prepare("SELECT x.*,h.ten_hang FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang WHERE x.id_danh_muc=? AND x.id!=? AND x.trang_thai='còn hàng' LIMIT 4");
$lq->execute([$xe['id_danh_muc'],$id]);$xe_lq=$lq->fetchAll();
$gia=$xe['gia_khuyen_mai']??$xe['gia_ban'];
?>
<div class="container">
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<div class="detail-grid">
<!-- Hình ảnh -->
<div>
<div class="detail-img" style="height:380px;">
    <?php if($xe['hinh_anh_chinh']):?><img id="main-xe-img" src="../assets/uploads/<?=htmlspecialchars($xe['hinh_anh_chinh'])?>" alt="<?=htmlspecialchars($xe['ten_xe'])?>">
    <?php else:?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;background:#f0f4f8;">🚗</div><?php endif;?>
</div>

<?php if(!empty($gallery)):?>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
    <?php foreach($gallery as $g):?>
        <?php $fn = $g['filename'] ?? ''; if(!$fn) continue; ?>
        <button type="button"
                onclick="swapXeImage('<?=htmlspecialchars($fn, ENT_QUOTES)?>')"
                style="padding:0;border:1px solid #eee;border-radius:10px;overflow:hidden;background:#fff;width:96px;height:64px;cursor:pointer;<?=($xe['hinh_anh_chinh']===$fn?'outline:2px solid var(--primary);':'')?>">
            <img src="../assets/uploads/<?=htmlspecialchars($fn)?>" alt="" style="width:100%;height:100%;object-fit:cover;">
        </button>
    <?php endforeach;?>
</div>
<?php endif;?>
</div>

<!-- Thông tin -->
<div class="detail-info">
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
        <span class="badge badge-blue"><?=htmlspecialchars($xe['ten_hang']??'—')?></span>
        <span class="badge badge-gray"><?=htmlspecialchars($xe['ten_danh_muc']??'—')?></span>
        <span class="badge <?=$xe['trang_thai']==='còn hàng'?'badge-green':($xe['trang_thai']==='hết hàng'?'badge-red':'badge-yellow')?>"><?=htmlspecialchars($xe['trang_thai'])?></span>
    </div>
    <h1 style="font-size:1.4rem;color:var(--primary);font-weight:900;margin-bottom:6px;"><?=htmlspecialchars($xe['ten_xe'])?></h1>
    <div style="font-size:.85rem;color:var(--text-light);margin-bottom:14px;">Mã xe: <strong><?=htmlspecialchars($xe['ma_xe'])?></strong>
        <?php if($avg_dg>0):?> &nbsp;|&nbsp; ⭐ <?=$avg_dg?>/5 (<?=count($bl_list)?> đánh giá)<?php endif;?></div>
    <div class="price-big"><?=number_format($gia)?> ₫</div>
    <?php if($xe['gia_khuyen_mai']):?>
    <div style="color:#aaa;text-decoration:line-through;font-size:.95rem;margin-top:4px;"><?=number_format($xe['gia_ban'])?> ₫ &nbsp; <span style="background:var(--accent);color:#fff;padding:2px 8px;border-radius:20px;font-size:.78rem;text-decoration:none;font-weight:700;">Tiết kiệm <?=number_format($xe['gia_ban']-$xe['gia_khuyen_mai'])?> ₫</span></div>
    <?php endif;?>

    <div class="specs-grid" style="margin-top:18px;">
        <?php foreach([
            ['⛽','Nhiên liệu',$xe['nhien_lieu']??'—'],
            ['📅','Năm SX',$xe['nam_san_xuat']??'—'],
            ['👥','Số chỗ',$xe['so_cho_ngoi'].' chỗ'],
            ['⚙️','Hộp số',$xe['hop_so']??'—'],
            ['🌍','Xuất xứ',$xe['quoc_gia']??'—'],
            ['📍','Trạng thái',$xe['trang_thai']],
        ] as [$icon,$label,$value]):?>
        <div class="spec-item"><div class="spec-label"><?=$icon?> <?=$label?></div><div class="spec-value"><?=htmlspecialchars($value)?></div></div>
        <?php endforeach;?>
    </div>

    <?php if($xe['mo_ta']):?>
    <div style="background:#f7faff;border-radius:10px;padding:14px;margin:16px 0;font-size:.9rem;line-height:1.7;color:var(--text-light);">
        <?=nl2br(htmlspecialchars($xe['mo_ta']))?>
    </div>
    <?php endif;?>

    <?php if($xe['trang_thai']==='còn hàng'):?>
    <div style="display:flex;gap:10px;margin-top:16px;">
        <?php if(!empty($_SESSION['user_id'])):?>
        <a href="dat_xe.php?id=<?=$xe['id']?>" class="btn btn-primary" style="flex:1;justify-content:center;">🛒 Đặt mua ngay</a>
        <a href="lich_hen.php?id_xe=<?=$xe['id']?>" class="btn btn-outline" style="flex:1;justify-content:center;">📅 Đặt lịch xem</a>
        <?php else:?>
        <a href="login.php?redirect=dat_xe.php?id=<?=$xe['id']?>" class="btn btn-primary" style="flex:1;justify-content:center;">🛒 Đặt mua (Đăng nhập)</a>
        <?php endif;?>
    </div>
    <?php endif;?>
</div>
</div>

<!-- Bình luận -->
<div class="card" style="margin-top:28px;">
<div class="card-header"><h2>💬 Đánh giá & Bình luận</h2></div>
<div class="card-body">
    <?php if(!empty($_SESSION['user_id'])):?>
    <form method="POST" action="../api/binh_luan.php?action=add" style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px dashed var(--border);">
        <input type="hidden" name="id_xe" value="<?=$xe['id']?>">
        <div class="form-group"><label class="lbl">Đánh giá của bạn</label>
            <div style="display:flex;gap:8px;">
                <?php for($s=1;$s<=5;$s++):?><label style="cursor:pointer;"><input type="radio" name="danh_gia" value="<?=$s?>" <?=$s===5?'checked':''?> style="display:none;"><span style="font-size:1.6rem;transition:transform .1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform=''">⭐</span></label><?php endfor;?>
            </div></div>
        <div class="form-group"><label class="lbl">Nội dung <span class="req">*</span></label><textarea name="noi_dung" required placeholder="Chia sẻ trải nghiệm về xe này..."></textarea></div>
        <button type="submit" class="btn btn-primary">📤 Gửi bình luận</button>
    </form>
    <?php else:?><div class="alert alert-info">Vui lòng <a href="login.php">đăng nhập</a> để viết bình luận.</div><?php endif;?>

    <?php if(empty($bl_list)):?><p style="color:var(--text-light);">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
    <?php else: foreach($bl_list as $bl):?>
    <div style="border-bottom:1px solid #f0f0f0;padding:14px 0;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.95rem;"><?=mb_substr($bl['ho_ten']??'?',0,1)?></div>
            <div><div style="font-weight:700;"><?=htmlspecialchars($bl['ho_ten']??'Ẩn danh')?></div>
            <div style="font-size:.82rem;color:var(--text-light);"><?=date('d/m/Y',strtotime($bl['ngay_tao']))?></div></div>
            <div style="margin-left:auto;color:var(--warning);"><?=str_repeat('⭐',(int)$bl['danh_gia'])?></div>
        </div>
        <p style="font-size:.92rem;color:var(--text);line-height:1.6;"><?=nl2br(htmlspecialchars($bl['noi_dung']))?></p>
    </div>
    <?php endforeach; endif;?>
</div>
</div>

<!-- Xe liên quan -->
<?php if(!empty($xe_lq)):?>
<div style="margin-top:28px;">
    <h2 style="font-size:1.2rem;color:var(--primary);font-weight:800;margin-bottom:16px;">🚗 Xe liên quan</h2>
    <div class="xe-grid">
    <?php foreach($xe_lq as $lqx):$lqgia=$lqx['gia_khuyen_mai']??$lqx['gia_ban'];?>
    <a href="xe_detail.php?id=<?=$lqx['id']?>" style="text-decoration:none;color:inherit;">
    <div class="xe-card">
        <div class="img-wrap"><?php if($lqx['hinh_anh_chinh']):?><img src="../assets/uploads/<?=htmlspecialchars($lqx['hinh_anh_chinh'])?>" alt=""><?php else:?><div class="img-placeholder">🚗</div><?php endif;?></div>
        <div class="xe-info"><div class="xe-name"><?=htmlspecialchars($lqx['ten_xe'])?></div>
        <div class="xe-price"><?=number_format($lqgia)?> ₫</div></div>
    </div></a>
    <?php endforeach;?>
    </div>
</div>
<?php endif;?>
</div>
<?php require_once '../includes/footer.php'; ?>
<script>
function swapXeImage(filename){
    const img = document.getElementById('main-xe-img');
    if(!img) return;
    img.src = '../assets/uploads/' + encodeURIComponent(filename);
}
</script>
