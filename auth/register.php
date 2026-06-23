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
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $csrf = $_POST['csrf_token'] ?? '';

  if (!verify_csrf($csrf)) {
    $error = 'Validasi keamanan gagal (CSRF token tidak valid). Silakan muat ulang halaman.';
  } elseif ($name === '' || $email === '' || $password === '') {
    $error = 'Semua field wajib diisi.';
  } else {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute(['email' => $email]);

    if ($check->fetch()) {
      $error = 'Email sudah terdaftar.';
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $insert = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
      $insert->execute([
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'role' => 'user',
      ]);

      session_regenerate_id(true);
      $_SESSION['user_id'] = $pdo->lastInsertId();
      $_SESSION['user_name'] = $name;
      $_SESSION['user_email'] = $email;
      $_SESSION['role'] = 'user';

      header('Location: ../user/dashboard_user.php');
      exit;
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<body class="auth-page">
  <main class="auth-shell">
    <div class="auth-grid">
      <section class="auth-copy">
        <div>
          <div class="kicker">Futsal Booking Wanasaba</div>
          <h1>Buat akun dan mulai booking.</h1>
          <p class="muted">Pendaftaran hanya butuh nama, email, dan password. Setelah itu langsung masuk ke dashboard user.</p>
        </div>

        <div class="auth-features">
          <div class="auth-feature">
            <strong>1</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Data akun aman</div>
              <div class="muted">Password disimpan dengan hashing bawaan PHP.</div>
            </div>
          </div>
          <div class="auth-feature">
            <strong>2</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Masuk ke dashboard</div>
              <div class="muted">Akun baru langsung diarahkan ke dashboard user.</div>
            </div>
          </div>
          <div class="auth-feature">
            <strong>3</strong>
            <div>
              <div style="font-weight: 700; margin-bottom: 4px;">Demo siap dipakai</div>
              <div class="muted">Seed data dan jalur login admin/user sudah tersedia.</div>
            </div>
          </div>
        </div>

        <div class="nav">
          <a class="pill secondary" href="../index.php">Kembali ke Home</a>
        </div>
      </section>

      <section class="auth-panel">
        <div class="auth-header">
          <div class="kicker">Register</div>
          <h2>Buat akun baru</h2>
          <p class="auth-note">Isi data Anda untuk mulai menggunakan sistem booking.</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="card" style="border-color: #d30005; color: #d30005; background: #fff5f5;">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form class="form" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="text" name="name" placeholder="Nama" autocomplete="name" required>
          <input type="email" name="email" placeholder="Email" autocomplete="email" required>
          <input type="password" name="password" placeholder="Password" autocomplete="new-password" required>
          <button class="button" type="submit">Register</button>
        </form>

        <div class="card">
          <div style="font-weight: 700; margin-bottom: 6px;">Sudah punya akun?</div>
          <p class="muted" style="margin-bottom: 12px;">Masuk menggunakan akun yang sudah terdaftar.</p>
          <a class="button secondary" href="login.php">Login Sekarang</a>
        </div>
      </section>
    </div>
  </main>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
