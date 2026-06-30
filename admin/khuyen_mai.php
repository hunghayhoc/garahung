<?php
// admin/khuyen_mai.php
$page_title = 'Quản lý Khuyến Mãi – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$list=$db->query("SELECT * FROM khuyen_mai ORDER BY ngay_bat_dau DESC")->fetchAll();
$edit=null;
if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM khuyen_mai WHERE id=? LIMIT 1");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title><link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>🎁 Quản lý Khuyến Mãi</h1><button class="btn btn-primary" onclick="openModal('modal-add')">+ Thêm khuyến mãi</button></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Tên KM</th><th>Mã KM</th><th>Giảm (%)</th><th>Giá trị giảm</th><th>Từ ngày</th><th>Đến ngày</th><th>Còn lại</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="10" style="text-align:center;padding:28px;color:var(--text-light);">Chưa có khuyến mãi</td></tr>
    <?php else: foreach($list as $i=>$km):$expired=($km['ngay_ket_thuc']&&strtotime($km['ngay_ket_thuc'])<time());?>
    <tr>
        <td style="color:var(--text-light);"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($km['ten_khuyen_mai'])?></strong></td>
        <td><code style="background:#f0f4f8;padding:3px 8px;border-radius:4px;font-size:.85rem;font-weight:700;color:var(--accent);"><?=htmlspecialchars($km['ma_khuyen_mai'])?></code></td>
        <td><?=$km['phan_tram_giam']?$km['phan_tram_giam'].'%':'—'?></td>
        <td><?=$km['gia_tri_giam']?number_format($km['gia_tri_giam']).' ₫':'—'?></td>
        <td style="font-size:.82rem;"><?=$km['ngay_bat_dau']?date('d/m/Y',strtotime($km['ngay_bat_dau'])):'—'?></td>
        <td style="font-size:.82rem;"><?=$km['ngay_ket_thuc']?date('d/m/Y',strtotime($km['ngay_ket_thuc'])):'—'?></td>
        <td><span class="badge badge-blue"><?=$km['so_luong_su_dung']?></span></td>
        <td><span class="badge <?=$expired||$km['trang_thai']==='hết hạn'?'badge-red':'badge-green'?>"><?=$expired?'hết hạn':htmlspecialchars($km['trang_thai'])?></span></td>
        <td><div class="table-actions">
            <a href="?edit=<?=$km['id']?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
            <form method="POST" action="../api/khuyen_mai.php?action=delete" onsubmit="return confirm('Xóa khuyến mãi này?')">
                <input type="hidden" name="id" value="<?=$km['id']?>"><button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
</table>
</div></div>
</div></div>

<?php function km_form($km=[]){?>
<div class="form-row">
    <div class="form-group span2"><label class="lbl">Tên khuyến mãi <span class="req">*</span></label><input type="text" name="ten_khuyen_mai" value="<?=htmlspecialchars($km['ten_khuyen_mai']??'')?>" required></div>
    <div class="form-group"><label class="lbl">Mã khuyến mãi <span class="req">*</span></label><input type="text" name="ma_khuyen_mai" value="<?=htmlspecialchars($km['ma_khuyen_mai']??'')?>" style="text-transform:uppercase;" required></div>
    <div class="form-group"><label class="lbl">% Giảm (1–100)</label><input type="number" name="phan_tram_giam" value="<?=$km['phan_tram_giam']??''?>" min="1" max="100"></div>
    <div class="form-group"><label class="lbl">Giá trị giảm (₫)</label><input type="number" name="gia_tri_giam" value="<?=$km['gia_tri_giam']??''?>" min="0" step="10000"></div>
    <div class="form-group"><label class="lbl">Từ ngày</label><input type="date" name="ngay_bat_dau" value="<?=$km['ngay_bat_dau']??''?>"></div>
    <div class="form-group"><label class="lbl">Đến ngày</label><input type="date" name="ngay_ket_thuc" value="<?=$km['ngay_ket_thuc']??''?>"></div>
    <div class="form-group"><label class="lbl">Số lượng sử dụng</label><input type="number" name="so_luong_su_dung" value="<?=$km['so_luong_su_dung']??1?>" min="0"></div>
    <div class="form-group"><label class="lbl">Trạng thái</label>
        <select name="trang_thai"><option value="hoạt động" <?=(($km['trang_thai']??'hoạt động')==='hoạt động')?'selected':''?>>Hoạt động</option><option value="hết hạn" <?=(($km['trang_thai']??'')==='hết hạn')?'selected':''?>>Hết hạn</option></select></div>
</div>
<?php }?>

<div id="modal-add" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-add')" style="align-items:flex-start;padding:30px 16px;">
<div class="modal" style="max-width:640px;"><div class="modal-header"><h3>➕ Thêm Khuyến Mãi</h3><button class="modal-close" onclick="closeModal('modal-add')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/khuyen_mai.php?action=add">
    <?php km_form();?>
    <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div></div>

<?php if($edit):?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')" style="align-items:flex-start;padding:30px 16px;">
<div class="modal" style="max-width:640px;"><div class="modal-header"><h3>✏️ Sửa: <?=htmlspecialchars($edit['ten_khuyen_mai'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/khuyen_mai.php?action=update">
    <input type="hidden" name="id" value="<?=$edit['id']?>">
    <?php km_form($edit);?>
    <div class="modal-footer"><a href="khuyen_mai.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div></div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif;?>
<script>function openModal(id){document.getElementById(id).classList.add('open');}function closeModal(id){document.getElementById(id).classList.remove('open');}</script>
</body></html>
