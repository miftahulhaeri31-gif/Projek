<?php
$page_title = 'Dashboard Saya';
$meta_description = 'Ringkasan booking aktif, status pembayaran, dan akses cepat ke lapangan futsal.';
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$totalBooking = 0;
$bookingPending = 0;
$bookingDibayar = 0;
$paymentPending = 0;
$paymentSukses = 0;

if ($userId > 0) {
    $summaryStmt = $pdo->prepare('SELECT 
        COUNT(*) AS total_booking,
        SUM(CASE WHEN b.status = "pending" THEN 1 ELSE 0 END) AS booking_pending,
        SUM(CASE WHEN b.status = "dibayar" THEN 1 ELSE 0 END) AS booking_dibayar,
        SUM(CASE WHEN p.status = "pending" THEN 1 ELSE 0 END) AS payment_pending,
        SUM(CASE WHEN p.status = "sukses" THEN 1 ELSE 0 END) AS payment_sukses
      FROM booking b
      INNER JOIN pembayaran p ON p.booking_id = b.id
      WHERE b.user_id = :user_id');
    $summaryStmt->execute(['user_id' => $userId]);
    $summary = $summaryStmt->fetch() ?: [];

    $totalBooking = (int) ($summary['total_booking'] ?? 0);
    $bookingPending = (int) ($summary['booking_pending'] ?? 0);
    $bookingDibayar = (int) ($summary['booking_dibayar'] ?? 0);
    $paymentPending = (int) ($summary['payment_pending'] ?? 0);
    $paymentSukses = (int) ($summary['payment_sukses'] ?? 0);

    $recentStmt = $pdo->prepare('SELECT b.id, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, b.total_harga, b.status, p.status AS payment_status
      FROM booking b
      INNER JOIN lapangan l ON l.id = b.lapangan_id
      INNER JOIN jadwal j ON j.id = b.jadwal_id
      INNER JOIN pembayaran p ON p.booking_id = b.id
      WHERE b.user_id = :user_id
      ORDER BY b.id DESC
      LIMIT 5');
    $recentStmt->execute(['user_id' => $userId]);
    $recentBookings = $recentStmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
  <div class="card">
    <div class="kicker">User Dashboard</div>
    <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Dashboard user</h1>
    <p class="muted">Menampilkan akun, booking aktif, dan akses ke jadwal lapangan.</p>
    <div class="nav">
      <a class="pill" href="lapangan.php">Lihat Lapangan</a>
      <a class="pill" href="jadwal.php">Lihat Jadwal</a>
      <a class="pill" href="booking.php">Booking</a>
      <a class="pill" href="pembayaran.php">Pembayaran</a>
      <a class="pill secondary" href="riwayat.php">Riwayat</a>
      <a class="pill secondary" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <div class="grid two">
    <div class="card">
      <strong><?php echo $totalBooking; ?></strong>
      <p class="muted">Total booking Anda</p>
    </div>
    <div class="card">
      <strong><?php echo $bookingPending; ?></strong>
      <p class="muted">Booking pending</p>
    </div>
    <div class="card">
      <strong><?php echo $bookingDibayar; ?></strong>
      <p class="muted">Booking dibayar</p>
    </div>
    <div class="card">
      <strong><?php echo $paymentSukses; ?></strong>
      <p class="muted">Pembayaran sukses</p>
    </div>
  </div>

  <div class="grid two">
    <div class="card">
      <strong><?php echo $paymentPending; ?></strong>
      <p class="muted">Pembayaran pending</p>
    </div>
    <div class="card">
      <strong><span class="pulse-dot"></span>Live</strong>
      <p class="muted">Ringkasan data akun Anda</p>
    </div>
  </div>

  <section class="card">
    <div class="kicker">Aktivitas Terbaru</div>
    <h2>5 booking terakhir Anda</h2>
    <div style="overflow-x: auto;">
      <table class="table">
        <thead>
          <tr>
            <th>No</th>
            <th>Lapangan</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Total</th>
            <th>Pembayaran</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentBookings)): ?>
            <tr>
              <td colspan="7" class="muted">Belum ada booking.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentBookings as $index => $booking): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($booking['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(substr($booking['jam_mulai'], 0, 5) . ' - ' . substr($booking['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                <td><?php echo render_status_badge($booking['payment_status']); ?></td>
                <td><?php echo render_status_badge($booking['status']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
