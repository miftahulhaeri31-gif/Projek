<?php
require_once __DIR__ . '/../includes/session.php';
require_role('admin', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

if (!function_exists('render_status_badge')) {
  function render_status_badge(string $status): string
  {
    $normalized = strtolower(trim($status));

    $classMap = [
      'pending' => 'status-badge status-pending',
      'dibayar' => 'status-badge status-success',
      'sukses' => 'status-badge status-success',
      'selesai' => 'status-badge status-info',
      'batal' => 'status-badge status-failed',
      'gagal' => 'status-badge status-failed',
    ];

    $class = $classMap[$normalized] ?? 'status-badge status-info';

    return '<span class="' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
  }
}

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalLapangan = (int) $pdo->query('SELECT COUNT(*) FROM lapangan')->fetchColumn();
$totalJadwal = (int) $pdo->query('SELECT COUNT(*) FROM jadwal')->fetchColumn();
$totalBooking = (int) $pdo->query('SELECT COUNT(*) FROM booking')->fetchColumn();
$totalPendapatan = (int) $pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM booking WHERE status IN ('dibayar', 'selesai')")->fetchColumn();
$bookingPending = (int) $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'pending'")->fetchColumn();
$paymentPending = (int) $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status = 'pending'")->fetchColumn();

$recentBookingStmt = $pdo->query('SELECT b.id, u.name AS user_name, l.nama_lapangan, b.status, b.total_harga
    FROM booking b
    INNER JOIN users u ON u.id = b.user_id
    INNER JOIN lapangan l ON l.id = b.lapangan_id
    ORDER BY b.id DESC
    LIMIT 5');
$recentBookings = $recentBookingStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
  <div class="card">
    <div class="kicker">Admin Dashboard</div>
    <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Dashboard admin</h1>
    <p class="muted">Tempat statistik booking, pendapatan, dan monitoring operasional.</p>
    <div class="nav">
      <a class="pill" href="users.php">Kelola User</a>
      <a class="pill" href="lapangan.php">Kelola Lapangan</a>
      <a class="pill" href="jadwal.php">Kelola Jadwal</a>
      <a class="pill" href="booking.php">Monitoring Booking</a>
      <a class="pill" href="laporan.php">Laporan</a>
      <a class="pill secondary" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <div class="grid two">
    <div class="card">
      <strong><?php echo $totalUsers; ?></strong>
      <p class="muted">Total user</p>
    </div>
    <div class="card">
      <strong><?php echo $totalLapangan; ?></strong>
      <p class="muted">Total lapangan</p>
    </div>
    <div class="card">
      <strong><?php echo $totalJadwal; ?></strong>
      <p class="muted">Total jadwal</p>
    </div>
    <div class="card">
      <strong><?php echo $totalBooking; ?></strong>
      <p class="muted">Total booking</p>
    </div>
  </div>

  <div class="grid two">
    <div class="card">
      <strong>Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></strong>
      <p class="muted">Pendapatan dari booking dibayar/selesai</p>
    </div>
    <div class="card">
      <strong><?php echo $bookingPending; ?></strong>
      <p class="muted">Booking masih pending</p>
    </div>
    <div class="card">
      <strong><?php echo $paymentPending; ?></strong>
      <p class="muted">Pembayaran pending</p>
    </div>
    <div class="card">
      <strong>Live</strong>
      <p class="muted">Ringkasan data real-time</p>
    </div>
  </div>

  <section class="card">
    <div class="kicker">Aktivitas Terbaru</div>
    <h2>5 booking terakhir</h2>
    <div style="overflow-x: auto;">
      <table class="table">
        <thead>
          <tr>
            <th>No</th>
            <th>User</th>
            <th>Lapangan</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentBookings)): ?>
            <tr>
              <td colspan="5" class="muted">Belum ada booking masuk.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentBookings as $index => $booking): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($booking['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
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
