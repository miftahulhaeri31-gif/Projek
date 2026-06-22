<?php
require_once __DIR__ . '/../includes/session.php';
require_role('admin', '../auth/login.php');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';
$editingUser = [
    'id' => '',
    'name' => '',
    'email' => '',
    'role' => 'user',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if ($name === '' || $email === '') {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (!in_array($role, ['admin', 'user'], true)) {
            $error = 'Role tidak valid.';
        } elseif ($id === '' && $password === '') {
            $error = 'Password wajib diisi untuk user baru.';
        } else {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
                $check->execute([
                    'email' => $email,
                    'id' => $id !== '' ? (int) $id : 0,
                ]);

                if ($check->fetch()) {
                    $error = 'Email sudah digunakan user lain.';
                } elseif ($id !== '') {
                    if ((int) $id === (int) ($_SESSION['user_id'] ?? 0)) {
                        $error = 'Akun yang sedang dipakai tidak dapat diubah melalui halaman ini.';
                    } else {
                        if ($password !== '') {
                            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, password = :password, role = :role WHERE id = :id');
                            $stmt->execute([
                                'name' => $name,
                                'email' => $email,
                                'password' => password_hash($password, PASSWORD_DEFAULT),
                                'role' => $role,
                                'id' => (int) $id,
                            ]);
                        } else {
                            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id');
                            $stmt->execute([
                                'name' => $name,
                                'email' => $email,
                                'role' => $role,
                                'id' => (int) $id,
                            ]);
                        }

                        $message = 'Data user berhasil diperbarui.';
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                    ]);
                    $message = 'User baru berhasil ditambahkan.';
                }
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan data user.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $error = 'Akun yang sedang dipakai tidak dapat dihapus.';
        } elseif ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'Data user berhasil dihapus.';
            } catch (PDOException $e) {
                $error = 'User tidak bisa dihapus karena masih terhubung dengan data booking.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $data = $stmt->fetch();

    if ($data) {
        $editingUser = $data;
    }
}

$userList = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container hero">
  <div class="grid" style="gap: 24px;">
    <div class="card">
      <div class="kicker">Admin / Users</div>
      <h1 style="font-size: clamp(2rem, 4vw, 3.2rem);">Kelola user</h1>
      <p class="muted">Tambah, ubah, dan hapus akun user atau admin dari satu tempat.</p>
      <div class="nav">
        <a class="pill secondary" href="dashboard_admin.php">Dashboard</a>
        <a class="pill secondary" href="lapangan.php">Lapangan</a>
        <a class="pill secondary" href="jadwal.php">Jadwal</a>
        <a class="pill" href="users.php">Users</a>
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
        <div class="kicker"><?php echo $editingUser['id'] !== '' ? 'Edit User' : 'Tambah User'; ?></div>
        <h2><?php echo $editingUser['id'] !== '' ? 'Perbarui akun user' : 'Masukkan akun baru'; ?></h2>
        <form class="form" method="post">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $editingUser['id'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="text" name="name" placeholder="Nama" value="<?php echo htmlspecialchars((string) $editingUser['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars((string) $editingUser['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
          <input type="password" name="password" placeholder="Password <?php echo $editingUser['id'] !== '' ? '(kosongkan jika tidak diubah)' : ''; ?>">
          <select name="role" required>
            <option value="user" <?php echo $editingUser['role'] === 'user' ? 'selected' : ''; ?>>User</option>
            <option value="admin" <?php echo $editingUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
          </select>
          <button class="button" type="submit"><?php echo $editingUser['id'] !== '' ? 'Update User' : 'Simpan User'; ?></button>
        </form>
      </section>

      <section class="card">
        <div class="kicker">Ringkasan</div>
        <h2>Statistik singkat</h2>
        <div class="grid two">
          <div class="card">
            <strong><?php echo count($userList); ?></strong>
            <p class="muted">Total user</p>
          </div>
          <div class="card">
            <strong><?php echo count(array_filter($userList, static function ($item) { return $item['role'] === 'admin'; })); ?></strong>
            <p class="muted">Total admin</p>
          </div>
        </div>
      </section>
    </div>

    <section class="card">
      <div class="kicker">Daftar User</div>
      <h2>Semua akun terdaftar</h2>
      <div style="overflow-x: auto;">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($userList)): ?>
              <tr>
                <td colspan="6" class="muted">Belum ada data user.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($userList as $index => $user): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <div class="nav">
                      <a class="pill secondary" href="users.php?edit=<?php echo (int) $user['id']; ?>">Edit</a>
                      <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <form method="post" onsubmit="return confirm('Hapus user ini?');" style="display:inline;">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                          <button class="pill" type="submit" style="border: none; cursor: pointer;">Hapus</button>
                        </form>
                      <?php endif; ?>
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
