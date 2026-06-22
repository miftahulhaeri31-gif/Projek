<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Sistem Booking Lapangan Futsal');
define('BASE_URL', 'http://localhost/PROJEK%202BLN');
define('PAYMENT_CALLBACK_URL', BASE_URL . '/callback.php');
define('PAYMENT_CALLBACK_TOKEN', 'ganti-token-ini');

define('MIDTRANS_SERVER_KEY', '');
define('MIDTRANS_CLIENT_KEY', '');
define('XENDIT_API_KEY', '');
