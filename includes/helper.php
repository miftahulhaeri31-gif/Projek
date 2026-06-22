<?php

if (!function_exists('render_status_badge')) {
    function render_status_badge(string $status): string
    {
        $normalized = strtolower(trim($status));

        $classMap = [
            'pending'  => 'status-badge status-pending',
            'dibayar'  => 'status-badge status-success',
            'sukses'   => 'status-badge status-success',
            'success'  => 'status-badge status-success',
            'selesai'  => 'status-badge status-info',
            'batal'    => 'status-badge status-failed',
            'gagal'    => 'status-badge status-failed',
            'failed'   => 'status-badge status-failed',
        ];

        $class = $classMap[$normalized] ?? 'status-badge status-info';

        return '<span class="' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('render_lapangan_status')) {
    function render_lapangan_status(string $status): string
    {
        $map = [
            'tersedia'    => 'status-badge status-success',
            'maintenance' => 'status-badge status-pending',
        ];
        $class = $map[strtolower(trim($status))] ?? 'status-badge status-info';
        return '<span class="' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
