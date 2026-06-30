<?php
// api/lich_hen.php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (in_array($action, ['create','list_user']) && empty($_SESSION['user_id']) && empty($_SESSION['nv_id'])) {
    header("Location: ../pages/login.php?error=Bạn cần đăng nhập."); exit;
}
if (in_array($action, ['delete','list_all']) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền."); exit;
}

switch ($action) {

    case 'create':
        if ($method !== 'POST') { header("Location: ../pages/lich_hen.php?error=Phương thức không hợp lệ."); exit; }
        $id_kh    = (int)$_SESSION['user_id'];
        $id_xe    = (int)($_POST['id_xe']    ?? 0);
        $ngay_gio = trim($_POST['ngay_gio']  ?? '');
        $dia_diem = trim($_POST['dia_diem']  ?? '') ?: 'Showroom GaraHung';
        $ghi_chu  = trim($_POST['ghi_chu']   ?? '') ?: null;
        if ($id_xe <= 0 || $ngay_gio === '') { header("Location: ../pages/lich_hen.php?error=Vui lòng điền đầy đủ thông tin."); exit; }
        $db->prepare("INSERT INTO lich_hen (id_khach_hang,id_xe,ngay_gio,dia_diem,ghi_chu) VALUES (?,?,?,?,?)")
           ->execute([$id_kh, $id_xe, $ngay_gio, $dia_diem, $ghi_chu]);
        header("Location: ../pages/lich_hen.php?msg=Đặt lịch hẹn thành công! Chúng tôi sẽ xác nhận sớm nhất."); exit;

    case 'update_status':
        if ($method !== 'POST') { header("Location: ../admin/lich_hen.php?error=Phương thức không hợp lệ."); exit; }
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['trang_thai'] ?? '');
        $id_nv  = (int)($_SESSION['nv_id'] ?? 0);
        $valid  = ['chờ xác nhận','đã xác nhận','hoàn thành','hủy'];
        if ($id <= 0 || !in_array($status, $valid)) { header("Location: ../admin/lich_hen.php?error=Dữ liệu không hợp lệ."); exit; }
        if ($id_nv > 0) {
            $db->prepare("UPDATE lich_hen SET trang_thai=?, id_nhan_vien=? WHERE id=?")->execute([$status, $id_nv, $id]);
        } else {
            $db->prepare("UPDATE lich_hen SET trang_thai=? WHERE id=?")->execute([$status, $id]);
        }
        header("Location: ../admin/lich_hen.php?msg=Cập nhật lịch hẹn thành công!"); exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/lich_hen.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header("Location: ../admin/lich_hen.php?error=ID không hợp lệ."); exit; }
        $db->prepare("DELETE FROM lich_hen WHERE id=?")->execute([$id]);
        header("Location: ../admin/lich_hen.php?msg=Xóa lịch hẹn thành công!"); exit;

    case 'list_user':
        header('Content-Type: application/json');
        $id_kh = (int)($_SESSION['user_id'] ?? 0);
        $page  = max(1,(int)($_GET['page'] ?? 1)); $limit=10; $offset=($page-1)*$limit;
        $total = $db->prepare("SELECT COUNT(*) FROM lich_hen WHERE id_khach_hang=?"); $total->execute([$id_kh]); $total=(int)$total->fetchColumn();
        $stmt  = $db->prepare("SELECT l.*,x.ten_xe,x.ma_xe,n.ho_ten AS ten_nv FROM lich_hen l LEFT JOIN xe_oto x ON x.id=l.id_xe LEFT JOIN nhan_vien n ON n.id=l.id_nhan_vien WHERE l.id_khach_hang=? ORDER BY l.ngay_gio DESC LIMIT $limit OFFSET $offset");
        $stmt->execute([$id_kh]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'pages'=>ceil($total/$limit)]); exit;

    case 'list_all':
        header('Content-Type: application/json');
        $page   = max(1,(int)($_GET['page'] ?? 1)); $limit=20; $offset=($page-1)*$limit;
        $search = trim($_GET['search'] ?? ''); $tt=trim($_GET['trang_thai']??'');
        $where=[]; $params=[];
        if ($search!=='') { $where[]="(k.ho_ten LIKE ? OR x.ten_xe LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
        if ($tt!=='')     { $where[]="l.trang_thai=?"; $params[]=$tt; }
        $w = $where ? 'WHERE '.implode(' AND ',$where) : '';
        $total=$db->prepare("SELECT COUNT(*) FROM lich_hen l LEFT JOIN khach_hang k ON k.id=l.id_khach_hang LEFT JOIN xe_oto x ON x.id=l.id_xe $w");
        $total->execute($params); $total=(int)$total->fetchColumn();
        $stmt=$db->prepare("SELECT l.*,k.ho_ten,k.so_dien_thoai,x.ten_xe,x.ma_xe,n.ho_ten AS ten_nv FROM lich_hen l LEFT JOIN khach_hang k ON k.id=l.id_khach_hang LEFT JOIN xe_oto x ON x.id=l.id_xe LEFT JOIN nhan_vien n ON n.id=l.id_nhan_vien $w ORDER BY l.ngay_gio DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'pages'=>ceil($total/$limit)]); exit;

    default:
        header("Location: ../pages/login.php?error=Action không hợp lệ."); exit;
}
