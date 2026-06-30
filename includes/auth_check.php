<?php
// includes/auth_check.php
// Kiểm tra session dùng chung – TV2, TV3, TV4 đều require_once file này

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Yêu cầu người dùng đã đăng nhập (khách hàng HOẶC nhân viên/admin).
 * Nếu chưa → redirect về login.
 */
function requireLogin(string $redirectTo = '../pages/login.php'): void {
    if (empty($_SESSION['user_id']) && empty($_SESSION['nv_id'])) {
        header("Location: {$redirectTo}?error=Bạn cần đăng nhập để tiếp tục.");
        exit;
    }
}

/**
 * Yêu cầu quyền admin (nhân viên đã đăng nhập).
 * Nếu không đủ quyền → redirect về login.
 */
function requireAdmin(string $redirectTo = '../pages/login.php'): void {
    if (empty($_SESSION['nv_id'])) {
        header("Location: {$redirectTo}?error=Bạn không có quyền truy cập trang này.");
        exit;
    }
}

/**
 * Kiểm tra người dùng có phải admin không (trả về bool).
 */
function isAdmin(): bool {
    return !empty($_SESSION['nv_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Kiểm tra khách hàng đã đăng nhập không.
 */
function isLoggedInKhachHang(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Lấy thông tin người dùng đang đăng nhập.
 */
function getCurrentUser(): array {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id'      => $_SESSION['user_id'],
            'ho_ten'  => $_SESSION['ho_ten']  ?? '',
            'email'   => $_SESSION['email']   ?? '',
            'role'    => 'khach_hang',
        ];
    }
    if (!empty($_SESSION['nv_id'])) {
        return [
            'id'      => $_SESSION['nv_id'],
            'ho_ten'  => $_SESSION['ho_ten']  ?? '',
            'email'   => $_SESSION['email']   ?? '',
            'role'    => $_SESSION['role']    ?? 'nhan_vien',
        ];
    }
    return [];
}
