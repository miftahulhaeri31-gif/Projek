<?php
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$message = flash_get('success');
$error = flash_get('error');
$selectedJadwal = null;
$createdBookingId = (int) ($_GET['booking_id'] ?? 0);
$lapanganList = $pdo->query('SELECT id, nama_lapangan FROM lapangan WHERE status = "tersedia" ORDER BY nama_lapangan ASC')->fetchAll();

$jadwalId = (int) ($_GET['jadwal_id'] ?? ($_POST['jadwal_id'] ?? 0));

if ($jadwalId > 0) {
    $stmt = $pdo->prepare('SELECT j.id, j.lapangan_id, l.nama_lapangan, l.harga_per_jam, j.tanggal, j.jam_mulai, j.jam_selesai, j.status
        FROM jadwal j
        INNER JOIN lapangan l ON l.id = j.lapangan_id
        WHERE j.id = :id LIMIT 1');
    $stmt->execute(['id' => $jadwalId]);
    $selectedJadwal = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jadwalId = (int) ($_POST['jadwal_id'] ?? 0);

    if ($jadwalId <= 0) {
      flash_set('error', 'Jadwal wajib dipilih.');
      header('Location: booking.php');
      exit;
    } else {
        $stmt = $pdo->prepare('SELECT j.id, j.lapangan_id, l.nama_lapangan, l.harga_per_jam, j.tanggal, j.jam_mulai, j.jam_selesai, j.status
            FROM jadwal j
            INNER JOIN lapangan l ON l.id = j.lapangan_id
            WHERE j.id = :id LIMIT 1');
        $stmt->execute(['id' => $jadwalId]);
        $selectedJadwal = $stmt->fetch();

        if (!$selectedJadwal) {
          flash_set('error', 'Jadwal tidak ditemukan.');
          header('Location: booking.php');
          exit;
        } elseif ($selectedJadwal['status'] !== 'tersedia') {
          flash_set('error', 'Jadwal sudah tidak tersedia.');
          header('Location: booking.php?jadwal_id=' . $jadwalId);
          exit;
        } else {
            $tanggalBooking = $selectedJadwal['tanggal'];
            $durasiMulai = new DateTime($selectedJadwal['jam_mulai']);
            $durasiSelesai = new DateTime($selectedJadwal['jam_selesai']);
            $durasiJam = max(1, (int) $durasiMulai->diff($durasiSelesai)->h);
            $totalHarga = (int) $selectedJadwal['harga_per_jam'] * $durasiJam;

            try {
                $pdo->beginTransaction();

                $bookingInsert = $pdo->prepare('INSERT INTO booking (user_id, lapangan_id, jadwal_id, tanggal_booking, total_harga, status) VALUES (:user_id, :lapangan_id, :jadwal_id, :tanggal_booking, :total_harga, :status)');
                $bookingInsert->execute([
                    'user_id' => (int) $_SESSION['user_id'],
                    'lapangan_id' => (int) $selectedJadwal['lapangan_id'],
                    'jadwal_id' => (int) $selectedJadwal['id'],
                    'tanggal_booking' => $tanggalBooking,
                    'total_harga' => $totalHarga,
                    'status' => 'pending',
                ]);

                $bookingId = (int) $pdo->lastInsertId();

                $paymentInsert = $pdo->prepare('INSERT INTO pembayaran (booking_id, metode, status, transaction_id) VALUES (:booking_id, :metode, :status, :transaction_id)');
                $paymentInsert->execute([
                    'booking_id' => $bookingId,
                    'metode' => 'midtrans',
                    'status' => 'pending',
                    'transaction_id' => 'TRX-' . date('YmdHis') . '-' . $bookingId,
                ]);

                $jadwalUpdate = $pdo->prepare('UPDATE jadwal SET status = :status WHERE id = :id');
                $jadwalUpdate->execute([
                    'status' => 'dibooking',
                    'id' => (int) $selectedJadwal['id'],
                ]);

                $pdo->commit();

                flash_set('success', 'Booking berhasil disimpan.');
                header('Location: booking.php?jadwal_id=' . $jadwalId . '&booking_id=' . $bookingId);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash_set('error', 'Gagal menyimpan booking.');
                header('Location: booking.php?jadwal_id=' . $jadwalId);
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">User / Booking</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Booking online</h1>
      <p class="muted">Pilih jadwal yang tersedia lalu simpan booking ke sistem.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_user.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill secondary" href="jadwal.php">Jadwal</a>
        <a class="pill" href="riwayat.php">Riwayat</a>
      </div>
    </div>

    <?php if ($message !== ''): ?>
      <div class="card" style="border-color: #007d48; color: #007d48; background: #f3fbf7;">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        <?php if ($createdBookingId > 0): ?>
          <div class="nav" style="margin-top: 12px;">
            <a class="pill" href="pembayaran.php?booking_id=<?php echo (int) $createdBookingId; ?>">Lanjut ke Pembayaran</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="card" style="border-color: #d30005; color: #d30005; background: #fff5f5;">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="grid two">
      <section class="card">
        <div class="kicker">Pilih Jadwal</div>
        <h2>Gunakan slot yang tersedia</h2>
        <form class="form" method="get" action="jadwal.php">
          <select name="lapangan_id" style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
            <option value="0">Semua lapangan</option>
            <?php foreach ($lapanganList as $lapangan): ?>
              <option value="<?php echo (int) $lapangan['id']; ?>"><?php echo htmlspecialchars($lapangan['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="button" type="submit">Lihat Jadwal</button>
        </form>

        <?php if ($selectedJadwal): ?>
          <div class="card" style="margin-top: 18px;">
            <div class="kicker">Jadwal Dipilih</div>
            <h3><?php echo htmlspecialchars($selectedJadwal['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="muted">Tanggal: <?php echo htmlspecialchars($selectedJadwal['tanggal'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Jam: <?php echo htmlspecialchars(substr($selectedJadwal['jam_mulai'], 0, 5) . ' - ' . substr($selectedJadwal['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted">Harga / jam: Rp <?php echo number_format((int) $selectedJadwal['harga_per_jam'], 0, ',', '.'); ?></p>
          </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <div class="kicker">Konfirmasi Booking</div>
        <h2>Simpan pesanan</h2>
        <form class="form" method="post">
          <input type="hidden" name="jadwal_id" value="<?php echo (int) ($selectedJadwal['id'] ?? $jadwalId); ?>">
          <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" disabled>
          <input type="text" value="<?php echo htmlspecialchars($selectedJadwal['nama_lapangan'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>" disabled>
          <input type="text" value="<?php echo htmlspecialchars($selectedJadwal['tanggal'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>" disabled>
          <input type="text" value="<?php echo htmlspecialchars(isset($selectedJadwal['jam_mulai']) ? substr($selectedJadwal['jam_mulai'], 0, 5) . ' - ' . substr($selectedJadwal['jam_selesai'], 0, 5) : '-', ENT_QUOTES, 'UTF-8'); ?>" disabled>
          <button class="button" type="submit" <?php echo $selectedJadwal ? '' : 'disabled'; ?>>Simpan Booking</button>
        </form>
      </section>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
