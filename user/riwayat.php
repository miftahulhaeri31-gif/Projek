<?php
$page_title = 'Riwayat Booking';
$meta_description = 'Pantau semua riwayat booking dan status pembayaran lapangan futsal.';
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$stmt = $pdo->prepare('SELECT b.id, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, b.total_harga, b.status, b.tanggal_booking, p.status AS payment_status
    FROM booking b
    INNER JOIN lapangan l ON l.id = b.lapangan_id
    INNER JOIN jadwal j ON j.id = b.jadwal_id
  INNER JOIN pembayaran p ON p.booking_id = b.id
    WHERE b.user_id = :user_id
    ORDER BY b.id DESC');
$stmt->execute(['user_id' => (int) $_SESSION['user_id']]);
$bookingList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">User / Riwayat</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Riwayat booking</h1>
      <p class="muted">Pantau status booking sebelumnya dan nominal transaksi.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_user.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill secondary" href="jadwal.php">Jadwal</a>
        <a class="pill" href="booking.php">Booking</a>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Daftar Booking</div>
      <h2>Booking milik Anda</h2>
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
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookingList)): ?>
              <tr>
                <td colspan="8" class="muted">Belum ada booking.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookingList as $index => $booking): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($booking['tanggal_booking'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(substr($booking['jam_mulai'], 0, 5) . ' - ' . substr($booking['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                  <td><?php echo render_status_badge($booking['payment_status']); ?></td>
                  <td><?php echo render_status_badge($booking['status']); ?></td>
                  <td style="display: flex; gap: 8px;">
                    <a class="pill secondary" href="pembayaran.php?booking_id=<?php echo (int) $booking['id']; ?>">Detail</a>
                    <?php if (in_array($booking['status'], ['dibayar', 'selesai'])): ?>
                      <a class="pill" href="cetak_struk.php?id=<?php echo (int) $booking['id']; ?>" target="_blank">Cetak</a>
                    <?php endif; ?>
                  </td>
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
