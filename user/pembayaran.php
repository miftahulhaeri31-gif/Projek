<?php
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$message = flash_get('success');
$error = flash_get('error');
$selectedBookingId = (int) ($_GET['booking_id'] ?? ($_POST['booking_id'] ?? 0));

if (!function_exists('render_status_badge')) {
  function render_status_badge(string $status): string
  {
    $normalized = strtolower(trim($status));

    $classMap = [
      'pending' => 'status-badge status-pending',
      'sukses' => 'status-badge status-success',
      'success' => 'status-badge status-success',
      'dibayar' => 'status-badge status-success',
      'gagal' => 'status-badge status-failed',
      'failed' => 'status-badge status-failed',
      'batal' => 'status-badge status-failed',
      'selesai' => 'status-badge status-info',
    ];

    $class = $classMap[$normalized] ?? 'status-badge status-info';

    return '<span class="' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId <= 0) {
      flash_set('error', 'Booking tidak valid.');
      header('Location: pembayaran.php');
      exit;
    } else {
        $stmt = $pdo->prepare('SELECT b.id, b.user_id, b.status AS booking_status, p.id AS payment_id, p.status AS payment_status, p.transaction_id, p.metode, b.total_harga, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai
            FROM booking b
            INNER JOIN pembayaran p ON p.booking_id = b.id
            INNER JOIN lapangan l ON l.id = b.lapangan_id
            INNER JOIN jadwal j ON j.id = b.jadwal_id
            WHERE b.id = :id AND b.user_id = :user_id LIMIT 1');
        $stmt->execute([
            'id' => $bookingId,
            'user_id' => (int) $_SESSION['user_id'],
        ]);
        $booking = $stmt->fetch();

        if (!$booking) {
          flash_set('error', 'Data booking tidak ditemukan.');
          header('Location: pembayaran.php');
          exit;
        } else {
            try {
                if ($action === 'pay') {
                    $transactionId = $booking['transaction_id'] ?: 'TRX-' . date('YmdHis') . '-' . $bookingId;
                    $updatePayment = $pdo->prepare('UPDATE pembayaran SET status = :status, transaction_id = :transaction_id WHERE booking_id = :booking_id');
                    $updatePayment->execute([
                        'status' => 'sukses',
                        'transaction_id' => $transactionId,
                        'booking_id' => $bookingId,
                    ]);

                    $updateBooking = $pdo->prepare('UPDATE booking SET status = :status WHERE id = :id');
                    $updateBooking->execute([
                        'status' => 'dibayar',
                        'id' => $bookingId,
                    ]);

                    flash_set('success', 'Pembayaran berhasil dikonfirmasi.');
                    header('Location: pembayaran.php?booking_id=' . $bookingId);
                    exit;
                } elseif ($action === 'fail') {
                    $updatePayment = $pdo->prepare('UPDATE pembayaran SET status = :status WHERE booking_id = :booking_id');
                    $updatePayment->execute([
                        'status' => 'gagal',
                        'booking_id' => $bookingId,
                    ]);

                    $updateBooking = $pdo->prepare('UPDATE booking SET status = :status WHERE id = :id');
                    $updateBooking->execute([
                        'status' => 'batal',
                        'id' => $bookingId,
                    ]);

                    flash_set('success', 'Pembayaran ditandai gagal.');
                    header('Location: pembayaran.php?booking_id=' . $bookingId);
                    exit;
                }
            } catch (PDOException $e) {
                  flash_set('error', 'Gagal memperbarui status pembayaran.');
                  header('Location: pembayaran.php?booking_id=' . $bookingId);
                  exit;
            }
        }
    }
}

$bookingListStmt = $pdo->prepare('SELECT b.id, b.status AS booking_status, b.total_harga, p.status AS payment_status, p.metode, p.transaction_id, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai
    FROM booking b
    INNER JOIN pembayaran p ON p.booking_id = b.id
    INNER JOIN lapangan l ON l.id = b.lapangan_id
    INNER JOIN jadwal j ON j.id = b.jadwal_id
    WHERE b.user_id = :user_id
    ORDER BY b.id DESC');
$bookingListStmt->execute(['user_id' => (int) $_SESSION['user_id']]);
$bookingList = $bookingListStmt->fetchAll();

$currentBooking = null;
if ($selectedBookingId > 0) {
    foreach ($bookingList as $bookingItem) {
        if ((int) $bookingItem['id'] === $selectedBookingId) {
            $currentBooking = $bookingItem;
            break;
        }
    }
}

if ($currentBooking === null && !empty($bookingList)) {
    $currentBooking = $bookingList[0];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">User / Pembayaran</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Pembayaran booking</h1>
      <p class="muted">Konfirmasi pembayaran booking yang masih pending dan lihat status transaksi.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_user.php">Dashboard</a>
        <a class="pill secondary" href="booking.php">Booking</a>
        <a class="pill secondary" href="riwayat.php">Riwayat</a>
        <a class="pill" href="lapangan.php">Lapangan</a>
      </div>
    </div>

    <?php if ($message !== ''): ?>
      <div class="card" style="border-color: #007d48; color: #007d48; background: #f3fbf7;">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="card" style="border-color: #d30005; color: #d30005; background: #fff5f5;">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="grid two">
      <section class="card">
        <div class="kicker">Daftar Pembayaran</div>
        <h2>Pilih booking</h2>
        <div style="overflow-x: auto;">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($bookingList)): ?>
                <tr>
                  <td colspan="6" class="muted">Belum ada booking untuk dibayar.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($bookingList as $index => $booking): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($booking['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>Rp <?php echo number_format((int) $booking['total_harga'], 0, ',', '.'); ?></td>
                    <td><?php echo render_status_badge($booking['payment_status']); ?><br><?php echo render_status_badge($booking['booking_status']); ?></td>
                    <td><a class="pill secondary" href="pembayaran.php?booking_id=<?php echo (int) $booking['id']; ?>">Lihat</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card">
        <div class="kicker">Konfirmasi</div>
        <h2>Detail transaksi</h2>
        <?php if ($currentBooking): ?>
          <div class="card" style="margin-bottom: 16px;">
            <p class="muted">Lapangan: <?php echo htmlspecialchars($currentBooking['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Tanggal: <?php echo htmlspecialchars($currentBooking['tanggal'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Jam: <?php echo htmlspecialchars(substr($currentBooking['jam_mulai'], 0, 5) . ' - ' . substr($currentBooking['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Total: Rp <?php echo number_format((int) $currentBooking['total_harga'], 0, ',', '.'); ?></p>
            <p class="muted">Metode: <?php echo htmlspecialchars($currentBooking['metode'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Transaction ID: <?php echo htmlspecialchars((string) $currentBooking['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Status bayar: <?php echo render_status_badge($currentBooking['payment_status']); ?></p>
            <p class="muted">Status booking: <?php echo render_status_badge($currentBooking['booking_status']); ?></p>
          </div>

          <form class="form" method="post">
            <input type="hidden" name="booking_id" value="<?php echo (int) $currentBooking['id']; ?>">
            <button class="button" type="submit" name="action" value="pay" <?php echo $currentBooking['payment_status'] === 'sukses' ? 'disabled' : ''; ?>>Bayar Sekarang</button>
            <button class="button secondary" type="submit" name="action" value="fail" <?php echo $currentBooking['payment_status'] === 'sukses' ? 'disabled' : ''; ?>>Tandai Gagal</button>
          </form>
        <?php else: ?>
          <p class="muted">Belum ada booking yang tersedia.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
