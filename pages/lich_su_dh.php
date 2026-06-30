<?php
// pages/lich_su_dh.php – Lịch sử đơn hàng khách hàng
session_start();
require_once '../includes/auth_check.php';
requireLogin('../pages/login.php');
if(empty($_SESSION['user_id'])){header("Location: login.php");exit;}
require_once '../config/db.php';

$page_title='Lịch sử đơn hàng – GaraHung';
require_once '../includes/header.php';

$id_kh=(int)$_SESSION['user_id'];
$error=htmlspecialchars($_GET['error']??'');$msg=htmlspecialchars($_GET['msg']??'');
$page=max(1,(int)($_GET['page']??1));$limit=10;$offset=($page-1)*$limit;
$total_stmt=$db->prepare("SELECT COUNT(*) FROM don_hang WHERE id_khach_hang=?");$total_stmt->execute([$id_kh]);
$total=(int)$total_stmt->fetchColumn();$pages=max(1,(int)ceil($total/$limit));
$stmt=$db->prepare("SELECT d.*,GROUP_CONCAT(x.ten_xe SEPARATOR ', ') AS ten_xe_list FROM don_hang d LEFT JOIN chi_tiet_don_hang c ON c.id_don_hang=d.id LEFT JOIN xe_oto x ON x.id=c.id_xe WHERE d.id_khach_hang=? GROUP BY d.id ORDER BY d.ngay_dat_hang DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$id_kh]);$list=$stmt->fetchAll();
$tt_map=['chờ xác nhận'=>'badge-yellow','đã xác nhận'=>'badge-blue','đang giao'=>'badge-purple','hoàn thành'=>'badge-green','hủy'=>'badge-red'];
?>
<div class="container">
<?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
<?php if($msg):?><div class="alert alert-success">✅ <?=$msg?></div><?php endif;?>
<div class="page-header"><h1>🛒 Lịch sử đơn hàng</h1><a href="xe.php" class="btn btn-primary">🚗 Tiếp tục mua xe</a></div>

<?php if(empty($list)):?>
<div class="card"><div class="card-body" style="text-align:center;padding:48px;">
    <div style="font-size:3rem;margin-bottom:12px;">🛒</div>
    <h3 style="color:var(--primary);margin-bottom:8px;">Chưa có đơn hàng nào</h3>
    <p style="color:var(--text-light);">Hãy khám phá các mẫu xe của chúng tôi!</p>
    <a href="xe.php" class="btn btn-primary" style="margin-top:16px;">Xem xe ngay</a>
</div></div>
<?php else:?>
<?php foreach($list as $dh):?>
<div class="card">
<div class="card-body" style="padding:18px 24px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <strong style="color:var(--primary);font-size:1rem;"><?=htmlspecialchars($dh['ma_don_hang'])?></strong>
                <span class="badge <?=$tt_map[$dh['trang_thai']]??'badge-gray'?>"><?=htmlspecialchars($dh['trang_thai'])?></span>
            </div>
            <div style="font-size:.88rem;color:var(--text-light);">🚗 <?=htmlspecialchars($dh['ten_xe_list']??'—')?></div>
            <div style="font-size:.82rem;color:var(--text-light);margin-top:3px;">📅 <?=date('d/m/Y H:i',strtotime($dh['ngay_dat_hang']))?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.2rem;font-weight:800;color:var(--accent);"><?=number_format($dh['tong_tien'])?> ₫</div>
            <div style="font-size:.82rem;color:var(--text-light);"><?=htmlspecialchars($dh['phuong_thuc_thanh_toan']??'')?></div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                <a href="don_hang_ct.php?id=<?=$dh['id']?>" class="btn btn-outline btn-sm">👁 Chi tiết</a>
                <?php if($dh['trang_thai']==='chờ xác nhận'):?>
                <form method="POST" action="../api/don_hang.php?action=update_status" onsubmit="return confirm('Hủy đơn này?')">
                    <input type="hidden" name="id" value="<?=$dh['id']?>"><input type="hidden" name="trang_thai" value="hủy"><input type="hidden" name="ref" value="user">
                    <button class="btn btn-danger btn-sm">✗ Hủy</button>
                </form>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>
</div>
<?php endforeach;?>
<?php if($pages>1):?>
<div class="pagination">
    <?php if($page>1):?><a href="?page=<?=$page-1?>">«</a><?php endif;?>
    <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?>
        <?php if($p===$page):?><span class="active"><?=$p?></span><?php else:?><a href="?page=<?=$p?>"><?=$p?></a><?php endif;?>
    <?php endfor;?>
    <?php if($page<$pages):?><a href="?page=<?=$page+1?>">»</a><?php endif;?>
</div>
<?php endif;?>
<?php endif;?>
</div>
<?php require_once '../includes/footer.php'; ?>
