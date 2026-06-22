<?php
require_once __DIR__ . '/../includes/session.php';
require_role('admin', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';
$editingLapangan = [
    'id' => '',
    'nama_lapangan' => '',
    'harga_per_jam' => '',
    'status' => 'tersedia',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim($_POST['id'] ?? '');
        $namaLapangan = trim($_POST['nama_lapangan'] ?? '');
        $hargaPerJam = trim($_POST['harga_per_jam'] ?? '');
        $status = $_POST['status'] ?? 'tersedia';

        if ($namaLapangan === '' || $hargaPerJam === '') {
            $error = 'Nama lapangan dan harga per jam wajib diisi.';
        } elseif (!is_numeric($hargaPerJam) || (int) $hargaPerJam < 0) {
            $error = 'Harga per jam harus berupa angka valid.';
        } elseif (!in_array($status, ['tersedia', 'maintenance'], true)) {
            $error = 'Status lapangan tidak valid.';
        } else {
            try {
                if ($id !== '') {
                    $stmt = $pdo->prepare('UPDATE lapangan SET nama_lapangan = :nama_lapangan, harga_per_jam = :harga_per_jam, status = :status WHERE id = :id');
                    $stmt->execute([
                        'nama_lapangan' => $namaLapangan,
                        'harga_per_jam' => (int) $hargaPerJam,
                        'status' => $status,
                        'id' => (int) $id,
                    ]);
                    $message = 'Data lapangan berhasil diperbarui.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO lapangan (nama_lapangan, harga_per_jam, status) VALUES (:nama_lapangan, :harga_per_jam, :status)');
                    $stmt->execute([
                        'nama_lapangan' => $namaLapangan,
                        'harga_per_jam' => (int) $hargaPerJam,
                        'status' => $status,
                    ]);
                    $message = 'Data lapangan berhasil ditambahkan.';
                }
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan data lapangan.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM lapangan WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'Data lapangan berhasil dihapus.';
            } catch (PDOException $e) {
                $error = 'Lapangan tidak bisa dihapus karena masih dipakai jadwal atau booking.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT id, nama_lapangan, harga_per_jam, status FROM lapangan WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $data = $stmt->fetch();

    if ($data) {
        $editingLapangan = $data;
    }
}

$lapanganList = $pdo->query('SELECT id, nama_lapangan, harga_per_jam, status FROM lapangan ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">Admin / Lapangan</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Kelola lapangan</h1>
      <p class="muted">Tambah, ubah, dan hapus data lapangan yang akan dipakai di booking.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_admin.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
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
        <div class="kicker"><?php echo $editingLapangan['id'] !== '' ? 'Edit Lapangan' : 'Tambah Lapangan'; ?></div>
        <h2><?php echo $editingLapangan['id'] !== '' ? 'Perbarui data lapangan' : 'Masukkan data lapangan baru'; ?></h2>
        <form class="form" method="post">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $editingLapangan['id'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="text" name="nama_lapangan" placeholder="Nama lapangan" value="<?php echo htmlspecialchars((string) $editingLapangan['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <input type="number" min="0" step="1" name="harga_per_jam" placeholder="Harga per jam" value="<?php echo htmlspecialchars((string) $editingLapangan['harga_per_jam'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <select name="status" class="form-input" required style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
            <option value="tersedia" <?php echo $editingLapangan['status'] === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
            <option value="maintenance" <?php echo $editingLapangan['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
          </select>
          <button class="button" type="submit"><?php echo $editingLapangan['id'] !== '' ? 'Update Lapangan' : 'Simpan Lapangan'; ?></button>
        </form>
      </section>

      <section class="card">
        <div class="kicker">Ringkasan</div>
        <h2>Statistik singkat</h2>
        <div class="grid two">
          <div class="card">
            <strong><?php echo count($lapanganList); ?></strong>
            <p class="muted">Total lapangan</p>
          </div>
          <div class="card">
            <strong><?php echo count(array_filter($lapanganList, static function ($item) { return $item['status'] === 'tersedia'; })); ?></strong>
            <p class="muted">Lapangan tersedia</p>
          </div>
        </div>
      </section>
    </div>

    <section class="card">
      <div class="kicker">Daftar Lapangan</div>
      <h2>Data lapangan tersimpan</h2>
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
                    <div class="nav">
                      <a class="pill secondary" href="lapangan.php?edit=<?php echo (int) $lapangan['id']; ?>">Edit</a>
                      <form method="post" onsubmit="return confirm('Hapus data lapangan ini?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $lapangan['id']; ?>">
                        <button class="pill" type="submit" style="border: none; cursor: pointer;">Hapus</button>
                      </form>
                    </div>
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
