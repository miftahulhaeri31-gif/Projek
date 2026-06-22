<?php
require_once __DIR__ . '/../includes/session.php';
require_role('admin', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';
$editingJadwal = [
    'id' => '',
    'lapangan_id' => '',
    'tanggal' => '',
    'jam_mulai' => '',
    'jam_selesai' => '',
    'status' => 'tersedia',
];

$lapanganList = $pdo->query('SELECT id, nama_lapangan FROM lapangan ORDER BY nama_lapangan ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim($_POST['id'] ?? '');
        $lapanganId = trim($_POST['lapangan_id'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? '');
        $jamMulai = trim($_POST['jam_mulai'] ?? '');
        $jamSelesai = trim($_POST['jam_selesai'] ?? '');
        $status = $_POST['status'] ?? 'tersedia';

        if ($lapanganId === '' || $tanggal === '' || $jamMulai === '' || $jamSelesai === '') {
            $error = 'Semua field jadwal wajib diisi.';
        } elseif (!ctype_digit($lapanganId)) {
            $error = 'Lapangan tidak valid.';
        } elseif (strtotime($jamMulai) === false || strtotime($jamSelesai) === false) {
            $error = 'Format jam tidak valid.';
        } elseif ($jamMulai >= $jamSelesai) {
            $error = 'Jam mulai harus lebih kecil dari jam selesai.';
        } elseif (!in_array($status, ['tersedia', 'dibooking'], true)) {
            $error = 'Status jadwal tidak valid.';
        } else {
            $lapanganCheck = $pdo->prepare('SELECT id FROM lapangan WHERE id = :id LIMIT 1');
            $lapanganCheck->execute(['id' => (int) $lapanganId]);

            if (!$lapanganCheck->fetch()) {
                $error = 'Lapangan yang dipilih tidak ditemukan.';
            } else {
                $conflictSql = 'SELECT COUNT(*) FROM jadwal WHERE lapangan_id = :lapangan_id AND tanggal = :tanggal AND NOT (jam_selesai <= :jam_mulai OR jam_mulai >= :jam_selesai)';
                $params = [
                    'lapangan_id' => (int) $lapanganId,
                    'tanggal' => $tanggal,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                ];

                if ($id !== '') {
                    $conflictSql .= ' AND id <> :id';
                    $params['id'] = (int) $id;
                }

                $conflictCheck = $pdo->prepare($conflictSql);
                $conflictCheck->execute($params);

                if ((int) $conflictCheck->fetchColumn() > 0) {
                    $error = 'Jadwal bentrok dengan jadwal lain pada lapangan dan tanggal yang sama.';
                } else {
                    try {
                        if ($id !== '') {
                            $stmt = $pdo->prepare('UPDATE jadwal SET lapangan_id = :lapangan_id, tanggal = :tanggal, jam_mulai = :jam_mulai, jam_selesai = :jam_selesai, status = :status WHERE id = :id');
                            $stmt->execute([
                                'lapangan_id' => (int) $lapanganId,
                                'tanggal' => $tanggal,
                                'jam_mulai' => $jamMulai,
                                'jam_selesai' => $jamSelesai,
                                'status' => $status,
                                'id' => (int) $id,
                            ]);
                            $message = 'Data jadwal berhasil diperbarui.';
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status) VALUES (:lapangan_id, :tanggal, :jam_mulai, :jam_selesai, :status)');
                            $stmt->execute([
                                'lapangan_id' => (int) $lapanganId,
                                'tanggal' => $tanggal,
                                'jam_mulai' => $jamMulai,
                                'jam_selesai' => $jamSelesai,
                                'status' => $status,
                            ]);
                            $message = 'Data jadwal berhasil ditambahkan.';
                        }
                    } catch (PDOException $e) {
                        $error = 'Gagal menyimpan data jadwal.';
                    }
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM jadwal WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'Data jadwal berhasil dihapus.';
            } catch (PDOException $e) {
                $error = 'Jadwal tidak bisa dihapus karena sudah terhubung ke data lain.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT id, lapangan_id, tanggal, jam_mulai, jam_selesai, status FROM jadwal WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $data = $stmt->fetch();

    if ($data) {
        $editingJadwal = $data;
    }
}

$jadwalList = $pdo->query('SELECT j.id, j.lapangan_id, l.nama_lapangan, j.tanggal, j.jam_mulai, j.jam_selesai, j.status FROM jadwal j INNER JOIN lapangan l ON l.id = j.lapangan_id ORDER BY j.tanggal DESC, j.jam_mulai DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">Admin / Jadwal</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Kelola jadwal lapangan</h1>
      <p class="muted">Atur slot waktu yang bisa dipakai user untuk booking.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_admin.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill" href="jadwal.php">Jadwal</a>
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
        <div class="kicker"><?php echo $editingJadwal['id'] !== '' ? 'Edit Jadwal' : 'Tambah Jadwal'; ?></div>
        <h2><?php echo $editingJadwal['id'] !== '' ? 'Perbarui slot jadwal' : 'Masukkan slot jadwal baru'; ?></h2>
        <form class="form" method="post">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $editingJadwal['id'], ENT_QUOTES, 'UTF-8'); ?>">
          <select name="lapangan_id" required style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
            <option value="">Pilih lapangan</option>
            <?php foreach ($lapanganList as $lapangan): ?>
              <option value="<?php echo (int) $lapangan['id']; ?>" <?php echo (string) $editingJadwal['lapangan_id'] === (string) $lapangan['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($lapangan['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="date" name="tanggal" value="<?php echo htmlspecialchars((string) $editingJadwal['tanggal'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <input type="time" name="jam_mulai" value="<?php echo htmlspecialchars((string) $editingJadwal['jam_mulai'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <input type="time" name="jam_selesai" value="<?php echo htmlspecialchars((string) $editingJadwal['jam_selesai'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <select name="status" required style="min-height: 48px; padding: 0 14px; border: 1px solid #e5e5e5; border-radius: 14px; font: inherit;">
            <option value="tersedia" <?php echo $editingJadwal['status'] === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
            <option value="dibooking" <?php echo $editingJadwal['status'] === 'dibooking' ? 'selected' : ''; ?>>Dibooking</option>
          </select>
          <button class="button" type="submit"><?php echo $editingJadwal['id'] !== '' ? 'Update Jadwal' : 'Simpan Jadwal'; ?></button>
        </form>
      </section>

      <section class="card">
        <div class="kicker">Ringkasan</div>
        <h2>Statistik singkat</h2>
        <div class="grid two">
          <div class="card">
            <strong><?php echo count($jadwalList); ?></strong>
            <p class="muted">Total jadwal</p>
          </div>
          <div class="card">
            <strong><?php echo count(array_filter($jadwalList, static function ($item) { return $item['status'] === 'tersedia'; })); ?></strong>
            <p class="muted">Jadwal tersedia</p>
          </div>
        </div>
      </section>
    </div>

    <section class="card">
      <div class="kicker">Daftar Jadwal</div>
      <h2>Data jadwal tersimpan</h2>
      <div style="overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Lapangan</th>
              <th>Tanggal</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jadwalList)): ?>
              <tr>
                <td colspan="7" class="muted">Belum ada data jadwal.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($jadwalList as $index => $jadwal): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($jadwal['nama_lapangan'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($jadwal['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(substr($jadwal['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(substr($jadwal['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($jadwal['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <div class="nav">
                      <a class="pill secondary" href="jadwal.php?edit=<?php echo (int) $jadwal['id']; ?>">Edit</a>
                      <form method="post" onsubmit="return confirm('Hapus jadwal ini?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $jadwal['id']; ?>">
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
