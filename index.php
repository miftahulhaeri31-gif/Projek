<?php require_once __DIR__ . '/includes/header.php'; ?>

<header class="topbar">
  <div class="container brand">
    <strong><?php echo APP_NAME; ?></strong>
    <nav class="nav">
      <a class="pill secondary" href="auth/login.php">Login</a>
      <a class="pill" href="auth/register.php">Register</a>
    </nav>
  </div>
</header>

<main>
  <section class="hero landing-hero">
    <div class="container hero-grid">
      <section class="card landing-panel fade-in-up">
        <div class="landing-eyebrow kicker">Futsal Booking Wanasaba</div>
        <h1 class="landing-title">Reservasi cepat, jadwal rapi, pembayaran terpantau.</h1>
        <p class="landing-copy">
          Sistem booking lapangan futsal berbasis PHP Native dan MySQL dengan alur admin dan user yang sudah siap dipakai.
          Mulai dari kelola lapangan, atur jadwal, booking online, sampai laporan dan pembayaran.
        </p>
        <div class="nav">
          <a class="button" href="auth/login.php">Mulai Login</a>
          <a class="button secondary" href="auth/register.php">Buat Akun</a>
        </div>

        <div class="landing-metrics delay-1" style="margin-top: 22px;">
          <div class="landing-metric">
            <strong>5</strong>
            <p class="muted">tabel inti</p>
          </div>
          <div class="landing-metric">
            <strong>2</strong>
            <p class="muted">role utama</p>
          </div>
          <div class="landing-metric">
            <strong>100%</strong>
            <p class="muted">siap demo lokal</p>
          </div>
        </div>
      </section>

      <aside class="card fade-in-up delay-2 landing-card-list">
        <div>
          <div class="kicker">Status Fondasi</div>
          <h2>Struktur awal sudah tersedia</h2>
          <p class="muted">Database, koneksi, session helper, seed data, dan stylesheet dasar sudah disiapkan.</p>
        </div>

        <div class="landing-card-item">
          <strong>1</strong>
          <div>
            <div style="font-weight: 700; margin-bottom: 4px;">Admin dashboard live</div>
            <div class="muted">Statistik booking, user, lapangan, dan laporan sudah terhubung ke data real.</div>
          </div>
        </div>

        <div class="landing-card-item">
          <strong>2</strong>
          <div>
            <div style="font-weight: 700; margin-bottom: 4px;">User flow lengkap</div>
            <div class="muted">Dari pilih lapangan sampai pembayaran dan riwayat booking.</div>
          </div>
        </div>

        <div class="landing-card-item">
          <strong>3</strong>
          <div>
            <div style="font-weight: 700; margin-bottom: 4px;">Demo data tersedia</div>
            <div class="muted">Seed admin, user demo, lapangan, jadwal, booking, dan pembayaran sudah masuk.</div>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section class="landing-section">
    <div class="container">
      <div class="section-head">
        <div>
          <div class="kicker">Kenapa dipakai</div>
          <h2>Rapi untuk operasional, cepat untuk demo.</h2>
        </div>
        <p class="muted">Struktur dibuat supaya admin dan user bisa langsung memahami alur tanpa banyak penjelasan tambahan.</p>
      </div>

      <div class="grid two">
        <div class="card fade-in-up delay-2">
          <div class="kicker">Admin</div>
          <h3>Kelola data dan monitoring</h3>
          <p class="muted">CRUD user, lapangan, jadwal, monitoring booking, laporan harian/bulanan, serta export CSV/XLS.</p>
        </div>
        <div class="card fade-in-up delay-3">
          <div class="kicker">User</div>
          <h3>Booking dan pembayaran terarah</h3>
          <p class="muted">Lihat lapangan, pilih jadwal, simpan booking, lanjut pembayaran, lalu pantau riwayat status transaksi.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
