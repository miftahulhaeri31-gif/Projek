<?php
$page_title = 'Laporan Booking';
$meta_description = 'Laporan harian dan bulanan booking lapangan futsal dengan fitur export CSV dan Excel.';
require_once __DIR__ . '/../includes/session.php';
require_role('admin', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$bulanFilter = trim($_GET['bulan'] ?? '');
$tanggalFilter = trim($_GET['tanggal'] ?? '');
$export = trim($_GET['export'] ?? '');

$sql = 'SELECT b.id, u.name AS user_name, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, b.total_harga, b.status, b.tanggal_booking, p.status AS payment_status
        FROM booking b
        INNER JOIN users u ON u.id = b.user_id
        INNER JOIN lapangan l ON l.id = b.lapangan_id
        INNER JOIN jadwal j ON j.id = b.jadwal_id
  INNER JOIN pembayaran p ON p.booking_id = b.id
        WHERE 1=1';
$params = [];

if ($tanggalFilter !== '') {
    $sql .= ' AND b.tanggal_booking = :tanggal';
    $params['tanggal'] = $tanggalFilter;
}

if ($bulanFilter !== '' && preg_match('/^\d{4}-\d{2}$/', $bulanFilter)) {
    $sql .= ' AND DATE_FORMAT(b.tanggal_booking, "%Y-%m") = :bulan';
    $params['bulan'] = $bulanFilter;
}

$sql .= ' ORDER BY b.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporanList = $stmt->fetchAll();

$totalBooking = count($laporanList);
$totalPendapatan = array_reduce($laporanList, static function (int $carry, array $item): int {
    return in_array($item['status'], ['dibayar', 'selesai'], true) ? $carry + (int) $item['total_harga'] : $carry;
}, 0);

if ($export === 'csv' || $export === 'xls') {
  if ($export === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-booking.xls');
  } else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-booking.csv');
  }

  if ($export === 'xls') {
    echo "<table border=\"1\">";
    echo "<tr><th>No</th><th>User</th><th>Lapangan</th><th>Tanggal Booking</th><th>Tanggal Jadwal</th><th>Jam</th><th>Total</th><th>Pembayaran</th><th>Status</th></tr>";

    foreach ($laporanList as $index => $row) {
      echo '<tr>';
      echo '<td>' . ($index + 1) . '</td>';
      echo '<td>' . htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8') . '</td>';
      echo '<td>' . htmlspecialchars($row['nama_lapangan'], ENT_QUOTES, 'UTF-8') . '</td>';
      echo '<td>' . htmlspecialchars($row['tanggal_booking'], ENT_QUOTES, 'UTF-8') . '</td>';
      echo '<td>' . htmlspecialchars($row['tanggal'], ENT_QUOTES, 'UTF-8') . '</td>';
      echo '<td>' . htmlspecialchars(substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8') . '</td>';
      echo '<td>' . number_format((int) $row['total_harga'], 0, ',', '.') . '</td>';
      echo '<td>' . render_status_badge($row['payment_status']) . '</td>';
      echo '<td>' . render_status_badge($row['status']) . '</td>';
      echo '</tr>';
    }

    echo '</table>';
    exit;
  }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-booking.csv');

    $output = fopen('php://output', 'w');
  fputcsv($output, ['No', 'User', 'Lapangan', 'Tanggal Booking', 'Tanggal Jadwal', 'Jam', 'Total', 'Pembayaran', 'Status']);

    foreach ($laporanList as $index => $row) {
        fputcsv($output, [
            $index + 1,
            $row['user_name'],
            $row['nama_lapangan'],
            $row['tanggal_booking'],
            $row['tanggal'],
            substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5),
            $row['total_harga'],
      $row['payment_status'],
            $row['status'] . ' / ' . $row['payment_status'],
        ]);
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">Admin / Laporan</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Laporan booking</h1>
      <p class="muted">Filter harian dan bulanan untuk rekap booking dan pendapatan.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_admin.php">Dashboard</a>
        <a class="pill secondary" href="booking.php">Booking</a>
        <a class="pill secondary" href="jadwal.php">Jadwal</a>
      </div>
    </div>

    <section class="card">
      <form class="grid two" method="get">
        <input type="date" name="tanggal" value="<?php echo htmlspecialchars($tanggalFilter, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="month" name="bulan" value="<?php echo htmlspecialchars($bulanFilter, ENT_QUOTES, 'UTF-8'); ?>">
        <button class="button" type="submit">Terapkan Filter</button>
        <a class="button secondary" href="laporan.php?export=csv&amp;tanggal=<?php echo urlencode($tanggalFilter); ?>&amp;bulan=<?php echo urlencode($bulanFilter); ?>">Export CSV</a>
        <a class="button secondary" href="laporan.php?export=xls&amp;tanggal=<?php echo urlencode($tanggalFilter); ?>&amp;bulan=<?php echo urlencode($bulanFilter); ?>">Export Excel</a>
      </form>
    </section>

    <div class="grid two">
      <div class="card">
        <strong><?php echo $totalBooking; ?></strong>
        <p class="muted">Total booking terfilter</p>
      </div>
      <div class="card">
        <strong>Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></strong>
        <p class="muted">Total pendapatan terfilter</p>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Rekap Data</div>
      <h2>Detail laporan</h2>
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
            </tr>
          </thead>
          <tbody>
            <?php if (empty($laporanList)): ?>
              <tr>
                <td colspan="8" class="muted">Belum ada data laporan sesuai filter.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($laporanList as $index => $row): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['tanggal_booking'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['tanggal'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars(substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>Rp <?php echo number_format((int) $row['total_harga'], 0, ',', '.'); ?></td>
                  <td><?php echo render_status_badge($row['payment_status']); ?></td>
                  <td><?php echo render_status_badge($row['status']); ?></td>
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
