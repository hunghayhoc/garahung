<?php
// admin/hang_xe.php
$page_title = 'Quản lý Hãng Xe – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$stmt=$db->query("SELECT h.*,COUNT(x.id) AS so_xe FROM hang_xe h LEFT JOIN xe_oto x ON x.id_hang=h.id GROUP BY h.id ORDER BY h.ten_hang");
$list=$stmt->fetchAll();
$edit=null;
if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM hang_xe WHERE id=? LIMIT 1");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">
<div class="page-header"><h1>🏭 Quản lý Hãng Xe</h1><button class="btn btn-primary" onclick="openModal('modal-add')">+ Thêm hãng xe</button></div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>
<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Tên hãng</th><th>Quốc gia</th><th>Số xe</th><th>Mô tả</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?><tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-light);">Không có hãng xe nào</td></tr>
    <?php else: foreach($list as $i=>$h):?>
    <tr>
        <td style="color:var(--text-light);"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($h['ten_hang'])?></strong></td>
        <td><?=htmlspecialchars($h['quoc_gia']??'—')?></td>
        <td><span class="badge badge-blue"><?=$h['so_xe']?> xe</span></td>
        <td style="max-width:220px;font-size:.85rem;color:var(--text-light);"><?=htmlspecialchars(mb_substr($h['mo_ta']??'—',0,60)).'...'?></td>
        <td><div class="table-actions">
            <a href="?edit=<?=$h['id']?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
            <form method="POST" action="../api/hang_xe.php?action=delete" onsubmit="return confirm('Xóa hãng xe này?')">
                <input type="hidden" name="id" value="<?=$h['id']?>">
                <button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
</table>
</div></div>
</div></div>

<!-- Modal Thêm -->
<div id="modal-add" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-add')">
<div class="modal"><div class="modal-header"><h3>➕ Thêm Hãng Xe</h3><button class="modal-close" onclick="closeModal('modal-add')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/hang_xe.php?action=add">
    <div class="form-group"><label class="lbl">Tên hãng <span class="req">*</span></label><input type="text" name="ten_hang" required></div>
    <div class="form-group"><label class="lbl">Quốc gia</label><input type="text" name="quoc_gia" placeholder="Nhật Bản, Hàn Quốc..."></div>
    <div class="form-group"><label class="lbl">Logo (URL/tên file)</label><input type="text" name="logo"></div>
    <div class="form-group"><label class="lbl">Mô tả</label><textarea name="mo_ta"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div></div>

<!-- Modal Sửa -->
<?php if($edit):?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')">
<div class="modal"><div class="modal-header"><h3>✏️ Sửa Hãng: <?=htmlspecialchars($edit['ten_hang'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
<div class="modal-body"><form method="POST" action="../api/hang_xe.php?action=update">
    <input type="hidden" name="id" value="<?=$edit['id']?>">
    <div class="form-group"><label class="lbl">Tên hãng <span class="req">*</span></label><input type="text" name="ten_hang" value="<?=htmlspecialchars($edit['ten_hang'])?>" required></div>
    <div class="form-group"><label class="lbl">Quốc gia</label><input type="text" name="quoc_gia" value="<?=htmlspecialchars($edit['quoc_gia']??'')?>"></div>
    <div class="form-group"><label class="lbl">Logo</label><input type="text" name="logo" value="<?=htmlspecialchars($edit['logo']??'')?>"></div>
    <div class="form-group"><label class="lbl">Mô tả</label><textarea name="mo_ta"><?=htmlspecialchars($edit['mo_ta']??'')?></textarea></div>
    <div class="modal-footer"><a href="hang_xe.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button></div>
</form></div></div></div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif;?>
<script>function openModal(id){document.getElementById(id).classList.add('open');}function closeModal(id){document.getElementById(id).classList.remove('open');}</script>
</body></html>
