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

$statusFilter = trim($_GET['status'] ?? '');
$tanggalFilter = trim($_GET['tanggal'] ?? '');

$sql = 'SELECT b.id, u.name AS user_name, u.email, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, b.total_harga, b.status, b.tanggal_booking, p.status AS payment_status, p.transaction_id
        FROM booking b
        INNER JOIN users u ON u.id = b.user_id
        INNER JOIN lapangan l ON l.id = b.lapangan_id
        INNER JOIN jadwal j ON j.id = b.jadwal_id
  INNER JOIN pembayaran p ON p.booking_id = b.id
        WHERE 1=1';
$params = [];

if ($statusFilter !== '') {
    $sql .= ' AND b.status = :status';
    $params['status'] = $statusFilter;
}

if ($tanggalFilter !== '') {
    $sql .= ' AND b.tanggal_booking = :tanggal';
    $params['tanggal'] = $tanggalFilter;
}

$sql .= ' ORDER BY b.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookingList = $stmt->fetchAll();

$totalBooking = count($bookingList);
$totalPendapatan = array_reduce($bookingList, static function (int $carry, array $item): int {
    return in_array($item['status'], ['dibayar', 'selesai'], true) ? $carry + (int) $item['total_harga'] : $carry;
}, 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">Admin / Booking</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Monitoring booking</h1>
      <p class="muted">Lihat seluruh booking dari user, status pembayaran, dan jadwal yang dipakai.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_admin.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill secondary" href="jadwal.php">Jadwal</a>
        <a class="pill" href="laporan.php">Laporan</a>
      </div>
    </div>

    <section class="card">
      <form class="grid two" method="get">
        <select name="status" style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
          <option value="">Semua status</option>
          <?php foreach (['pending', 'dibayar', 'selesai', 'batal'] as $status): ?>
            <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="tanggal" value="<?php echo htmlspecialchars($tanggalFilter, ENT_QUOTES, 'UTF-8'); ?>">
        <button class="button" type="submit">Filter Booking</button>
      </form>
    </section>

    <div class="grid two">
      <div class="card">
        <strong><?php echo $totalBooking; ?></strong>
        <p class="muted">Total booking tampil</p>
      </div>
      <div class="card">
        <strong>Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></strong>
        <p class="muted">Estimasi pendapatan</p>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Daftar Booking</div>
      <h2>Semua transaksi booking</h2>
      <div style="overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>User</th>
              <th>Lapangan</th>
              <th>Tanggal Booking</th>
              <th>Jadwal</th>
              <th>Total</th>
              <th>Pembayaran</th>
              <th>Status</th>
              <th>Transaksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookingList)): ?>
              <tr>
                <td colspan="9" class="muted">Belum ada booking yang sesuai filter.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookingList as $index => $booking): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td>
                    <?php echo htmlspecialchars($booking['user_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                    <span class="muted"><?php echo htmlspecialchars($booking['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($booking['tanggal_booking'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($booking['tanggal'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars(substr($booking['jam_mulai'], 0, 5) . ' - ' . substr($booking['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                  <td><?php echo render_status_badge($booking['payment_status']); ?></td>
                  <td><?php echo render_status_badge($booking['status']); ?></td>
                  <td><?php echo htmlspecialchars((string) $booking['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></td>
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
