<?php
// pages/khuyen_mai.php
$page_title = 'Khuyến Mãi – GaraHung';
require_once '../includes/header.php';
require_once '../config/db.php';

$stmt=$db->query("SELECT * FROM khuyen_mai WHERE trang_thai='hoạt động' AND (ngay_ket_thuc IS NULL OR ngay_ket_thuc>=CURDATE()) ORDER BY ngay_bat_dau DESC");
$list=$stmt->fetchAll();
?>
<div class="container">
<div class="page-header"><h1>🎁 Chương trình Khuyến Mãi</h1></div>

<?php if(empty($list)):?>
<div class="card"><div class="card-body" style="text-align:center;padding:48px;">
    <div style="font-size:3rem;margin-bottom:12px;">🎁</div>
    <h3 style="color:var(--primary);">Chưa có khuyến mãi nào đang hoạt động</h3>
    <p style="color:var(--text-light);">Hãy quay lại sau để không bỏ lỡ ưu đãi!</p>
</div></div>
<?php else:?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
<?php foreach($list as $km):
    $days_left=$km['ngay_ket_thuc']?ceil((strtotime($km['ngay_ket_thuc'])-time())/86400):null;
?>
<div class="card" style="position:relative;overflow:visible;">
    <div style="position:absolute;top:-10px;right:20px;background:var(--accent);color:#fff;padding:4px 14px;border-radius:20px;font-size:.8rem;font-weight:700;">
        <?php if($km['phan_tram_giam']):?>-<?=$km['phan_tram_giam']?>%<?php elseif($km['gia_tri_giam']):?>-<?=number_format($km['gia_tri_giam'])?> ₫<?php endif;?>
    </div>
    <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:22px 20px 16px;border-radius:14px 14px 0 0;">
        <div style="font-size:2rem;margin-bottom:8px;">🎁</div>
        <h3 style="color:#fff;font-size:1.05rem;font-weight:800;margin-bottom:6px;"><?=htmlspecialchars($km['ten_khuyen_mai'])?></h3>
        <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:8px 14px;display:inline-block;">
            <span style="color:rgba(255,255,255,.8);font-size:.82rem;">Mã:</span>
            <strong style="color:#fff;font-size:1rem;letter-spacing:1px;"><?=htmlspecialchars($km['ma_khuyen_mai'])?></strong>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
            <?php if($km['ngay_bat_dau']):?><div><div style="font-size:.75rem;color:#999;text-transform:uppercase;font-weight:700;">Từ ngày</div><div style="font-weight:600;"><?=date('d/m/Y',strtotime($km['ngay_bat_dau']))?></div></div><?php endif;?>
            <?php if($km['ngay_ket_thuc']):?><div><div style="font-size:.75rem;color:#999;text-transform:uppercase;font-weight:700;">Đến ngày</div><div style="font-weight:600;"><?=date('d/m/Y',strtotime($km['ngay_ket_thuc']))?></div></div><?php endif;?>
        </div>
        <?php if($days_left!==null):?>
        <div style="background:<?=$days_left<=3?'#fdecea':'#e8f5e9'?>;border-radius:8px;padding:8px 12px;font-size:.85rem;font-weight:600;color:<?=$days_left<=3?'var(--danger)':'var(--success)'?>;">
            ⏰ <?=$days_left<=0?'Hết hạn hôm nay':'Còn '.$days_left.' ngày'?>
        </div>
        <?php endif;?>
        <div style="margin-top:14px;">
            <button onclick="copyCode('<?=htmlspecialchars($km['ma_khuyen_mai'])?>')" class="btn btn-primary btn-full">📋 Sao chép mã</button>
        </div>
    </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
</div>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('✅ Đã sao chép mã: ' + code + '\nÁp dụng khi đặt xe để được giảm giá!');
    }).catch(() => {
        prompt('Sao chép mã khuyến mãi:', code);
    });
}
</script>
<?php require_once '../includes/footer.php'; ?>
<!-- Phong v1: tạo trang khuyến mãi -->
<!-- Phong v1: tạo trang khuyến mãi -->
