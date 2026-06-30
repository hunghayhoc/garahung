<?php
// api/khach_hang.php
// API CRUD khách hàng

session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


// Các action yêu cầu quyền admin
$admin_actions = ['admin_add', 'admin_update', 'admin_delete', 'list'];
if (in_array($action, $admin_actions) && ($_SESSION['role'] ?? '') !== 'admin') {
    if ($action === 'list') {
        // list trả JSON → trả lỗi JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../pages/login.php?error=Bạn không có quyền thực hiện thao tác này.");
    exit;
}

// update_profile yêu cầu đăng nhập khách hàng
if ($action === 'update_profile' && empty($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?error=Vui lòng đăng nhập.");
    exit;
}

switch ($action) {

    // ─── ĐĂNG KÝ KHÁCH HÀNG MỚI ──────────────────────────────────────────────
    case 'register':
        if ($method !== 'POST') {
            header("Location: ../pages/register.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $ho_ten       = trim($_POST['ho_ten']       ?? '');
        $email        = trim($_POST['email']         ?? '');
        $mat_khau     = trim($_POST['mat_khau']      ?? '');
        $xac_nhan_mk  = trim($_POST['xac_nhan_mk']   ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
        $dia_chi      = trim($_POST['dia_chi']        ?? '') ?: null;
        $ngay_sinh    = trim($_POST['ngay_sinh']      ?? '') ?: null;
        $gioi_tinh    = trim($_POST['gioi_tinh']      ?? '') ?: null;

        // Validate
        $errors = [];
        if ($ho_ten === '') $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (strlen($mat_khau) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        if ($mat_khau !== $xac_nhan_mk) $errors[] = 'Mật khẩu xác nhận không khớp.';
        if ($so_dien_thoai !== '' && !preg_match('/^[0-9]{9,11}$/', $so_dien_thoai))
            $errors[] = 'Số điện thoại không hợp lệ (9-11 chữ số).';
        if ($gioi_tinh && !in_array($gioi_tinh, ['Nam', 'Nữ', 'Khác']))
            $errors[] = 'Giới tính không hợp lệ.';

        if ($errors) {
            $msg = urlencode(implode(' | ', $errors));
            header("Location: ../pages/register.php?error={$msg}");
            exit;
        }

        // Kiểm tra email trùng
        $stmt = $db->prepare("SELECT id FROM khach_hang WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: ../pages/register.php?error=Email đã được sử dụng.");
            exit;
        }

        // Kiểm tra SĐT trùng (nếu có)
        if ($so_dien_thoai !== '') {
            $stmt = $db->prepare("SELECT id FROM khach_hang WHERE so_dien_thoai = ? LIMIT 1");
            $stmt->execute([$so_dien_thoai]);
            if ($stmt->fetch()) {
                header("Location: ../pages/register.php?error=Số điện thoại đã được sử dụng.");
                exit;
            }
        }

        $hash = password_hash($mat_khau, PASSWORD_DEFAULT);
        $sdt  = $so_dien_thoai !== '' ? $so_dien_thoai : null;

        $stmt = $db->prepare(
            "INSERT INTO khach_hang (ho_ten, email, so_dien_thoai, mat_khau, dia_chi, ngay_sinh, gioi_tinh)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$ho_ten, $email, $sdt, $hash, $dia_chi, $ngay_sinh, $gioi_tinh]);

        header("Location: ../pages/login.php?msg=Đăng ký thành công! Vui lòng đăng nhập.");
        exit;
// ─── ADMIN: THÊM KHÁCH HÀNG MỚI ──────────────────────────────────────────
    case 'admin_add':
        header("Location: ../admin/khach_hang.php?error=Chức năng thêm khách hàng đã bị tắt. Vui lòng dùng trang đăng ký.");
        exit;
    // ─── CẬP NHẬT PROFILE (khách hàng tự sửa) ────────────────────────────────
    case 'update_profile':
        if ($method !== 'POST') {
            header("Location: ../pages/profile.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $id           = (int)$_SESSION['user_id'];
        $ho_ten       = trim($_POST['ho_ten']        ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '') ?: null;
        $dia_chi      = trim($_POST['dia_chi']         ?? '') ?: null;
        $ngay_sinh    = trim($_POST['ngay_sinh']       ?? '') ?: null;
        $gioi_tinh    = trim($_POST['gioi_tinh']       ?? '') ?: null;
        $mat_khau_cu  = trim($_POST['mat_khau_cu']     ?? '');
        $mat_khau_moi = trim($_POST['mat_khau_moi']    ?? '');
        $xac_nhan_mk  = trim($_POST['xac_nhan_mk']     ?? '');

        $errors = [];
        if ($ho_ten === '') $errors[] = 'Họ tên không được để trống.';
        if ($gioi_tinh && !in_array($gioi_tinh, ['Nam', 'Nữ', 'Khác']))
            $errors[] = 'Giới tính không hợp lệ.';

        // Đổi mật khẩu (tùy chọn)
        $new_hash = null;
        if ($mat_khau_moi !== '') {
            if ($mat_khau_cu === '') {
                $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
            } else {
                $stmt = $db->prepare("SELECT mat_khau FROM khach_hang WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row || !password_verify($mat_khau_cu, $row['mat_khau'])) {
                    $errors[] = 'Mật khẩu hiện tại không đúng.';
                } elseif (strlen($mat_khau_moi) < 6) {
                    $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
                } elseif ($mat_khau_moi !== $xac_nhan_mk) {
                    $errors[] = 'Xác nhận mật khẩu không khớp.';
                } else {
                    $new_hash = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
                }
            }
        }

        if ($errors) {
            $msg = urlencode(implode(' | ', $errors));
            header("Location: ../pages/profile.php?error={$msg}");
            exit;
        }

        if ($new_hash) {
            $stmt = $db->prepare(
                "UPDATE khach_hang SET ho_ten=?, so_dien_thoai=?, dia_chi=?, ngay_sinh=?, gioi_tinh=?, mat_khau=?
                 WHERE id=?"
            );
            $stmt->execute([$ho_ten, $so_dien_thoai, $dia_chi, $ngay_sinh, $gioi_tinh, $new_hash, $id]);
        } else {
            $stmt = $db->prepare(
                "UPDATE khach_hang SET ho_ten=?, so_dien_thoai=?, dia_chi=?, ngay_sinh=?, gioi_tinh=?
                 WHERE id=?"
            );
            $stmt->execute([$ho_ten, $so_dien_thoai, $dia_chi, $ngay_sinh, $gioi_tinh, $id]);
        }

        // Cập nhật session
        $_SESSION['ho_ten'] = $ho_ten;

        header("Location: ../pages/profile.php?msg=Cập nhật thông tin thành công!");
        exit;

    // ─── ADMIN: SỬA KHÁCH HÀNG ───────────────────────────────────────────────
    case 'admin_update':
        if ($method !== 'POST') {
            header("Location: ../admin/khach_hang.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $id           = (int)($_POST['id'] ?? 0);
        $ho_ten       = trim($_POST['ho_ten']         ?? '');
        $email        = trim($_POST['email']           ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai']  ?? '') ?: null;
        $dia_chi      = trim($_POST['dia_chi']          ?? '') ?: null;
        $ngay_sinh    = trim($_POST['ngay_sinh']        ?? '') ?: null;
        $gioi_tinh    = trim($_POST['gioi_tinh']        ?? '') ?: null;
        $diem_tich_luy = (int)($_POST['diem_tich_luy'] ?? 0);
        $trang_thai   = trim($_POST['trang_thai']       ?? 'hoạt động');

        $errors = [];
        if ($id <= 0)      $errors[] = 'ID khách hàng không hợp lệ.';
        if ($ho_ten === '') $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (!in_array($trang_thai, ['hoạt động', 'khóa'])) $errors[] = 'Trạng thái không hợp lệ.';

        if ($errors) {
            $msg = urlencode(implode(' | ', $errors));
            header("Location: ../admin/khach_hang.php?error={$msg}");
            exit;
        }

        // Kiểm tra email trùng với người khác
        $stmt = $db->prepare("SELECT id FROM khach_hang WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            header("Location: ../admin/khach_hang.php?error=Email đã tồn tại.");
            exit;
        }

        $stmt = $db->prepare(
            "UPDATE khach_hang SET ho_ten=?, email=?, so_dien_thoai=?, dia_chi=?, ngay_sinh=?,
             gioi_tinh=?, diem_tich_luy=?, trang_thai=? WHERE id=?"
        );
        $stmt->execute([$ho_ten, $email, $so_dien_thoai, $dia_chi, $ngay_sinh,
                        $gioi_tinh, $diem_tich_luy, $trang_thai, $id]);

        header("Location: ../admin/khach_hang.php?msg=Cập nhật khách hàng thành công!");
        exit;

    // ─── ADMIN: XÓA KHÁCH HÀNG ───────────────────────────────────────────────
    case 'admin_delete':
        if ($method !== 'POST') {
            header("Location: ../admin/khach_hang.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: ../admin/khach_hang.php?error=ID không hợp lệ.");
            exit;
        }

        $stmt = $db->prepare("DELETE FROM khach_hang WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: ../admin/khach_hang.php?msg=Xóa khách hàng thành công!");
        exit;

    // ─── DANH SÁCH KHÁCH HÀNG (JSON) ─────────────────────────────────────────
    case 'list':
        header('Content-Type: application/json');

        $search     = trim($_GET['search']     ?? '');
        $trang_thai = trim($_GET['trang_thai'] ?? '');
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = 20;
        $offset     = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = "(ho_ten LIKE ? OR email LIKE ? OR so_dien_thoai LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($trang_thai !== '') {
            $where[]  = "trang_thai = ?";
            $params[] = $trang_thai;
        }

        $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_stmt = $db->prepare("SELECT COUNT(*) FROM khach_hang {$sql_where}");
        $count_stmt->execute($params);
        $total = (int)$count_stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT id, ho_ten, email, so_dien_thoai, dia_chi, ngay_sinh,
                    gioi_tinh, diem_tich_luy, trang_thai, ngay_dang_ky
             FROM khach_hang {$sql_where}
             ORDER BY ngay_dang_ky DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $limit),
        ]);
        exit;

    default:
        header("Location: ../pages/login.php?error=Action không hợp lệ.");
        exit;
}
