<?php
// admin/khach_hang.php
$page_title = 'Quản lý Khách Hàng – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
$search = trim($_GET['search'] ?? '');
$tt_filter = trim($_GET['trang_thai'] ?? '');
$page  = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;

$where=[]; $params=[];
if ($search!=='') { $where[]="(ho_ten LIKE ? OR email LIKE ? OR so_dien_thoai LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
if ($tt_filter!=='') { $where[]="trang_thai=?"; $params[]=$tt_filter; }
$w = $where ? 'WHERE '.implode(' AND ',$where) : '';
$total_stmt=$db->prepare("SELECT COUNT(*) FROM khach_hang $w"); $total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn(); $pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT * FROM khach_hang $w ORDER BY ngay_dang_ky DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params); $list=$stmt->fetchAll();

// Edit target
$edit = null;
if (!empty($_GET['edit'])) {
    $s=$db->prepare("SELECT * FROM khach_hang WHERE id=? LIMIT 1"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch();
}
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head><body>
<div style="display:flex;">
<?php include '../includes/admin_sidebar.php'; ?>
<div class="admin-content" style="flex:1;">

<div class="page-header">
    <h1>👥 Quản lý Khách Hàng</h1>
</div>

<?php if($error): ?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif; ?>
<?php if($msg):   ?><div class="alert alert-success">✅ <?=$msg?></div><?php endif; ?>

<!-- Tìm kiếm -->
<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Tìm tên, email, SĐT..." value="<?=htmlspecialchars($search)?>">
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="hoạt động" <?=$tt_filter==='hoạt động'?'selected':''?>>Hoạt động</option>
        <option value="khóa" <?=$tt_filter==='khóa'?'selected':''?>>Khóa</option>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
    <a href="khach_hang.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> khách hàng</span>
</form>

<!-- Bảng danh sách -->
<div class="card">
<div class="card-body p0 table-wrap">
<table>
    <thead><tr>
        <th>#</th><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Giới tính</th>
        <th>Điểm</th><th>Trạng thái</th><th>Ngày đăng ký</th><th>Thao tác</th>
    </tr></thead>
    <tbody>
    <?php if(empty($list)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:var(--text-light);">Không có dữ liệu</td></tr>
    <?php else: ?>
    <?php foreach($list as $i=>$kh): ?>
    <tr>
        <td style="color:var(--text-light);"><?=$offset+$i+1?></td>
        <td><strong><?=htmlspecialchars($kh['ho_ten'])?></strong></td>
        <td><?=htmlspecialchars($kh['email'])?></td>
        <td><?=htmlspecialchars($kh['so_dien_thoai']??'—')?></td>
        <td><?=htmlspecialchars($kh['gioi_tinh']??'—')?></td>
        <td><span style="color:var(--warning);font-weight:700;">⭐<?=number_format($kh['diem_tich_luy'])?></span></td>
        <td><span class="badge <?=$kh['trang_thai']==='hoạt động'?'badge-green':'badge-red'?>"><?=htmlspecialchars($kh['trang_thai'])?></span></td>
        <td style="font-size:.82rem;"><?=date('d/m/Y',strtotime($kh['ngay_dang_ky']))?></td>
        <td>
            <div class="table-actions">
                <a href="?edit=<?=$kh['id']?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>&page=<?=$page?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
                <form method="POST" action="../api/khach_hang.php?action=admin_delete" onsubmit="return confirm('Xóa khách hàng này?')">
                    <input type="hidden" name="id" value="<?=$kh['id']?>">
                    <button class="btn btn-danger btn-sm">🗑 Xóa</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<!-- Pagination -->
<?php if($pages>1): ?>
<div class="pagination">
    <?php if($page>1): ?><a href="?page=<?=$page-1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>">«</a><?php endif; ?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
        <?php if($p===$page): ?><span class="active"><?=$p?></span>
        <?php else: ?><a href="?page=<?=$p?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>"><?=$p?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if($page<$pages): ?><a href="?page=<?=$page+1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>">»</a><?php endif; ?>
</div>
<?php endif; ?>

</div>
</div>

<!-- Modal Sửa -->
<?php if($edit): ?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')">
<div class="modal">
    <div class="modal-header"><h3>✏️ Sửa Khách Hàng: <?=htmlspecialchars($edit['ho_ten'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
    <div class="modal-body">
    <form method="POST" action="../api/khach_hang.php?action=admin_update">
        <input type="hidden" name="id" value="<?=$edit['id']?>">
        <div class="form-row">
            <div class="form-group"><label class="lbl">Họ tên <span class="req">*</span></label><input type="text" name="ho_ten" value="<?=htmlspecialchars($edit['ho_ten'])?>" required></div>
            <div class="form-group"><label class="lbl">Email <span class="req">*</span></label><input type="email" name="email" value="<?=htmlspecialchars($edit['email'])?>" required></div>
            <div class="form-group"><label class="lbl">Số điện thoại</label><input type="text" name="so_dien_thoai" value="<?=htmlspecialchars($edit['so_dien_thoai']??'')?>"></div>
            <div class="form-group"><label class="lbl">Ngày sinh</label><input type="date" name="ngay_sinh" value="<?=htmlspecialchars($edit['ngay_sinh']??'')?>"></div>
            <div class="form-group"><label class="lbl">Giới tính</label>
                <select name="gioi_tinh"><option value="">-- Chọn --</option>
                <?php foreach(['Nam','Nữ','Khác'] as $gt): ?>
                <option value="<?=$gt?>" <?=($edit['gioi_tinh']??'')===$gt?'selected':''?>><?=$gt?></option>
                <?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="lbl">Điểm tích lũy</label><input type="number" name="diem_tich_luy" value="<?=(int)$edit['diem_tich_luy']?>" min="0"></div>
            <div class="form-group"><label class="lbl">Trạng thái</label>
                <select name="trang_thai">
                <option value="hoạt động" <?=$edit['trang_thai']==='hoạt động'?'selected':''?>>Hoạt động</option>
                <option value="khóa" <?=$edit['trang_thai']==='khóa'?'selected':''?>>Khóa</option>
                </select></div>
            <div class="form-group span2"><label class="lbl">Địa chỉ</label><textarea name="dia_chi"><?=htmlspecialchars($edit['dia_chi']??'')?></textarea></div>
        </div>
        <div class="modal-footer"><a href="khach_hang.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button></div>
    </form>
    </div>
</div>
</div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif; ?>

<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
</script>
</body></html>
