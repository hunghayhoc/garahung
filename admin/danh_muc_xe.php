<?php
// admin/danh_muc_xe.php
$page_title = 'Quản lý Danh Mục Xe – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$list=$db->query("SELECT d.*,COUNT(x.id) AS so_xe FROM danh_muc_xe d LEFT JOIN xe_oto x ON x.id_danh_muc=d.id GROUP BY d.id ORDER BY d.ten_danh_muc")->fetchAll();
$edit=null;
if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM danh_muc_xe WHERE id=? LIMIT 1");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title><link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>📂 Quản lý Danh Mục Xe</h1><button class="btn btn-primary" onclick="openModal('modal-add')">+ Thêm danh mục</button></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>
<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Tên danh mục</th><th>Số xe</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-light);">Chưa có danh mục</td></tr>
    <?php else: foreach($list as $i=>$dm):?>
    <tr>
        <td style="color:var(--text-light);"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($dm['ten_danh_muc'])?></strong></td>
        <td><span class="badge badge-blue"><?=$dm['so_xe']?> xe</span></td>
        <td><span class="badge <?=$dm['trang_thai']==='hoạt động'?'badge-green':'badge-gray'?>"><?=htmlspecialchars($dm['trang_thai'])?></span></td>
        <td style="font-size:.82rem;"><?=date('d/m/Y',strtotime($dm['ngay_tao']))?></td>
        <td><div class="table-actions">
            <a href="?edit=<?=$dm['id']?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
            <form method="POST" action="../api/danh_muc_xe.php?action=delete" onsubmit="return confirm('Xóa danh mục này?')">
                <input type="hidden" name="id" value="<?=$dm['id']?>">
                <button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
</table>
</div></div>
</div></div>

<div id="modal-add" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-add')">
<div class="modal"><div class="modal-header"><h3>➕ Thêm Danh Mục</h3><button class="modal-close" onclick="closeModal('modal-add')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/danh_muc_xe.php?action=add">
    <div class="form-group"><label class="lbl">Tên danh mục <span class="req">*</span></label><input type="text" name="ten_danh_muc" required></div>
    <div class="form-group"><label class="lbl">Mô tả</label><textarea name="mo_ta"></textarea></div>
    <div class="form-group"><label class="lbl">Trạng thái</label>
        <select name="trang_thai"><option value="hoạt động">Hoạt động</option><option value="không hoạt động">Không hoạt động</option></select></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div></div>

<?php if($edit):?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')">
<div class="modal"><div class="modal-header"><h3>✏️ Sửa: <?=htmlspecialchars($edit['ten_danh_muc'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/danh_muc_xe.php?action=update">
    <input type="hidden" name="id" value="<?=$edit['id']?>">
    <div class="form-group"><label class="lbl">Tên danh mục <span class="req">*</span></label><input type="text" name="ten_danh_muc" value="<?=htmlspecialchars($edit['ten_danh_muc'])?>" required></div>
    <div class="form-group"><label class="lbl">Mô tả</label><textarea name="mo_ta"><?=htmlspecialchars($edit['mo_ta']??'')?></textarea></div>
    <div class="form-group"><label class="lbl">Trạng thái</label>
        <select name="trang_thai">
            <option value="hoạt động" <?=$edit['trang_thai']==='hoạt động'?'selected':''?>>Hoạt động</option>
            <option value="không hoạt động" <?=$edit['trang_thai']==='không hoạt động'?'selected':''?>>Không hoạt động</option>
        </select></div>
    <div class="modal-footer"><a href="danh_muc_xe.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div></div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif;?>
<script>function openModal(id){document.getElementById(id).classList.add('open');}function closeModal(id){document.getElementById(id).classList.remove('open');}</script>
</body></html>
