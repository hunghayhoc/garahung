<?php
// api/hang_xe.php
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
        if ($method !== 'POST') { header("Location: ../admin/hang_xe.php?error=Phương thức không hợp lệ."); exit; }
        $ten_hang = trim($_POST['ten_hang'] ?? '');
        $quoc_gia = trim($_POST['quoc_gia'] ?? '') ?: null;
        $mo_ta    = trim($_POST['mo_ta']    ?? '') ?: null;
        $logo     = trim($_POST['logo']     ?? '') ?: null;
        if ($ten_hang === '') { header("Location: ../admin/hang_xe.php?error=Tên hãng không được để trống."); exit; }
        $stmt = $db->prepare("SELECT id FROM hang_xe WHERE ten_hang = ? LIMIT 1");
        $stmt->execute([$ten_hang]);
        if ($stmt->fetch()) { header("Location: ../admin/hang_xe.php?error=Hãng xe đã tồn tại."); exit; }
        $db->prepare("INSERT INTO hang_xe (ten_hang, quoc_gia, mo_ta, logo) VALUES (?,?,?,?)")->execute([$ten_hang, $quoc_gia, $mo_ta, $logo]);
        header("Location: ../admin/hang_xe.php?msg=Thêm hãng xe thành công!"); exit;

    case 'update':
        if ($method !== 'POST') { header("Location: ../admin/hang_xe.php?error=Phương thức không hợp lệ."); exit; }
        $id       = (int)($_POST['id'] ?? 0);
        $ten_hang = trim($_POST['ten_hang'] ?? '');
        $quoc_gia = trim($_POST['quoc_gia'] ?? '') ?: null;
        $mo_ta    = trim($_POST['mo_ta']    ?? '') ?: null;
        $logo     = trim($_POST['logo']     ?? '') ?: null;
        if ($id <= 0 || $ten_hang === '') { header("Location: ../admin/hang_xe.php?error=Dữ liệu không hợp lệ."); exit; }
        $stmt = $db->prepare("SELECT id FROM hang_xe WHERE ten_hang=? AND id!=? LIMIT 1");
        $stmt->execute([$ten_hang, $id]);
        if ($stmt->fetch()) { header("Location: ../admin/hang_xe.php?error=Tên hãng đã tồn tại."); exit; }
        $db->prepare("UPDATE hang_xe SET ten_hang=?, quoc_gia=?, mo_ta=?, logo=? WHERE id=?")->execute([$ten_hang, $quoc_gia, $mo_ta, $logo, $id]);
        header("Location: ../admin/hang_xe.php?msg=Cập nhật hãng xe thành công!"); exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/hang_xe.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header("Location: ../admin/hang_xe.php?error=ID không hợp lệ."); exit; }
        // Kiểm tra có xe không
        $cnt = $db->prepare("SELECT COUNT(*) FROM xe_oto WHERE id_hang=?"); $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) { header("Location: ../admin/hang_xe.php?error=Không thể xóa: hãng xe đang có xe trong hệ thống."); exit; }
        $db->prepare("DELETE FROM hang_xe WHERE id=?")->execute([$id]);
        header("Location: ../admin/hang_xe.php?msg=Xóa hãng xe thành công!"); exit;

    case 'list':
        header('Content-Type: application/json');
        $stmt = $db->query("SELECT h.*, COUNT(x.id) AS so_xe FROM hang_xe h LEFT JOIN xe_oto x ON x.id_hang=h.id GROUP BY h.id ORDER BY h.ten_hang ASC");
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;

    default:
        header("Location: ../admin/hang_xe.php?error=Action không hợp lệ."); exit;
}
