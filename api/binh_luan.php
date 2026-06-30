<?php
// api/binh_luan.php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'add' && empty($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?error=Bạn cần đăng nhập để bình luận."); exit;
}
if (in_array($action,['update_status','delete']) && ($_SESSION['role']??'')!=='admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền."); exit;
}

switch ($action) {

    case 'add':
        if ($method!=='POST') { header("Location: ../pages/xe_detail.php?error=Phương thức không hợp lệ."); exit; }
        $id_xe   = (int)($_POST['id_xe']   ?? 0);
        $id_kh   = (int)$_SESSION['user_id'];
        $noi_dung= trim($_POST['noi_dung'] ?? '');
        $dg      = (int)($_POST['danh_gia'] ?? 5);
        if ($id_xe<=0||$noi_dung==='') { header("Location: ../pages/xe_detail.php?id=$id_xe&error=Vui lòng nhập nội dung bình luận."); exit; }
        $dg = max(1, min(5, $dg));
        $db->prepare("INSERT INTO binh_luan (id_xe,id_khach_hang,noi_dung,danh_gia) VALUES (?,?,?,?)")->execute([$id_xe,$id_kh,$noi_dung,$dg]);
        header("Location: ../pages/xe_detail.php?id=$id_xe&msg=Bình luận đã được gửi, chờ duyệt."); exit;

    case 'update_status':
        if ($method!=='POST') { header("Location: ../admin/binh_luan.php?error=Phương thức không hợp lệ."); exit; }
        $id=$id=(int)($_POST['id']??0); $tt=trim($_POST['trang_thai']??'');
        if (!in_array($tt,['phê duyệt','chờ duyệt','ẩn'])) { header("Location: ../admin/binh_luan.php?error=Trạng thái không hợp lệ."); exit; }
        $db->prepare("UPDATE binh_luan SET trang_thai=? WHERE id=?")->execute([$tt,$id]);
        header("Location: ../admin/binh_luan.php?msg=Cập nhật bình luận thành công!"); exit;

    case 'delete':
        if ($method!=='POST') { header("Location: ../admin/binh_luan.php?error=Phương thức không hợp lệ."); exit; }
        $id=(int)($_POST['id']??0);
        $db->prepare("DELETE FROM binh_luan WHERE id=?")->execute([$id]);
        header("Location: ../admin/binh_luan.php?msg=Xóa bình luận thành công!"); exit;

    case 'list_xe':
        header('Content-Type: application/json');
        $id_xe=(int)($_GET['id_xe']??0); $page=max(1,(int)($_GET['page']??1)); $limit=10; $offset=($page-1)*$limit;
        $tt=trim($_GET['trang_thai']??'phê duyệt');
        $total=$db->prepare("SELECT COUNT(*) FROM binh_luan WHERE id_xe=? AND trang_thai=?"); $total->execute([$id_xe,$tt]); $total=(int)$total->fetchColumn();
        $stmt=$db->prepare("SELECT b.*,k.ho_ten FROM binh_luan b LEFT JOIN khach_hang k ON k.id=b.id_khach_hang WHERE b.id_xe=? AND b.trang_thai=? ORDER BY b.ngay_tao DESC LIMIT $limit OFFSET $offset");
        $stmt->execute([$id_xe,$tt]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total]); exit;

    default:
        header("Location: ../admin/binh_luan.php?error=Action không hợp lệ."); exit;
}
