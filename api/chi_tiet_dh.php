<?php
// api/chi_tiet_dh.php – API chi tiết đơn hàng
session_start();
require_once '../config/db.php';

if(empty($_SESSION['user_id'])&&empty($_SESSION['nv_id'])){
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

header('Content-Type: application/json');
$id_don = (int)($_GET['id_don'] ?? 0);
if($id_don<=0){ echo json_encode(['success'=>false,'message'=>'ID không hợp lệ']); exit; }

// Khách hàng chỉ xem đơn của mình
if(!empty($_SESSION['user_id'])){
    $check=$db->prepare("SELECT id FROM don_hang WHERE id=? AND id_khach_hang=? LIMIT 1");
    $check->execute([$id_don,$_SESSION['user_id']]);
    if(!$check->fetch()){ echo json_encode(['success'=>false,'message'=>'Không có quyền']); exit; }
}

$stmt=$db->prepare("SELECT c.*,x.ten_xe,x.ma_xe,x.hinh_anh_chinh,h.ten_hang FROM chi_tiet_don_hang c LEFT JOIN xe_oto x ON x.id=c.id_xe LEFT JOIN hang_xe h ON h.id=x.id_hang WHERE c.id_don_hang=?");
$stmt->execute([$id_don]);
echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
