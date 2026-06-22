<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_id'])) {
  $target = ($_SESSION['role'] ?? 'user') === 'admin' ? '../admin/dashboard_admin.php' : '../user/dashboard_user.php';
  header('Location: ' . $target);
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Email dan password wajib diisi.';
  } else {
    $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = $user['name'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['role'] = $user['role'];

      $target = $user['role'] === 'admin' ? '../admin/dashboard_admin.php' : '../user/dashboard_user.php';
      header('Location: ' . $target);
      exit;
    }

    $error = 'Email atau password salah.';
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<body class="auth-page">
  <main class="auth-shell">
    <div class="auth-grid">
      <section class="auth-copy">
        <div>
          <div class="kicker">Sistem Booking Futsal</div>
          <h1>Masuk cepat, booking lebih rapi.</h1>
          <p class="muted">Kelola lapangan, jadwal, booking, dan pembayaran dari satu sistem yang sederhana dan terstruktur.</p>
        </div>

        <div class="auth-features">
          <div class="auth-feature">
            <strong>1</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Akses admin dan user</div>
              <div class="muted">Role otomatis mengarahkan ke dashboard yang sesuai.</div>
            </div>
          </div>
          <div class="auth-feature">
            <strong>2</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Alur booking cepat</div>
              <div class="muted">Pilih jadwal, simpan booking, lalu lanjut ke pembayaran.</div>
            </div>
          </div>
          <div class="auth-feature">
            <strong>3</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Siap demo</div>
              <div class="muted">Data seed dan dashboard live sudah disiapkan untuk presentasi.</div>
            </div>
          </div>
        </div>

        <div class="nav">
          <a class="pill secondary" href="../index.php">Kembali ke Home</a>
        </div>
      </section>

      <section class="auth-panel">
        <div class="auth-header">
          <div class="kicker">Login</div>
          <h2>Masuk ke akun Anda</h2>
          <p class="auth-note">Gunakan email dan password yang sudah terdaftar.</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="card" style="border-color: #d30005; color: #d30005; background: #fff5f5;">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form class="form" method="post">
          <input type="email" name="email" placeholder="Email" autocomplete="email" required>
          <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
          <button class="button" type="submit">Login</button>
        </form>

        <div class="card">
          <div style="font-weight: 700; margin-bottom: 6px;">Belum punya akun?</div>
          <p class="muted" style="margin-bottom: 12px;">Buat akun baru untuk mulai booking lapangan futsal.</p>
          <a class="button secondary" href="register.php">Daftar Sekarang</a>
        </div>
      </section>
    </div>
  </main>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
