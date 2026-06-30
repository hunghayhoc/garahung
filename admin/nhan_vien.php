<?php
// admin/nhan_vien.php
$page_title = 'Quản lý Nhân Viên – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
$search = trim($_GET['search'] ?? '');
$tt_filter = trim($_GET['trang_thai'] ?? '');
$page = max(1,(int)($_GET['page']??1)); $limit=20; $offset=($page-1)*$limit;

$where=[]; $params=[];
if($search!==''){$where[]="(ho_ten LIKE ? OR email LIKE ? OR chuc_vu LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
if($tt_filter!==''){$where[]="trang_thai=?";$params[]=$tt_filter;}
$w=$where?'WHERE '.implode(' AND ',$where):'';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM nhan_vien $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT * FROM nhan_vien $w ORDER BY ngay_tao DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();
$edit=null;
if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM nhan_vien WHERE id=? LIMIT 1");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$page_title?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">

<div class="page-header">
    <h1>👔 Quản lý Nhân Viên</h1>
    <button class="btn btn-primary" onclick="openModal('modal-add')">+ Thêm nhân viên</button>
</div>

<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Tìm tên, email, chức vụ..." value="<?=htmlspecialchars($search)?>">
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="hoạt động" <?=$tt_filter==='hoạt động'?'selected':''?>>Hoạt động</option>
        <option value="nghỉ việc" <?=$tt_filter==='nghỉ việc'?'selected':''?>>Nghỉ việc</option>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
    <a href="nhan_vien.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> nhân viên</span>
</form>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Chức vụ</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?>
        <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-light);">Không có dữ liệu</td></tr>
    <?php else: foreach($list as $i=>$nv):?>
    <tr>
        <td style="color:var(--text-light);"><?=$offset+$i+1?></td>
        <td><strong><?=htmlspecialchars($nv['ho_ten'])?></strong>
            <?php if((int)$nv['id']===(int)$_SESSION['nv_id']):?><span class="badge badge-blue" style="margin-left:4px;">Bạn</span><?php endif;?>
        </td>
        <td><?=htmlspecialchars($nv['email'])?></td>
        <td><?=htmlspecialchars($nv['so_dien_thoai']??'—')?></td>
        <td><span class="badge badge-blue"><?=htmlspecialchars($nv['chuc_vu']??'—')?></span></td>
        <td><span class="badge <?=$nv['trang_thai']==='hoạt động'?'badge-green':'badge-gray'?>"><?=htmlspecialchars($nv['trang_thai'])?></span></td>
        <td style="font-size:.82rem;"><?=date('d/m/Y',strtotime($nv['ngay_tao']))?></td>
        <td><div class="table-actions">
            <a href="?edit=<?=$nv['id']?>&search=<?=urlencode($search)?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
            <?php if((int)$nv['id']!==(int)$_SESSION['nv_id']):?>
            <form method="POST" action="../api/nhan_vien.php?action=delete" onsubmit="return confirm('Xóa nhân viên này?')">
                <input type="hidden" name="id" value="<?=$nv['id']?>">
                <button class="btn btn-danger btn-sm">🗑 Xóa</button>
            </form>
            <?php endif;?>
        </div></td>
    </tr>
    <?php endforeach; endif;?>
    </tbody>
</table>
</div></div>

<?php if($pages>1):?>
<div class="pagination">
    <?php if($page>1):?><a href="?page=<?=$page-1?>&search=<?=urlencode($search)?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span>
        <?php else:?><a href="?page=<?=$p?>&search=<?=urlencode($search)?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>&search=<?=urlencode($search)?>">»</a><?php endif;?>
</div>
<?php endif;?>
</div></div>

<!-- Modal Thêm -->
<div id="modal-add" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-add')">
<div class="modal">
    <div class="modal-header"><h3>➕ Thêm Nhân Viên Mới</h3><button class="modal-close" onclick="closeModal('modal-add')">×</button></div>
    <div class="modal-body">
    <form method="POST" action="../api/nhan_vien.php?action=add">
        <div class="form-row">
            <div class="form-group"><label class="lbl">Họ tên <span class="req">*</span></label><input type="text" name="ho_ten" required></div>
            <div class="form-group"><label class="lbl">Email <span class="req">*</span></label><input type="email" name="email" required></div>
            <div class="form-group"><label class="lbl">Số điện thoại</label><input type="text" name="so_dien_thoai"></div>
            <div class="form-group"><label class="lbl">Chức vụ</label>
                <select name="chuc_vu">
                    <option value="Nhân viên bán hàng">Nhân viên bán hàng</option>
                    <option value="Kỹ thuật viên">Kỹ thuật viên</option>
                    <option value="Tư vấn viên">Tư vấn viên</option>
                    <option value="Quản lý">Quản lý</option>
                </select></div>
            <div class="form-group"><label class="lbl">Mật khẩu <span class="req">*</span></label><input type="password" name="mat_khau" required></div>
            <div class="form-group"><label class="lbl">Xác nhận MK <span class="req">*</span></label><input type="password" name="xac_nhan_mk" required></div>
            <div class="form-group"><label class="lbl">Trạng thái</label>
                <select name="trang_thai"><option value="hoạt động">Hoạt động</option><option value="nghỉ việc">Nghỉ việc</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
    </form>
    </div>
</div></div>

<!-- Modal Sửa -->
<?php if($edit):?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')">
<div class="modal">
    <div class="modal-header"><h3>✏️ Sửa Nhân Viên: <?=htmlspecialchars($edit['ho_ten'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
    <div class="modal-body">
    <form method="POST" action="../api/nhan_vien.php?action=update">
        <input type="hidden" name="id" value="<?=$edit['id']?>">
        <div class="form-row">
            <div class="form-group"><label class="lbl">Họ tên <span class="req">*</span></label><input type="text" name="ho_ten" value="<?=htmlspecialchars($edit['ho_ten'])?>" required></div>
            <div class="form-group"><label class="lbl">Email <span class="req">*</span></label><input type="email" name="email" value="<?=htmlspecialchars($edit['email'])?>" required></div>
            <div class="form-group"><label class="lbl">Số điện thoại</label><input type="text" name="so_dien_thoai" value="<?=htmlspecialchars($edit['so_dien_thoai']??'')?>"></div>
            <div class="form-group"><label class="lbl">Chức vụ</label>
                <select name="chuc_vu">
                    <?php foreach(['Nhân viên bán hàng','Kỹ thuật viên','Tư vấn viên','Quản lý'] as $cv):?>
                    <option value="<?=$cv?>" <?=($edit['chuc_vu']??'')===$cv?'selected':''?>><?=$cv?></option>
                    <?php endforeach;?>
                </select></div>
            <div class="form-group"><label class="lbl">Mật khẩu mới <small style="color:#999;">(để trống = giữ nguyên)</small></label><input type="password" name="mat_khau_moi"></div>
            <div class="form-group"><label class="lbl">Xác nhận MK mới</label><input type="password" name="xac_nhan_mk"></div>
            <div class="form-group"><label class="lbl">Trạng thái</label>
                <select name="trang_thai">
                    <option value="hoạt động" <?=$edit['trang_thai']==='hoạt động'?'selected':''?>>Hoạt động</option>
                    <option value="nghỉ việc" <?=$edit['trang_thai']==='nghỉ việc'?'selected':''?>>Nghỉ việc</option>
                </select></div>
        </div>
        <div class="modal-footer"><a href="nhan_vien.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button></div>
    </form>
    </div>
</div></div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif;?>

<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
</script>
</body></html>
