<?php
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$lapanganId = (int) ($_GET['lapangan_id'] ?? 0);
$tanggal = trim($_GET['tanggal'] ?? '');

$lapanganList = $pdo->query('SELECT id, nama_lapangan FROM lapangan ORDER BY nama_lapangan ASC')->fetchAll();

$sql = 'SELECT j.id, j.lapangan_id, l.nama_lapangan, l.harga_per_jam, j.tanggal, j.jam_mulai, j.jam_selesai, j.status
        FROM jadwal j
        INNER JOIN lapangan l ON l.id = j.lapangan_id
        WHERE 1=1';
$params = [];

if ($lapanganId > 0) {
    $sql .= ' AND j.lapangan_id = :lapangan_id';
    $params['lapangan_id'] = $lapanganId;
}

if ($tanggal !== '') {
    $sql .= ' AND j.tanggal = :tanggal';
    $params['tanggal'] = $tanggal;
}

$sql .= ' ORDER BY j.tanggal ASC, j.jam_mulai ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jadwalList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">User / Jadwal</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Jadwal lapangan</h1>
      <p class="muted">Filter jadwal berdasarkan lapangan dan tanggal, lalu lanjut booking dari slot yang tersedia.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_user.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill" href="booking.php">Booking</a>
      </div>
    </div>

    <section class="card">
      <form class="grid two" method="get">
        <select name="lapangan_id" required style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
          <option value="0">Semua lapangan</option>
          <?php foreach ($lapanganList as $lapangan): ?>
            <option value="<?php echo (int) $lapangan['id']; ?>" <?php echo $lapanganId === (int) $lapangan['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($lapangan['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="tanggal" value="<?php echo htmlspecialchars($tanggal, ENT_QUOTES, 'UTF-8'); ?>">
        <button class="button" type="submit">Filter Jadwal</button>
      </form>
    </section>

    <section class="card">
      <div class="kicker">Daftar Jadwal</div>
      <h2>Slot yang bisa dibooking</h2>
      <div style="overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Lapangan</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jadwalList)): ?>
              <tr>
                <td colspan="6" class="muted">Belum ada jadwal yang sesuai filter.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($jadwalList as $index => $jadwal): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($jadwal['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($jadwal['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(substr($jadwal['jam_mulai'], 0, 5) . ' - ' . substr($jadwal['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($jadwal['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <?php if ($jadwal['status'] === 'tersedia'): ?>
                      <a class="pill" href="booking.php?jadwal_id=<?php echo (int) $jadwal['id']; ?>">Booking</a>
                    <?php else: ?>
                      <span class="muted">Tidak tersedia</span>
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
