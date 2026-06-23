<?php
$page_title = 'Pembayaran';
$meta_description = 'Konfirmasi dan pantau status pembayaran booking lapangan futsal.';
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

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
                    $metode = $_POST['metode'] ?? 'gateway';
                    $transactionId = $booking['transaction_id'] ?: 'TRX-' . date('YmdHis') . '-' . $bookingId;
                    $updatePayment = $pdo->prepare('UPDATE pembayaran SET status = :status, transaction_id = :transaction_id, metode = :metode WHERE booking_id = :booking_id');
                    $updatePayment->execute([
                        'status' => 'sukses',
                        'transaction_id' => $transactionId,
                        'metode' => $metode,
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

$selectedBookingId = (int) ($_GET['booking_id'] ?? 0);
$message = flash_get('success');
$error = flash_get('error');

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

          <?php if ($currentBooking['payment_status'] === 'sukses'): ?>
            <p class="muted" style="margin-top: 20px; font-weight: 500; color: #007d48;">✅ Pembayaran berhasil. Silakan cek menu Riwayat untuk cetak struk.</p>
          <?php else: ?>
            <div id="payment-selection">
              <h3 style="margin-top: 20px;">Pilih Metode Pembayaran</h3>
              <select id="metode-select" class="form-input" style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit; width: 100%; margin-bottom: 20px;" onchange="showPaymentMethod(this.value)">
                <option value="">-- Pilih Metode --</option>
                <option value="QRIS">QRIS</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="Tunai">Tunai di Kasir</option>
              </select>
            </div>

            <!-- Formulir untuk dikirim -->
            <form class="form" method="post" id="form-payment" style="display: none;">
              <input type="hidden" name="booking_id" value="<?php echo (int) $currentBooking['id']; ?>">
              <input type="hidden" name="metode" id="input-metode" value="">
              
              <!-- Container instruksi dinamis -->
              <div id="instruksi-container" style="margin-bottom: 20px; background: #fafafa; padding: 20px; border: 1px solid #e5e5e5; border-radius: 14px;">
              </div>

              <button class="button" type="submit" name="action" value="pay" style="width: 100%;">Konfirmasi Pembayaran</button>
            </form>

            <script>
            function showPaymentMethod(method) {
                const form = document.getElementById('form-payment');
                const inputMetode = document.getElementById('input-metode');
                const instruksiContainer = document.getElementById('instruksi-container');
                const trxId = "<?php echo htmlspecialchars((string) ($currentBooking['transaction_id'] ?: 'TRX-' . date('YmdHis') . '-' . $currentBooking['id']), ENT_QUOTES, 'UTF-8'); ?>";

                if (method === '') {
                    form.style.display = 'none';
                    return;
                }

                form.style.display = 'block';
                inputMetode.value = method;

                let html = '';
                if (method === 'QRIS') {
                    html = `
                        <div style="text-align: center;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${trxId}" alt="QRIS" style="margin-bottom: 16px; border-radius: 8px;">
                            <p style="font-weight: 700; font-size: 1.2rem; margin: 0;">Scan QRIS ini</p>
                            <p class="muted" style="margin-top: 4px;">Setelah melakukan scan dan bayar di aplikasi E-Wallet/M-Banking Anda, tekan tombol konfirmasi di bawah.</p>
                        </div>
                    `;
                } else if (method === 'Transfer Bank') {
                    html = `
                        <div>
                            <p style="font-weight: 700; margin-bottom: 8px;">Transfer ke Rekening Berikut:</p>
                            <div style="padding: 12px; border: 1px solid #e5e5e5; border-radius: 8px; margin-bottom: 8px; background: #fff;">
                                <strong>BCA</strong>: 1234567890<br>
                                a.n. Futsal Arena
                            </div>
                            <div style="padding: 12px; border: 1px solid #e5e5e5; border-radius: 8px; background: #fff;">
                                <strong>Mandiri</strong>: 0987654321<br>
                                a.n. Futsal Arena
                            </div>
                            <p class="muted" style="margin-top: 12px;">Pastikan nominal transfer sesuai. Setelah transfer berhasil, tekan tombol konfirmasi di bawah.</p>
                        </div>
                    `;
                } else if (method === 'Tunai') {
                    html = `
                        <div style="text-align: center;">
                            <p style="font-weight: 700; font-size: 1.2rem; margin: 0;">Bayar Tunai di Kasir</p>
                            <p class="muted" style="margin-top: 4px;">Silakan tunjukkan Nomor Transaksi <strong>${trxId}</strong> ke resepsionis untuk melakukan pembayaran tunai.</p>
                            <p class="muted" style="font-size: 0.85rem; margin-top: 12px;">*(Sebagai simulasi, tekan tombol konfirmasi di bawah agar sistem menganggap kasir telah menerima uang Anda)*</p>
                        </div>
                    `;
                }

                instruksiContainer.innerHTML = html;
            }
            </script>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted">Belum ada booking yang tersedia.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
