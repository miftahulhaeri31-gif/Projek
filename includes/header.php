<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helper.php';

$page_title       = isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' — ' . APP_NAME : APP_NAME;
$meta_description = isset($meta_description) ? htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8') : 'Sistem booking lapangan futsal berbasis PHP Native dan MySQL dengan alur admin dan user yang sudah siap dipakai.';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title><?php echo $page_title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
