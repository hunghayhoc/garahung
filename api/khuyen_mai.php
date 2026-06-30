<?php
// api/khuyen_mai.php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$admin_only = ['add','update','delete'];
if (in_array($action, $admin_only) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền."); exit;
}

switch ($action) {

    case 'add':
        if ($method !== 'POST') { header("Location: ../admin/khuyen_mai.php?error=Phương thức không hợp lệ."); exit; }
        $ten     = trim($_POST['ten_khuyen_mai']    ?? '');
        $ma      = strtoupper(trim($_POST['ma_khuyen_mai']  ?? ''));
        $ptgiam  = (int)($_POST['phan_tram_giam']  ?? 0) ?: null;
        $gtvgiam = floatval($_POST['gia_tri_giam']  ?? 0) ?: null;
        $ngay_bd = trim($_POST['ngay_bat_dau']      ?? '') ?: null;
        $ngay_kt = trim($_POST['ngay_ket_thuc']     ?? '') ?: null;
        $sl      = (int)($_POST['so_luong_su_dung'] ?? 1);
        $tt      = trim($_POST['trang_thai']         ?? 'hoạt động');
        $errors=[];
        if ($ten===''||$ma==='') $errors[]='Tên và mã khuyến mãi không được để trống.';
        if ($ptgiam===null&&$gtvgiam===null) $errors[]='Vui lòng nhập phần trăm giảm hoặc giá trị giảm.';
        if ($errors) { header("Location: ../admin/khuyen_mai.php?error=".urlencode(implode(' | ',$errors))); exit; }
        $ck=$db->prepare("SELECT id FROM khuyen_mai WHERE ma_khuyen_mai=? LIMIT 1"); $ck->execute([$ma]);
        if ($ck->fetch()) { header("Location: ../admin/khuyen_mai.php?error=Mã khuyến mãi đã tồn tại."); exit; }
        $db->prepare("INSERT INTO khuyen_mai (ten_khuyen_mai,ma_khuyen_mai,phan_tram_giam,gia_tri_giam,ngay_bat_dau,ngay_ket_thuc,so_luong_su_dung,trang_thai) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$ten,$ma,$ptgiam,$gtvgiam,$ngay_bd,$ngay_kt,$sl,$tt]);
        header("Location: ../admin/khuyen_mai.php?msg=Thêm khuyến mãi thành công!"); exit;

    case 'update':
        if ($method !== 'POST') { header("Location: ../admin/khuyen_mai.php?error=Phương thức không hợp lệ."); exit; }
        $id      = (int)($_POST['id'] ?? 0);
        $ten     = trim($_POST['ten_khuyen_mai']    ?? '');
        $ma      = strtoupper(trim($_POST['ma_khuyen_mai'] ?? ''));
        $ptgiam  = (int)($_POST['phan_tram_giam']   ?? 0) ?: null;
        $gtvgiam = floatval($_POST['gia_tri_giam']   ?? 0) ?: null;
        $ngay_bd = trim($_POST['ngay_bat_dau']       ?? '') ?: null;
        $ngay_kt = trim($_POST['ngay_ket_thuc']      ?? '') ?: null;
        $sl      = (int)($_POST['so_luong_su_dung']  ?? 1);
        $tt      = trim($_POST['trang_thai']          ?? 'hoạt động');
        if ($id<=0||$ten===''||$ma==='') { header("Location: ../admin/khuyen_mai.php?error=Dữ liệu không hợp lệ."); exit; }
        $ck=$db->prepare("SELECT id FROM khuyen_mai WHERE ma_khuyen_mai=? AND id!=? LIMIT 1"); $ck->execute([$ma,$id]);
        if ($ck->fetch()) { header("Location: ../admin/khuyen_mai.php?error=Mã khuyến mãi đã tồn tại."); exit; }
        $db->prepare("UPDATE khuyen_mai SET ten_khuyen_mai=?,ma_khuyen_mai=?,phan_tram_giam=?,gia_tri_giam=?,ngay_bat_dau=?,ngay_ket_thuc=?,so_luong_su_dung=?,trang_thai=? WHERE id=?")
           ->execute([$ten,$ma,$ptgiam,$gtvgiam,$ngay_bd,$ngay_kt,$sl,$tt,$id]);
        header("Location: ../admin/khuyen_mai.php?msg=Cập nhật khuyến mãi thành công!"); exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/khuyen_mai.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id<=0) { header("Location: ../admin/khuyen_mai.php?error=ID không hợp lệ."); exit; }
        $db->prepare("DELETE FROM khuyen_mai WHERE id=?")->execute([$id]);
        header("Location: ../admin/khuyen_mai.php?msg=Xóa khuyến mãi thành công!"); exit;

    case 'list':
        header('Content-Type: application/json');
        $stmt=$db->query("SELECT * FROM khuyen_mai ORDER BY ngay_bat_dau DESC");
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;

    case 'apply':
        header('Content-Type: application/json');
        $ma    = strtoupper(trim($_POST['ma_khuyen_mai'] ?? ''));
        $gia   = floatval($_POST['gia_ban'] ?? 0);
        if ($ma==='') { echo json_encode(['success'=>false,'message'=>'Vui lòng nhập mã khuyến mãi']); exit; }
        $km=$db->prepare("SELECT * FROM khuyen_mai WHERE ma_khuyen_mai=? AND trang_thai='hoạt động' AND (ngay_bat_dau IS NULL OR ngay_bat_dau<=CURDATE()) AND (ngay_ket_thuc IS NULL OR ngay_ket_thuc>=CURDATE()) AND so_luong_su_dung>0 LIMIT 1");
        $km->execute([$ma]); $km=$km->fetch();
        if (!$km) { echo json_encode(['success'=>false,'message'=>'Mã khuyến mãi không hợp lệ hoặc đã hết hạn']); exit; }
        $gia_sau=$gia;
        if ($km['phan_tram_giam']) $gia_sau=$gia*(1-$km['phan_tram_giam']/100);
        elseif ($km['gia_tri_giam']) $gia_sau=max(0,$gia-$km['gia_tri_giam']);
        echo json_encode(['success'=>true,'ten'=>$km['ten_khuyen_mai'],'gia_sau'=>$gia_sau,'giam'=>$gia-$gia_sau]); exit;

    default:
        header("Location: ../admin/khuyen_mai.php?error=Action không hợp lệ."); exit;
}
