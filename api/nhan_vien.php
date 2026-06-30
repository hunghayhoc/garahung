<?php
// api/nhan_vien.php
// API CRUD nhân viên (admin)

session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$admin_only = ['add', 'update', 'delete'];
if (in_array($action, $admin_only, true) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền thực hiện thao tác này.");
    exit;
}

switch ($action) {

    case 'add':
        if ($method !== 'POST') {
            header("Location: ../admin/nhan_vien.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $ho_ten        = trim($_POST['ho_ten'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '') ?: null;
        $chuc_vu       = trim($_POST['chuc_vu'] ?? 'Nhân viên');
        $mat_khau      = trim($_POST['mat_khau'] ?? '');
        $xac_nhan_mk   = trim($_POST['xac_nhan_mk'] ?? '');
        $trang_thai    = trim($_POST['trang_thai'] ?? 'hoạt động');

        $errors = [];
        if ($ho_ten === '') $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if ($so_dien_thoai !== null && !preg_match('/^[0-9]{9,11}$/', $so_dien_thoai)) $errors[] = 'Số điện thoại không hợp lệ (9-11 chữ số).';
        if (strlen($mat_khau) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        if ($mat_khau !== $xac_nhan_mk) $errors[] = 'Mật khẩu xác nhận không khớp.';
        if (!in_array($trang_thai, ['hoạt động', 'nghỉ việc'], true)) $errors[] = 'Trạng thái không hợp lệ.';

        if ($errors) {
            header("Location: ../admin/nhan_vien.php?error=" . urlencode(implode(' | ', $errors)));
            exit;
        }

        $ck = $db->prepare("SELECT id FROM nhan_vien WHERE email = ? LIMIT 1");
        $ck->execute([$email]);
        if ($ck->fetch()) {
            header("Location: ../admin/nhan_vien.php?error=Email đã tồn tại.");
            exit;
        }

        $hash = password_hash($mat_khau, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO nhan_vien (ho_ten, email, so_dien_thoai, chuc_vu, mat_khau, trang_thai) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$ho_ten, $email, $so_dien_thoai, $chuc_vu, $hash, $trang_thai]);

        header("Location: ../admin/nhan_vien.php?msg=Thêm nhân viên thành công!");
        exit;

    case 'update':
        if ($method !== 'POST') {
            header("Location: ../admin/nhan_vien.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $id            = (int)($_POST['id'] ?? 0);
        $ho_ten        = trim($_POST['ho_ten'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '') ?: null;
        $chuc_vu       = trim($_POST['chuc_vu'] ?? 'Nhân viên');
        $mat_khau_moi  = trim($_POST['mat_khau_moi'] ?? '');
        $xac_nhan_mk   = trim($_POST['xac_nhan_mk'] ?? '');
        $trang_thai    = trim($_POST['trang_thai'] ?? 'hoạt động');

        $errors = [];
        if ($id <= 0) $errors[] = 'ID không hợp lệ.';
        if ($ho_ten === '') $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if ($so_dien_thoai !== null && $so_dien_thoai !== '' && !preg_match('/^[0-9]{9,11}$/', $so_dien_thoai)) $errors[] = 'Số điện thoại không hợp lệ (9-11 chữ số).';
        if (!in_array($trang_thai, ['hoạt động', 'nghỉ việc'], true)) $errors[] = 'Trạng thái không hợp lệ.';
        if ($mat_khau_moi !== '') {
            if (strlen($mat_khau_moi) < 6) $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            if ($mat_khau_moi !== $xac_nhan_mk) $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }

        if ($errors) {
            header("Location: ../admin/nhan_vien.php?error=" . urlencode(implode(' | ', $errors)));
            exit;
        }

        $ck = $db->prepare("SELECT id FROM nhan_vien WHERE email = ? AND id != ? LIMIT 1");
        $ck->execute([$email, $id]);
        if ($ck->fetch()) {
            header("Location: ../admin/nhan_vien.php?error=Email đã tồn tại.");
            exit;
        }

        if ($mat_khau_moi !== '') {
            $hash = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE nhan_vien SET ho_ten=?, email=?, so_dien_thoai=?, chuc_vu=?, trang_thai=?, mat_khau=? WHERE id=?");
            $stmt->execute([$ho_ten, $email, $so_dien_thoai, $chuc_vu, $trang_thai, $hash, $id]);
        } else {
            $stmt = $db->prepare("UPDATE nhan_vien SET ho_ten=?, email=?, so_dien_thoai=?, chuc_vu=?, trang_thai=? WHERE id=?");
            $stmt->execute([$ho_ten, $email, $so_dien_thoai, $chuc_vu, $trang_thai, $id]);
        }

        header("Location: ../admin/nhan_vien.php?msg=Cập nhật nhân viên thành công!");
        exit;

    case 'delete':
        if ($method !== 'POST') {
            header("Location: ../admin/nhan_vien.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: ../admin/nhan_vien.php?error=ID không hợp lệ.");
            exit;
        }
        if (!empty($_SESSION['nv_id']) && (int)$_SESSION['nv_id'] === $id) {
            header("Location: ../admin/nhan_vien.php?error=Không thể xóa chính bạn.");
            exit;
        }

        $db->prepare("DELETE FROM nhan_vien WHERE id=?")->execute([$id]);
        header("Location: ../admin/nhan_vien.php?msg=Xóa nhân viên thành công!");
        exit;

    default:
        header("Location: ../admin/nhan_vien.php?error=Hành động không hợp lệ.");
        exit;
}

