<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('require_login')) {
    function require_login(string $redirect = '../auth/login.php'): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role(string $role, string $redirect = '../auth/login.php'): void
    {
        require_login($redirect);

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            $fallback = $role === 'admin' ? '../user/dashboard_user.php' : '../admin/dashboard_admin.php';
            header('Location: ' . $fallback);
            exit;
        }
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key): ?string
    {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $message = (string) $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        if (empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }

        return $message;
    }
}
