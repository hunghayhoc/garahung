<?php
// admin/xe_oto.php
$page_title = 'Quản lý Xe Ô Tô – GaraHung';
session_start();
require_once '../includes/auth_check.php';
requireAdmin('../pages/login.php');
require_once '../config/db.php';

$error = htmlspecialchars($_GET['error'] ?? '');
$msg   = htmlspecialchars($_GET['msg']   ?? '');
$search = trim($_GET['search'] ?? '');
$tt_filter = trim($_GET['trang_thai'] ?? '');
$hang_filter = (int)($_GET['id_hang'] ?? 0);
$page = max(1,(int)($_GET['page']??1)); $limit=20; $offset=($page-1)*$limit;

$where=["1=1"]; $params=[];
if($search!==''){$where[]="(x.ten_xe LIKE ? OR x.ma_xe LIKE ? OR h.ten_hang LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
if($tt_filter!==''){$where[]="x.trang_thai=?";$params[]=$tt_filter;}
if($hang_filter>0){$where[]="x.id_hang=?";$params[]=$hang_filter;}
$w='WHERE '.implode(' AND ',$where);
$total_stmt=$db->prepare("SELECT COUNT(*) FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang $w");$total_stmt->execute($params);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT x.*,h.ten_hang,d.ten_danh_muc FROM xe_oto x LEFT JOIN hang_xe h ON h.id=x.id_hang LEFT JOIN danh_muc_xe d ON d.id=x.id_danh_muc $w ORDER BY x.ngay_tao DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);$list=$stmt->fetchAll();

$hang_list=$db->query("SELECT id,ten_hang FROM hang_xe ORDER BY ten_hang")->fetchAll();
$dm_list=$db->query("SELECT id,ten_danh_muc FROM danh_muc_xe ORDER BY ten_danh_muc")->fetchAll();
$xe_oto_anh_enabled = false;
try {
    $db->query("SELECT 1 FROM xe_oto_anh LIMIT 1");
    $xe_oto_anh_enabled = true;
} catch (Throwable $e) {
    $xe_oto_anh_enabled = false;
}
$edit=null;
if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM xe_oto WHERE id=? LIMIT 1");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$edit_images = [];
if ($edit && $xe_oto_anh_enabled) {
    $img_stmt = $db->prepare("SELECT id, filename, is_chinh FROM xe_oto_anh WHERE id_xe=? ORDER BY is_chinh DESC, thu_tu ASC, id ASC");
    $img_stmt->execute([(int)$edit['id']]);
    $edit_images = $img_stmt->fetchAll();
}
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
    <h1>🚗 Quản lý Xe Ô Tô</h1>
    <button class="btn btn-primary" onclick="openModal('modal-add')">+ Thêm xe mới</button>
</div>
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>

<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Tên xe, mã xe, hãng..." value="<?=htmlspecialchars($search)?>">
    <select name="id_hang">
        <option value="">-- Tất cả hãng --</option>
        <?php foreach($hang_list as $h):?><option value="<?=$h['id']?>" <?=$hang_filter===$h['id']?'selected':''?>><?=htmlspecialchars($h['ten_hang'])?></option><?php endforeach;?>
    </select>
    <select name="trang_thai">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="còn hàng" <?=$tt_filter==='còn hàng'?'selected':''?>>Còn hàng</option>
        <option value="hết hàng" <?=$tt_filter==='hết hàng'?'selected':''?>>Hết hàng</option>
        <option value="đang bảo dưỡng" <?=$tt_filter==='đang bảo dưỡng'?'selected':''?>>Đang bảo dưỡng</option>
    </select>
    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
    <a href="xe_oto.php" class="btn btn-outline">Xóa lọc</a>
    <span style="margin-left:auto;font-size:.88rem;color:var(--text-light);">Tổng: <strong><?=number_format($total)?></strong> xe</span>
</form>

<div class="card"><div class="card-body p0 table-wrap">
<table>
    <thead><tr><th>#</th><th>Mã xe</th><th>Tên xe</th><th>Hãng</th><th>Danh mục</th><th>Giá bán</th><th>Nhiên liệu</th><th>Năm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if(empty($list)):?>
        <tr><td colspan="10" style="text-align:center;padding:28px;color:var(--text-light);">Không có dữ liệu</td></tr>
    <?php else: foreach($list as $i=>$xe):
        $gia_hien=$xe['gia_khuyen_mai']??$xe['gia_ban'];
        $tt_map=['còn hàng'=>'badge-green','hết hàng'=>'badge-red','đang bảo dưỡng'=>'badge-yellow'];
    ?>
    <tr>
        <td style="color:var(--text-light);"><?=$offset+$i+1?></td>
        <td><code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;font-size:.82rem;"><?=htmlspecialchars($xe['ma_xe'])?></code></td>
        <td><strong><?=htmlspecialchars($xe['ten_xe'])?></strong><?php if($xe['gia_khuyen_mai']):?><span class="badge badge-red" style="margin-left:4px;font-size:.68rem;">SALE</span><?php endif;?></td>
        <td><?=htmlspecialchars($xe['ten_hang']??'—')?></td>
        <td><?=htmlspecialchars($xe['ten_danh_muc']??'—')?></td>
        <td style="font-weight:700;color:var(--accent);"><?=number_format($gia_hien)?> ₫
            <?php if($xe['gia_khuyen_mai']):?><br><span style="text-decoration:line-through;color:#aaa;font-size:.8rem;font-weight:400;"><?=number_format($xe['gia_ban'])?></span><?php endif;?>
        </td>
        <td><?=htmlspecialchars($xe['nhien_lieu']??'—')?></td>
        <td><?=$xe['nam_san_xuat']?></td>
        <td><span class="badge <?=$tt_map[$xe['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($xe['trang_thai'])?></span></td>
        <td><div class="table-actions">
            <a href="?edit=<?=$xe['id']?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
            <form method="POST" action="../api/xe_oto.php?action=delete" onsubmit="return confirm('Xóa xe này?')">
                <input type="hidden" name="id" value="<?=$xe['id']?>">
                <button class="btn btn-danger btn-sm">🗑</button>
            </form>
        </div></td>
    </tr>
    <?php endforeach; endif;?>
    </tbody>
</table>
</div></div>

<?php if($pages>1):?>
<div class="pagination">
    <?php if($page>1):?><a href="?page=<?=$page-1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>&id_hang=<?=$hang_filter?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span><?php else:?><a href="?page=<?=$p?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>&id_hang=<?=$hang_filter?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_filter)?>&id_hang=<?=$hang_filter?>">»</a><?php endif;?>
</div>
<?php endif;?>

</div></div>

<?php
// Helper: xe form fields
function xe_form_fields($xe, $hang_list, $dm_list, $images, $prefix, $multi_enabled) { ?>
<div class="form-row col3">
    <div class="form-group span2"><label class="lbl">Tên xe <span class="req">*</span></label><input type="text" name="ten_xe" value="<?=htmlspecialchars($xe['ten_xe']??'')?>" required></div>
    <div class="form-group"><label class="lbl">Mã xe <span class="req">*</span></label><input type="text" name="ma_xe" value="<?=htmlspecialchars($xe['ma_xe']??'')?>" required></div>
    <div class="form-group"><label class="lbl">Hãng xe <span class="req">*</span></label>
        <select name="id_hang" required>
            <option value="">-- Chọn hãng --</option>
            <?php foreach($hang_list as $h):?><option value="<?=$h['id']?>" <?=(($xe['id_hang']??0)===$h['id'])?'selected':''?>><?=htmlspecialchars($h['ten_hang'])?></option><?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl">Danh mục</label>
        <select name="id_danh_muc">
            <option value="">-- Chọn danh mục --</option>
            <?php foreach($dm_list as $d):?><option value="<?=$d['id']?>" <?=(($xe['id_danh_muc']??0)===$d['id'])?'selected':''?>><?=htmlspecialchars($d['ten_danh_muc'])?></option><?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl">Giá bán (₫) <span class="req">*</span></label><input type="number" name="gia_ban" value="<?=$xe['gia_ban']??''?>" min="0" step="1000000" required></div>
    <div class="form-group"><label class="lbl">Giá khuyến mãi (₫)</label><input type="number" name="gia_khuyen_mai" value="<?=$xe['gia_khuyen_mai']??''?>" min="0" step="1000000"></div>
    <div class="form-group"><label class="lbl">Năm sản xuất</label><input type="number" name="nam_san_xuat" value="<?=$xe['nam_san_xuat']??date('Y')?>" min="2000" max="<?=date('Y')+1?>"></div>
    <div class="form-group"><label class="lbl">Nhiên liệu</label>
        <select name="nhien_lieu">
            <?php foreach(['Xăng','Dầu','Điện','Hybrid'] as $nl):?><option value="<?=$nl?>" <?=(($xe['nhien_lieu']??'Xăng')===$nl)?'selected':''?>><?=$nl?></option><?php endforeach;?>
        </select></div>
    <div class="form-group"><label class="lbl">Số chỗ ngồi</label><input type="number" name="so_cho_ngoi" value="<?=$xe['so_cho_ngoi']??5?>" min="2" max="50"></div>
    <div class="form-group"><label class="lbl">Hộp số</label>
        <select name="hop_so">
            <option value="Số tự động" <?=(($xe['hop_so']??'')==='Số tự động')?'selected':''?>>Số tự động</option>
            <option value="Số sàn" <?=(($xe['hop_so']??'')==='Số sàn')?'selected':''?>>Số sàn</option>
        </select></div>
    <div class="form-group"><label class="lbl">Trạng thái</label>
        <select name="trang_thai">
            <?php foreach(['còn hàng','hết hàng','đang bảo dưỡng'] as $tt):?><option value="<?=$tt?>" <?=(($xe['trang_thai']??'còn hàng')===$tt)?'selected':''?>><?=$tt?></option><?php endforeach;?>
        </select></div>
    <div class="form-group">
        <label class="lbl">Ảnh xe (upload nhiều ảnh)</label>
        <input type="file" name="hinh_anh_files[]" multiple accept="image/*">
        <?php if($multi_enabled):?>
            <div style="margin-top:6px;font-size:.82rem;color:var(--text-light);line-height:1.35;">
                Ảnh sẽ được lưu vào <code>assets/uploads/</code>. Bạn có thể upload nhiều ảnh cho 1 xe.
            </div>
        <?php else:?>
            <div style="margin-top:6px;font-size:.82rem;color:var(--warning);line-height:1.35;">
                Chưa có bảng <code>xe_oto_anh</code> nên hệ thống chỉ lưu được 1 ảnh chính. Hãy chạy file <code>sql/xe_oto_anh.sql</code> để bật nhiều ảnh.
            </div>
        <?php endif;?>

        <div id="img-selected-preview-<?=htmlspecialchars($prefix)?>" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;"></div>

        <div style="margin-top:16px;">
            <label class="lbl">Chọn ảnh chính từ máy (sẽ copy vào uploads)</label>
            <input type="file" name="hinh_anh_chinh_file" accept="image/*">
            <div id="main-img-preview-<?=htmlspecialchars($prefix)?>" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;"></div>
        </div>

        <?php if(!empty($images)):?>
            <div style="margin-top:12px;">
                <label class="lbl">Ảnh chính</label>
                <select name="hinh_anh_chinh">
                    <option value="">-- Giữ nguyên --</option>
                    <?php foreach($images as $im):?>
                        <option value="<?=htmlspecialchars($im['filename'])?>" <?=(($xe['hinh_anh_chinh']??'')===$im['filename'])?'selected':''?>>
                            <?=htmlspecialchars($im['filename'])?><?=!empty($im['is_chinh'])?' (chính)':''?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>

            <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                <?php foreach($images as $im):?>
                    <div style="width:120px;">
                        <div style="width:120px;height:80px;border:1px solid #eee;border-radius:10px;overflow:hidden;background:#fafafa;">
                            <img src="../assets/uploads/<?=htmlspecialchars($im['filename'])?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div style="display:flex;gap:6px;margin-top:6px;align-items:center;">
                            <?php if(!empty($im['is_chinh'])):?>
                                <span class="badge badge-blue" style="font-size:.72rem;">Chính</span>
                            <?php endif;?>
                            <button class="btn btn-danger btn-sm" type="button" style="margin-left:auto;"
                                    onclick="deleteXeImage(<?= (int)($xe['id'] ?? 0) ?>, <?= (int)($im['id'] ?? 0) ?>)">
                                Xóa
                            </button>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        <?php endif;?>
    </div>
    <div class="form-group span3"><label class="lbl">Mô tả</label><textarea name="mo_ta"><?=htmlspecialchars($xe['mo_ta']??'')?></textarea></div>
</div>
<?php }
?>

<!-- Modal Thêm -->
<div id="modal-add" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-add')" style="align-items:flex-start;padding:30px 16px;">
<div class="modal" style="max-width:720px;">
    <div class="modal-header"><h3>➕ Thêm Xe Mới</h3><button class="modal-close" onclick="closeModal('modal-add')">×</button></div>
    <div class="modal-body">
    <form method="POST" action="../api/xe_oto.php?action=add" enctype="multipart/form-data">
        <?php xe_form_fields([],$hang_list,$dm_list,[],'add',$xe_oto_anh_enabled);?>
        <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Hủy</button><button type="submit" class="btn btn-primary">💾 Lưu</button></div>
    </form></div>
</div></div>

<!-- Modal Sửa -->
<?php if($edit):?>
<div id="modal-edit" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-edit')" style="align-items:flex-start;padding:30px 16px;">
<div class="modal" style="max-width:720px;">
    <div class="modal-header"><h3>✏️ Sửa Xe: <?=htmlspecialchars($edit['ten_xe'])?></h3><button class="modal-close" onclick="closeModal('modal-edit')">×</button></div>
    <div class="modal-body">
    <form method="POST" action="../api/xe_oto.php?action=update" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?=$edit['id']?>">
        <?php xe_form_fields($edit,$hang_list,$dm_list,$edit_images,'edit',$xe_oto_anh_enabled);?>
        <div class="modal-footer"><a href="xe_oto.php" class="btn btn-outline">Hủy</a><button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button></div>
    </form></div>
</div></div>
<script>document.getElementById('modal-edit').classList.add('open');</script>
<?php endif;?>

<script>
function previewSelectedFiles(prefix){
    const container = document.getElementById('img-selected-preview-' + prefix);
    if (!container) return;
    const form = container.closest('form');
    const input = form ? form.querySelector('input[type="file"][name="hinh_anh_files[]"]') : null;
    if (!input) return;

    container.innerHTML = '';
    const files = Array.from(input.files || []);
    files.slice(0, 12).forEach((f) => {
        const box = document.createElement('div');
        box.style.width = '86px';
        box.style.height = '56px';
        box.style.border = '1px solid #eee';
        box.style.borderRadius = '8px';
        box.style.overflow = 'hidden';
        box.style.background = '#fafafa';

        const img = document.createElement('img');
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.alt = '';
        box.appendChild(img);

        const reader = new FileReader();
        reader.onload = () => { img.src = String(reader.result || ''); };
        reader.readAsDataURL(f);

        container.appendChild(box);
    });
}
function deleteXeImage(idXe, idAnh){
    if(!idXe || !idAnh) return;
    if(!confirm('Xóa ảnh này?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../api/xe_oto.php?action=delete_image';

    const in1 = document.createElement('input');
    in1.type = 'hidden'; in1.name = 'id_xe'; in1.value = String(idXe);
    const in2 = document.createElement('input');
    in2.type = 'hidden'; in2.name = 'id_anh'; in2.value = String(idAnh);
    form.appendChild(in1);
    form.appendChild(in2);
    document.body.appendChild(form);
    form.submit();
}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('input[type=\"file\"][name=\"hinh_anh_files[]\"], input[type=\"file\"][name=\"hinh_anh_chinh_file\"]').forEach((inp) => {
    inp.addEventListener('change', () => {
        const form = inp.closest('form');
        let preview = form ? form.querySelector('[id^=\"img-selected-preview-\"]') : null;
        if (inp.name === 'hinh_anh_chinh_file') {
            preview = form ? form.querySelector('[id^=\"main-img-preview-\"]') : null;
        }
        const prefix = preview && preview.id ? preview.id.replace(/^(img-selected-preview-?|main-img-preview-)/,'') : '';
        if (prefix && previewSelectedFiles) previewSelectedFiles(prefix);
    });
});
</script>
</body></html>
