<?php
// api/auth.php
// Xử lý login / logout cho cả khách hàng và nhân viên

session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function verifyNhanVienPassword(string $input, string $stored): bool {
    $info = password_get_info($stored);
    if (($info['algo'] ?? 0) !== 0) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input);
}

function sanitizeRedirect(?string $redirect, string $default): string {
    $redirect = trim((string)$redirect);
    if ($redirect === '') return $default;
    if (preg_match('/[\\r\\n]/', $redirect)) return $default;
    if (preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $redirect)) return $default;
    if (strpos($redirect, '//') === 0) return $default;

    if (preg_match('~^\\.\\./pages/[a-zA-Z0-9_-]+\\.php(\\?.*)?$~', $redirect)) {
        return $redirect;
    }
    return $default;
}

switch ($action) {

    // ─── LOGIN ────────────────────────────────────────────────────────────────
    case 'login':
        if ($method !== 'POST') {
            header("Location: ../pages/login.php?error=Phương thức không hợp lệ.");
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $mat_khau = trim($_POST['mat_khau'] ?? '');
        $loai     = trim($_POST['loai']     ?? 'auto'); // 'auto' | 'khach_hang' | 'nhan_vien'

        if ($email === '' || $mat_khau === '') {
            header("Location: ../pages/login.php?error=Vui lòng nhập đầy đủ email và mật khẩu.");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../pages/login.php?error=Email không hợp lệ.");
            exit;
        }

        if ($loai !== 'nhan_vien' && $loai !== 'khach_hang') {
            $loai = 'auto';
        }

        // Auto: ưu tiên đăng nhập admin/nhân viên, nếu không khớp thì thử khách hàng
        if ($loai === 'auto') {
            $stmt = $db->prepare("SELECT * FROM nhan_vien WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $nv = $stmt->fetch();

            if ($nv && verifyNhanVienPassword($mat_khau, (string)$nv['mat_khau'])) {
                if (($nv['trang_thai'] ?? '') === 'nghỉ việc') {
                    header("Location: ../pages/login.php?error=Tài khoản nhân viên đã bị vô hiệu hóa.");
                    exit;
                }

                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['nv_id']   = $nv['id'];
                $_SESSION['ho_ten']  = $nv['ho_ten'];
                $_SESSION['email']   = $nv['email'];
                $_SESSION['chuc_vu'] = $nv['chuc_vu'];
                $_SESSION['role']    = 'admin';

                header("Location: ../admin/dashboard.php?msg=Đăng nhập thành công!");
                exit;
            }

            $stmt = $db->prepare("SELECT * FROM khach_hang WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $kh = $stmt->fetch();

            if (!$kh || !password_verify($mat_khau, (string)$kh['mat_khau'])) {
                header("Location: ../pages/login.php?error=Email hoặc mật khẩu không đúng.");
                exit;
            }

            if (($kh['trang_thai'] ?? '') === 'khóa') {
                header("Location: ../pages/login.php?error=Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.");
                exit;
            }

            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['user_id'] = $kh['id'];
            $_SESSION['ho_ten']  = $kh['ho_ten'];
            $_SESSION['email']   = $kh['email'];
            $_SESSION['role']    = 'khach_hang';

            $redirect = sanitizeRedirect($_POST['redirect'] ?? null, '../pages/index.php');
            $join = (strpos($redirect, '?') !== false) ? '&' : '?';
            header("Location: {$redirect}{$join}msg=Chào mừng " . urlencode($kh['ho_ten']) . "!");
            exit;
        }

        if ($loai === 'nhan_vien') {
            // ── Đăng nhập nhân viên (so sánh plain text) ──────────────────
            $stmt = $db->prepare("SELECT * FROM nhan_vien WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $nv = $stmt->fetch();

            if (!$nv || !verifyNhanVienPassword($mat_khau, (string)$nv['mat_khau'])) {
                header("Location: ../pages/login.php?error=Email hoặc mật khẩu không đúng.");
                exit;
            }

            if ($nv['trang_thai'] === 'nghỉ việc') {
                header("Location: ../pages/login.php?error=Tài khoản nhân viên đã bị vô hiệu hóa.");
                exit;
            }

            // Tạo session nhân viên
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['nv_id']   = $nv['id'];
            $_SESSION['ho_ten']  = $nv['ho_ten'];
            $_SESSION['email']   = $nv['email'];
            $_SESSION['chuc_vu'] = $nv['chuc_vu'];
            // Nhân viên có chức vụ 'quan_ly' hoặc 'admin' → role = admin
            $_SESSION['role'] = 'admin';

            header("Location: ../admin/dashboard.php?msg=Đăng nhập thành công!");
            exit;

        } else {
            // ── Đăng nhập khách hàng (dùng password_hash) ─────────────────
            $stmt = $db->prepare("SELECT * FROM khach_hang WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $kh = $stmt->fetch();

            if (!$kh || !password_verify($mat_khau, $kh['mat_khau'])) {
                header("Location: ../pages/login.php?error=Email hoặc mật khẩu không đúng.");
                exit;
            }

            if ($kh['trang_thai'] === 'khóa') {
                header("Location: ../pages/login.php?error=Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.");
                exit;
            }

            // Tạo session khách hàng
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['user_id']  = $kh['id'];
            $_SESSION['ho_ten']   = $kh['ho_ten'];
            $_SESSION['email']    = $kh['email'];
            $_SESSION['role']     = 'khach_hang';

            $redirect = sanitizeRedirect($_POST['redirect'] ?? null, '../pages/index.php');
            $join = (strpos($redirect, '?') !== false) ? '&' : '?';
            header("Location: {$redirect}{$join}msg=Chào mừng " . urlencode($kh['ho_ten']) . "!");
            exit;
        }

    // ─── LOGOUT ───────────────────────────────────────────────────────────────
    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: ../pages/login.php?msg=Bạn đã đăng xuất thành công.");
        exit;

    default:
        header("Location: ../pages/login.php?error=Hành động không hợp lệ.");
        exit;
}
