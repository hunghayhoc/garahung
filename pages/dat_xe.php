<?php
// pages/dat_xe.php
session_start();
require_once '../includes/auth_check.php';
requireLogin('../pages/login.php');
if(empty($_SESSION['user_id'])){header("Location: login.php?error=Chỉ khách hàng mới đặt xe được.");exit;}
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if($id<=0){header("Location: xe.php?error=Xe không hợp lệ.");exit;}
$stmt=$db->prepare("SELECT x.*,h.ten_hang,d.ten_danh_muc FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang LEFT JOIN danh_muc_xe d ON d.id=x.id_danh_muc WHERE x.id=? AND x.trang_thai='còn hàng' LIMIT 1");
$stmt->execute([$id]);$xe=$stmt->fetch();
if(!$xe){header("Location: xe.php?error=Xe không tồn tại hoặc đã hết hàng.");exit;}
$page_title = 'Đặt mua – '.htmlspecialchars($xe['ten_xe']).' – GaraHung';
$gia=$xe['gia_khuyen_mai']??$xe['gia_ban'];
require_once '../includes/header.php';
$error=htmlspecialchars($_GET['error']??'');
?>
<div class="container">
<div style="display:grid;grid-template-columns:1fr 1.1fr;gap:28px;align-items:start;">
<!-- Thông tin xe -->
<div class="card">
<div class="card-header"><h2>🚗 Xe đặt mua</h2></div>
<div class="card-body">
    <div style="height:220px;background:#f0f4f8;border-radius:10px;overflow:hidden;margin-bottom:16px;">
        <?php if($xe['hinh_anh_chinh']):?><img src="../assets/uploads/<?=htmlspecialchars($xe['hinh_anh_chinh'])?>" style="width:100%;height:100%;object-fit:cover;" alt="">
        <?php else:?><div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;">🚗</div><?php endif;?>
    </div>
    <h3 style="font-size:1.1rem;color:var(--primary);margin-bottom:8px;"><?=htmlspecialchars($xe['ten_xe'])?></h3>
    <div style="font-size:.85rem;color:var(--text-light);margin-bottom:12px;">
        🏭 <?=htmlspecialchars($xe['ten_hang']??'—')?> &nbsp;|&nbsp; 📂 <?=htmlspecialchars($xe['ten_danh_muc']??'—')?>
    </div>
    <div class="specs-grid">
        <div class="spec-item"><div class="spec-label">Năm SX</div><div class="spec-value"><?=$xe['nam_san_xuat']?></div></div>
        <div class="spec-item"><div class="spec-label">Nhiên liệu</div><div class="spec-value"><?=$xe['nhien_lieu']??'—'?></div></div>
        <div class="spec-item"><div class="spec-label">Hộp số</div><div class="spec-value"><?=$xe['hop_so']??'—'?></div></div>
        <div class="spec-item"><div class="spec-label">Số chỗ</div><div class="spec-value"><?=$xe['so_cho_ngoi']?> chỗ</div></div>
    </div>
    <div style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--border);">
        <?php if($xe['gia_khuyen_mai']):?>
        <div style="text-decoration:line-through;color:#aaa;font-size:.9rem;"><?=number_format($xe['gia_ban'])?> ₫</div>
        <?php endif;?>
        <div style="font-size:1.5rem;font-weight:900;color:var(--accent);" id="gia-hien"><?=number_format($gia)?> ₫</div>
    </div>
</div>
</div>

<!-- Form đặt xe -->
<div class="card">
<div class="card-header"><h2>📋 Thông tin đặt mua</h2></div>
<div class="card-body">
    <?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
    <form method="POST" action="../api/don_hang.php?action=create">
        <input type="hidden" name="id_xe" value="<?=$xe['id']?>">
        <div class="form-group"><label class="lbl">Phương thức thanh toán</label>
            <select name="phuong_thuc_thanh_toan">
                <option value="tiền mặt">💵 Tiền mặt tại showroom</option>
                <option value="chuyển khoản">🏦 Chuyển khoản ngân hàng</option>
                <option value="thẻ tín dụng">💳 Thẻ tín dụng / ghi nợ</option>
            </select></div>
        <div class="form-group"><label class="lbl">Mã khuyến mãi (nếu có)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" id="ma_km_input" placeholder="Nhập mã KM..." style="flex:1;text-transform:uppercase;">
                <input type="hidden" name="ma_km" id="ma_km_value">
                <button type="button" onclick="applyKM()" class="btn btn-outline">Áp dụng</button>
            </div>
            <div id="km-result" style="margin-top:6px;font-size:.88rem;"></div></div>
        <div class="form-group"><label class="lbl">Ghi chú</label><textarea name="ghi_chu" placeholder="Yêu cầu đặc biệt, địa chỉ giao xe..."></textarea></div>

        <div style="background:#f7faff;border-radius:10px;padding:16px;margin-bottom:18px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Giá xe:</span><span><?=number_format($gia)?> ₫</span></div>
            <div id="row-km" style="display:none;justify-content:space-between;margin-bottom:8px;color:var(--success);"><span>Giảm giá:</span><span id="so-giam"></span></div>
            <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;border-top:1px solid var(--border);padding-top:10px;"><span>Tổng cộng:</span><span id="tong-tien" style="color:var(--accent);"><?=number_format($gia)?> ₫</span></div>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="font-size:1rem;padding:14px;">🛒 Xác nhận đặt mua</button>
        <a href="xe_detail.php?id=<?=$xe['id']?>" class="btn btn-outline btn-full" style="margin-top:8px;">← Quay lại</a>
    </form>
</div>
</div>
</div>
</div>

<script>
const giaGoc = <?=$gia?>;
let giaSau = giaGoc;

async function applyKM() {
    const ma = document.getElementById('ma_km_input').value.trim().toUpperCase();
    if (!ma) return;
    const res = await fetch('../api/khuyen_mai.php?action=apply', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ma_khuyen_mai=' + encodeURIComponent(ma) + '&gia_ban=' + giaGoc
    });
    const data = await res.json();
    const div = document.getElementById('km-result');
    if (data.success) {
        giaSau = data.gia_sau;
        document.getElementById('ma_km_value').value = ma;
        div.innerHTML = '<span style="color:var(--success);">✅ ' + data.ten + ' – Giảm ' + Number(data.giam).toLocaleString('vi-VN') + ' ₫</span>';
        document.getElementById('row-km').style.display = 'flex';
        document.getElementById('so-giam').textContent = '-' + Number(data.giam).toLocaleString('vi-VN') + ' ₫';
        document.getElementById('tong-tien').textContent = Number(giaSau).toLocaleString('vi-VN') + ' ₫';
    } else {
        div.innerHTML = '<span style="color:var(--danger);">❌ ' + data.message + '</span>';
        document.getElementById('ma_km_value').value = '';
    }
}
</script>
<?php require_once '../includes/footer.php'; ?>
