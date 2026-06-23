<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

define('APP_NAME', 'Futsal Booking Wanasaba');
define('BASE_URL', 'http://localhost/PROJEK%202BLN');
define('PAYMENT_CALLBACK_URL', BASE_URL . '/callback.php');
define('PAYMENT_CALLBACK_TOKEN', 'ganti-token-ini');

define('MIDTRANS_SERVER_KEY', '');
define('MIDTRANS_CLIENT_KEY', '');
define('XENDIT_API_KEY', '');
