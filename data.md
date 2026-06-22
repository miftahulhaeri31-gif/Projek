{
"project_name": "Sistem Booking Lapangan Futsal",
"technology": {
"backend": "PHP Native",
"database": "MySQL",
"frontend": "HTML, CSS (mengikuti desain.md), JavaScript",
"server": "XAMPP / Apache"
},
"database": {
"tables": [
{
"name": "users",
"fields": [
"id (INT, PK, AI)",
"name (VARCHAR)",
"email (VARCHAR)",
"password (VARCHAR)",
"role (ENUM: admin, user)",
"created_at (TIMESTAMP)"
]
},
{
"name": "lapangan",
"fields": [
"id (INT, PK, AI)",
"nama_lapangan (VARCHAR)",
"harga_per_jam (INT)",
"status (ENUM: tersedia, maintenance)"
]
},
{
"name": "jadwal",
"fields": [
"id (INT, PK, AI)",
"lapangan_id (INT, FK)",
"tanggal (DATE)",
"jam_mulai (TIME)",
"jam_selesai (TIME)",
"status (ENUM: tersedia, dibooking)"
]
},
{
"name": "booking",
"fields": [
"id (INT, PK, AI)",
"user_id (INT, FK)",
"lapangan_id (INT, FK)",
"jadwal_id (INT, FK)",
"tanggal_booking (DATE)",
"total_harga (INT)",
"status (ENUM: pending, dibayar, selesai, batal)"
]
},
{
"name": "pembayaran",
"fields": [
"id (INT, PK, AI)",
"booking_id (INT, FK)",
"metode (VARCHAR)",
"status (ENUM: pending, sukses, gagal)",
"transaction_id (VARCHAR)",
"created_at (TIMESTAMP)"
]
}
]
},
"features": {
"authentication": {
"login": {
"fields": ["email", "password"],
"session": "PHP Session",
"redirect": {
"admin": "dashboard_admin.php",
"user": "dashboard_user.php"
}
},
"register": {
"fields": ["name", "email", "password"]
},
"logout": "destroy session"
},
"admin": {
"dashboard": "Statistik booking & pendapatan",
"manage_users": "CRUD data user",
"manage_lapangan": "CRUD lapangan",
"manage_jadwal": "CRUD jadwal lapangan",
"booking_monitoring": "Lihat semua booking",
"laporan": {
"harian": "Filter tanggal",
"bulanan": "Rekap total pendapatan",
"export": "PDF / Excel"
}
},
"user": {
"dashboard": "Info akun & booking aktif",
"lihat_lapangan": "List lapangan + harga",
"jadwal_lapangan": "Lihat ketersediaan jadwal",
"booking_online": {
"steps": [
"Pilih lapangan",
"Pilih tanggal",
"Pilih jam",
"Konfirmasi harga",
"Simpan booking"
]
},
"pembayaran": {
"gateway": "Midtrans / Xendit",
"flow": [
"User klik bayar",
"Redirect ke payment gateway",
"Callback update status"
]
},
"riwayat": "List booking sebelumnya + status"
}
},
"pages": {
"auth": [
"login.php",
"register.php",
"logout.php"
],
"admin": [
"dashboard_admin.php",
"users.php",
"lapangan.php",
"jadwal.php",
"booking.php",
"laporan.php"
],
"user": [
"dashboard_user.php",
"lapangan.php",
"jadwal.php",
"booking.php",
"pembayaran.php",
"riwayat.php"
]
},
"payment_gateway": {
"provider": ["Midtrans", "Xendit"],
"integration": {
"api_key": "disimpan di config.php",
"callback_url": "/callback.php",
"update_status": "update tabel pembayaran & booking"
}
},
"folder_structure": {
"root": [
"config/",
"assets/",
"auth/",
"admin/",
"user/",
"includes/",
"uploads/"
],
"config": [
"koneksi.php",
"config.php"
],
"includes": [
"header.php",
"footer.php",
"session.php"
]
},
"security": {
"password_hash": "password_hash()",
"sql_injection": "prepared statements",
"session_security": "session_regenerate_id()",
"validation": "sanitize input"
},
"ui_ux": {
"design_reference": "desain.md",
"theme": "modern, dark/light mode",
"components": [
"navbar",
"sidebar",
"card",
"table",
"form booking",
"calendar jadwal"
]
},
"development_steps": [
"Setup database",
"Buat koneksi PHP ke MySQL",
"Implementasi login & register",
"Buat dashboard admin & user",
"CRUD lapangan",
"CRUD jadwal",
"Implementasi booking",
"Integrasi payment gateway",
"Riwayat booking",
"Laporan & export",
"Testing & debugging"
]
}
