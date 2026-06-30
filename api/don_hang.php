<?php
// api/don_hang.php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Bảo vệ
if (in_array($action, ['create','list_user']) && empty($_SESSION['user_id']) && empty($_SESSION['nv_id'])) {
    header("Location: ../pages/login.php?error=Bạn cần đăng nhập."); exit;
}
if (in_array($action, ['delete','list_all']) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền."); exit;
}

switch ($action) {

    case 'create':
        if ($method !== 'POST') { header("Location: ../pages/dat_xe.php?error=Phương thức không hợp lệ."); exit; }
        $id_kh   = (int)$_SESSION['user_id'];
        $id_xe   = (int)($_POST['id_xe']   ?? 0);
        $pttt    = trim($_POST['phuong_thuc_thanh_toan'] ?? 'tiền mặt');
        $ghi_chu = trim($_POST['ghi_chu']  ?? '') ?: null;
        $km_code = trim($_POST['ma_km']    ?? '');

        if ($id_xe <= 0) { header("Location: ../pages/xe.php?error=Vui lòng chọn xe."); exit; }
        $xe = $db->prepare("SELECT * FROM xe_oto WHERE id=? AND trang_thai='còn hàng' LIMIT 1");
        $xe->execute([$id_xe]); $xe = $xe->fetch();
        if (!$xe) { header("Location: ../pages/xe.php?error=Xe không tồn tại hoặc đã hết hàng."); exit; }

        $gia = $xe['gia_khuyen_mai'] ?? $xe['gia_ban'];
        $tong = $gia;

        // Kiểm tra mã khuyến mãi
        if ($km_code !== '') {
            $km = $db->prepare("SELECT * FROM khuyen_mai WHERE ma_khuyen_mai=? AND trang_thai='hoạt động' AND ngay_bat_dau<=CURDATE() AND ngay_ket_thuc>=CURDATE() AND so_luong_su_dung>0 LIMIT 1");
            $km->execute([$km_code]); $km = $km->fetch();
            if ($km) {
                if ($km['phan_tram_giam']) $tong = $tong * (1 - $km['phan_tram_giam']/100);
                elseif ($km['gia_tri_giam']) $tong = max(0, $tong - $km['gia_tri_giam']);
                // Giảm số lượng KM
                $db->prepare("UPDATE khuyen_mai SET so_luong_su_dung=so_luong_su_dung-1 WHERE id=?")->execute([$km['id']]);
            }
        }

        // Tạo mã đơn hàng
        $ma_dh = 'DH' . date('ymd') . rand(100,999);
        $db->prepare("INSERT INTO don_hang (ma_don_hang,id_khach_hang,tong_tien,phuong_thuc_thanh_toan,ghi_chu) VALUES (?,?,?,?,?)")
           ->execute([$ma_dh, $id_kh, $tong, $pttt, $ghi_chu]);
        $id_dh = $db->lastInsertId();

        // Chi tiết
        $db->prepare("INSERT INTO chi_tiet_don_hang (id_don_hang,id_xe,so_luong,gia_ban) VALUES (?,?,1,?)")
           ->execute([$id_dh, $id_xe, $gia]);

        header("Location: ../pages/don_hang_ct.php?id={$id_dh}&msg=Đặt xe thành công!"); exit;

    case 'update_info':
        if ($method !== 'POST') { header("Location: ../admin/don_hang.php?error=Phương thức không hợp lệ."); exit; }
        $id      = (int)($_POST['id'] ?? 0);
        $pttt    = trim($_POST['phuong_thuc_thanh_toan'] ?? '');
        $ghi_chu = trim($_POST['ghi_chu'] ?? '') ?: null;
        if ($id <= 0) { header("Location: ../admin/don_hang.php?error=ID không hợp lệ."); exit; }
        $db->prepare("UPDATE don_hang SET phuong_thuc_thanh_toan=?, ghi_chu=? WHERE id=?")->execute([$pttt, $ghi_chu, $id]);
        header("Location: ../admin/don_hang.php?msg=Cập nhật đơn hàng thành công!"); exit;

    case 'update_status':
        if ($method !== 'POST') { header("Location: ../admin/don_hang.php?error=Phương thức không hợp lệ."); exit; }
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['trang_thai'] ?? '');
        $valid  = ['chờ xác nhận','đã xác nhận','đang giao','hoàn thành','hủy'];
        if ($id <= 0 || !in_array($status, $valid)) { header("Location: ../admin/don_hang.php?error=Dữ liệu không hợp lệ."); exit; }
        $db->prepare("UPDATE don_hang SET trang_thai=? WHERE id=?")->execute([$status, $id]);
        $ref = ($_POST['ref'] ?? 'admin');
        if ($ref === 'user') header("Location: ../pages/lich_su_dh.php?msg=Cập nhật trạng thái thành công!");
        else header("Location: ../admin/don_hang.php?msg=Cập nhật trạng thái thành công!");
        exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/don_hang.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header("Location: ../admin/don_hang.php?error=ID không hợp lệ."); exit; }
        $db->prepare("DELETE FROM don_hang WHERE id=?")->execute([$id]);
        header("Location: ../admin/don_hang.php?msg=Xóa đơn hàng thành công!"); exit;

    case 'list_user':
        header('Content-Type: application/json');
        $id_kh  = (int)($_SESSION['user_id'] ?? 0);
        $page   = max(1,(int)($_GET['page'] ?? 1)); $limit=10; $offset=($page-1)*$limit;
        $total  = $db->prepare("SELECT COUNT(*) FROM don_hang WHERE id_khach_hang=?"); $total->execute([$id_kh]); $total=(int)$total->fetchColumn();
        $stmt   = $db->prepare("SELECT d.*,GROUP_CONCAT(x.ten_xe SEPARATOR ', ') AS ten_xe_list FROM don_hang d LEFT JOIN chi_tiet_don_hang c ON c.id_don_hang=d.id LEFT JOIN xe_oto x ON x.id=c.id_xe WHERE d.id_khach_hang=? GROUP BY d.id ORDER BY d.ngay_dat_hang DESC LIMIT $limit OFFSET $offset");
        $stmt->execute([$id_kh]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'pages'=>ceil($total/$limit)]); exit;

    case 'list_all':
        header('Content-Type: application/json');
        $page   = max(1,(int)($_GET['page']   ?? 1)); $limit=20; $offset=($page-1)*$limit;
        $search = trim($_GET['search'] ?? ''); $tt = trim($_GET['trang_thai'] ?? '');
        $where=[]; $params=[];
        if ($search!=='') { $where[]="(k.ho_ten LIKE ? OR d.ma_don_hang LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
        if ($tt!=='')     { $where[]="d.trang_thai=?"; $params[]=$tt; }
        $w = $where ? 'WHERE '.implode(' AND ',$where) : '';
        $total = $db->prepare("SELECT COUNT(*) FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang $w");
        $total->execute($params); $total=(int)$total->fetchColumn();
        $stmt = $db->prepare("SELECT d.*,k.ho_ten,k.email,k.so_dien_thoai,GROUP_CONCAT(x.ten_xe SEPARATOR ', ') AS ten_xe_list FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang LEFT JOIN chi_tiet_don_hang c ON c.id_don_hang=d.id LEFT JOIN xe_oto x ON x.id=c.id_xe $w GROUP BY d.id ORDER BY d.ngay_dat_hang DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'pages'=>ceil($total/$limit)]); exit;

    case 'get_detail':
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        $dh = $db->prepare("SELECT d.*,k.ho_ten,k.email,k.so_dien_thoai FROM don_hang d LEFT JOIN khach_hang k ON k.id=d.id_khach_hang WHERE d.id=? LIMIT 1");
        $dh->execute([$id]); $dh=$dh->fetch();
        if (!$dh) { echo json_encode(['success'=>false,'message'=>'Không tìm thấy đơn hàng']); exit; }
        $ct = $db->prepare("SELECT c.*,x.ten_xe,x.ma_xe,x.hinh_anh_chinh FROM chi_tiet_don_hang c LEFT JOIN xe_oto x ON x.id=c.id_xe WHERE c.id_don_hang=?");
        $ct->execute([$id]);
        echo json_encode(['success'=>true,'don_hang'=>$dh,'chi_tiet'=>$ct->fetchAll(PDO::FETCH_ASSOC)]); exit;

    default:
        header("Location: ../pages/login.php?error=Action không hợp lệ."); exit;
}
