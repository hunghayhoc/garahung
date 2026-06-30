<?php

session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$admin_only = ['add', 'update', 'delete'];
if (in_array($action, $admin_only) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền thực hiện thao tác này.");
    exit;
}

switch ($action) {

    case 'add':
        if ($method !== 'POST') { header("Location: ../admin/danh_muc_xe.php?error=Phương thức không hợp lệ."); exit; }
        $ten  = trim($_POST['ten_danh_muc'] ?? '');
        $mo   = trim($_POST['mo_ta']        ?? '') ?: null;
        $tt   = trim($_POST['trang_thai']   ?? 'hoạt động');
        if ($ten === '') { header("Location: ../admin/danh_muc_xe.php?error=Tên danh mục không được để trống."); exit; }
        if (!in_array($tt, ['hoạt động','không hoạt động'])) $tt = 'hoạt động';
        $db->prepare("INSERT INTO danh_muc_xe (ten_danh_muc, mo_ta, trang_thai) VALUES (?,?,?)")->execute([$ten, $mo, $tt]);
        header("Location: ../admin/danh_muc_xe.php?msg=Thêm danh mục thành công!"); exit;

    case 'update':
        if ($method !== 'POST') { header("Location: ../admin/danh_muc_xe.php?error=Phương thức không hợp lệ."); exit; }
        $id  = (int)($_POST['id'] ?? 0);
        $ten = trim($_POST['ten_danh_muc'] ?? '');
        $mo  = trim($_POST['mo_ta']        ?? '') ?: null;
        $tt  = trim($_POST['trang_thai']   ?? 'hoạt động');
        if ($id <= 0 || $ten === '') { header("Location: ../admin/danh_muc_xe.php?error=Dữ liệu không hợp lệ."); exit; }
        if (!in_array($tt, ['hoạt động','không hoạt động'])) $tt = 'hoạt động';
        $db->prepare("UPDATE danh_muc_xe SET ten_danh_muc=?, mo_ta=?, trang_thai=? WHERE id=?")->execute([$ten, $mo, $tt, $id]);
        header("Location: ../admin/danh_muc_xe.php?msg=Cập nhật danh mục thành công!"); exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/danh_muc_xe.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header("Location: ../admin/danh_muc_xe.php?error=ID không hợp lệ."); exit; }
        $cnt = $db->prepare("SELECT COUNT(*) FROM xe_oto WHERE id_danh_muc=?"); $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) { header("Location: ../admin/danh_muc_xe.php?error=Không thể xóa: danh mục đang có xe."); exit; }
        $db->prepare("DELETE FROM danh_muc_xe WHERE id=?")->execute([$id]);
        header("Location: ../admin/danh_muc_xe.php?msg=Xóa danh mục thành công!"); exit;

    case 'list':
        header('Content-Type: application/json');
        $stmt = $db->query("SELECT d.*, COUNT(x.id) AS so_xe FROM danh_muc_xe d LEFT JOIN xe_oto x ON x.id_danh_muc=d.id GROUP BY d.id ORDER BY d.ten_danh_muc ASC");
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;

    default:
        header("Location: ../admin/danh_muc_xe.php?error=Action không hợp lệ."); exit;
}
