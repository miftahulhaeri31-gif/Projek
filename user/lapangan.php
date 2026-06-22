<?php
require_once __DIR__ . '/../includes/session.php';
require_role('user', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$lapanganList = $pdo->query('SELECT id, nama_lapangan, harga_per_jam, status FROM lapangan ORDER BY nama_lapangan ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">User / Lapangan</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Pilih lapangan</h1>
      <p class="muted">Lihat daftar lapangan dan harga per jam sebelum masuk ke jadwal.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_user.php">Dashboard</a>
        <a class="pill" href="jadwal.php">Lihat Jadwal</a>
        <a class="pill secondary" href="riwayat.php">Riwayat</a>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Daftar Lapangan</div>
      <h2>Semua lapangan tersedia</h2>
      <div style="overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Lapangan</th>
              <th>Harga / Jam</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($lapanganList)): ?>
              <tr>
                <td colspan="5" class="muted">Belum ada data lapangan.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($lapanganList as $index => $lapangan): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($lapangan['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>Rp <?php echo number_format((int) $lapangan['harga_per_jam'], 0, ',', '.'); ?></td>
                  <td><?php echo htmlspecialchars($lapangan['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <a class="pill secondary" href="jadwal.php?lapangan_id=<?php echo (int) $lapangan['id']; ?>">Lihat Jadwal</a>
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
