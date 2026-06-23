<?php
$page_title = 'Cetak Struk Booking';
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$bookingId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

// Get booking details
$stmt = $pdo->prepare('SELECT b.*, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, u.name AS user_name, u.email, p.status AS payment_status, p.transaction_id, p.metode
    FROM booking b
    INNER JOIN lapangan l ON l.id = b.lapangan_id
    INNER JOIN jadwal j ON j.id = b.jadwal_id
    INNER JOIN users u ON u.id = b.user_id
    INNER JOIN pembayaran p ON p.booking_id = b.id
    WHERE b.id = :id AND b.user_id = :user_id');
$stmt->execute(['id' => $bookingId, 'user_id' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    echo "Data booking tidak ditemukan atau Anda tidak memiliki akses.";
    exit;
}

// Only allow printing if status is dibayar or selesai
if (!in_array($booking['status'], ['dibayar', 'selesai'])) {
    echo "Struk hanya tersedia untuk booking yang sudah lunas.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Booking #<?php echo $bookingId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            color: #111;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .struk-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #e5e5e5;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-family: 'Bebas Neue', Arial, sans-serif;
            font-size: 3rem;
            margin: 0;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            color: #707072;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-block strong {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #707072;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .info-block p {
            margin: 0;
            font-weight: 500;
            font-size: 1rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th, .table td {
            padding: 12px 0;
            border-bottom: 1px solid #e5e5e5;
            text-align: left;
        }
        .table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #707072;
            letter-spacing: 1px;
        }
        .table td {
            font-weight: 500;
        }
        .table .text-right {
            text-align: right;
        }
        .total-row {
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Bebas Neue', Arial, sans-serif;
        }
        .footer {
            text-align: center;
            font-size: 0.85rem;
            color: #707072;
            margin-top: 40px;
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
        }
        .print-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background: #111;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 9999px;
            cursor: pointer;
            border: none;
            font-family: 'Inter', sans-serif;
        }
        @media print {
            body { background: #fff; }
            .struk-container { margin: 0; padding: 0; border: none; max-width: 100%; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Cetak Struk</button>
    <div class="struk-container">
        <div class="header">
            <h1><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>Bukti Pembayaran Resmi</p>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <strong>No. Booking</strong>
                <p>#<?php echo str_pad($bookingId, 5, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="info-block" style="text-align: right;">
                <strong>Tanggal Cetak</strong>
                <p><?php echo date('d M Y, H:i'); ?></p>
            </div>
            <div class="info-block">
                <strong>Nama Pemesan</strong>
                <p><?php echo htmlspecialchars($booking['user_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="info-block" style="text-align: right;">
                <strong>Metode / ID Transaksi</strong>
                <p><?php echo htmlspecialchars($booking['metode'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($booking['transaction_id'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th class="text-right">Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($booking['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(substr($booking['jam_mulai'], 0, 5) . ' - ' . substr($booking['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-right">Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 20px;"><strong>Status:</strong></td>
                    <td class="text-right" style="padding-top: 20px; color: #007d48; font-weight: 700; text-transform: uppercase;">
                        <?php echo htmlspecialchars($booking['payment_status'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; border-bottom: none;"><strong>TOTAL DIBAYAR:</strong></td>
                    <td class="text-right total-row" style="border-bottom: none;">Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Terima kasih telah menggunakan <?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?>.<br>Harap simpan struk ini sebagai bukti pemesanan yang sah.</p>
        </div>
    </div>
    <script>
        // Auto print prompt when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
