CREATE DATABASE IF NOT EXISTS booking_futsal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE booking_futsal;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lapangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_lapangan VARCHAR(100) NOT NULL,
  harga_per_jam INT NOT NULL,
  status ENUM('tersedia', 'maintenance') NOT NULL DEFAULT 'tersedia'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lapangan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  status ENUM('tersedia', 'dibooking') NOT NULL DEFAULT 'tersedia',
  CONSTRAINT fk_jadwal_lapangan
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX idx_jadwal_lapangan_tanggal (lapangan_id, tanggal)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS booking (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  lapangan_id INT NOT NULL,
  jadwal_id INT NOT NULL,
  tanggal_booking DATE NOT NULL,
  total_harga INT NOT NULL,
  status ENUM('pending', 'dibayar', 'selesai', 'batal') NOT NULL DEFAULT 'pending',
  CONSTRAINT fk_booking_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_booking_lapangan
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_booking_jadwal
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX idx_booking_user_status (user_id, status),
  INDEX idx_booking_tanggal (tanggal_booking)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  metode VARCHAR(50) NOT NULL,
  status ENUM('pending', 'sukses', 'gagal') NOT NULL DEFAULT 'pending',
  transaction_id VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pembayaran_booking
    FOREIGN KEY (booking_id) REFERENCES booking(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  UNIQUE KEY uq_pembayaran_transaction_id (transaction_id),
  INDEX idx_pembayaran_booking_status (booking_id, status)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role)
SELECT 'Admin', 'admin@bookingfutsal.local', '$2y$10$4y4mdoZtdzf1DZ.8mQ.3oeQIKINUuZ5nIgKR4I5Q7q15pVoEDbiRi', 'admin'
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'admin@bookingfutsal.local'
);

INSERT INTO users (name, email, password, role)
SELECT 'User Demo', 'userdemo@bookingfutsal.local', '$2y$10$QLvgZk6xTZqM1W1kqCXr6eQ4SWCsQSMpB.TufNl7j7FNOt.pS//HS', 'user'
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE email = 'userdemo@bookingfutsal.local'
);

INSERT INTO lapangan (nama_lapangan, harga_per_jam, status)
SELECT 'Lapangan A', 150000, 'tersedia'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama_lapangan = 'Lapangan A');

INSERT INTO lapangan (nama_lapangan, harga_per_jam, status)
SELECT 'Lapangan B', 175000, 'tersedia'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama_lapangan = 'Lapangan B');

INSERT INTO lapangan (nama_lapangan, harga_per_jam, status)
SELECT 'Lapangan C', 200000, 'maintenance'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama_lapangan = 'Lapangan C');

INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status)
SELECT id, '2026-07-01', '08:00:00', '09:00:00', 'tersedia'
FROM lapangan WHERE nama_lapangan = 'Lapangan A'
AND NOT EXISTS (
  SELECT 1 FROM jadwal WHERE lapangan_id = lapangan.id AND tanggal = '2026-07-01' AND jam_mulai = '08:00:00' AND jam_selesai = '09:00:00'
);

INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status)
SELECT id, '2026-07-01', '09:00:00', '10:00:00', 'tersedia'
FROM lapangan WHERE nama_lapangan = 'Lapangan A'
AND NOT EXISTS (
  SELECT 1 FROM jadwal WHERE lapangan_id = lapangan.id AND tanggal = '2026-07-01' AND jam_mulai = '09:00:00' AND jam_selesai = '10:00:00'
);

INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status)
SELECT id, '2026-07-02', '10:00:00', '11:00:00', 'tersedia'
FROM lapangan WHERE nama_lapangan = 'Lapangan B'
AND NOT EXISTS (
  SELECT 1 FROM jadwal WHERE lapangan_id = lapangan.id AND tanggal = '2026-07-02' AND jam_mulai = '10:00:00' AND jam_selesai = '11:00:00'
);

INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status)
SELECT id, '2026-07-02', '11:00:00', '12:00:00', 'tersedia'
FROM lapangan WHERE nama_lapangan = 'Lapangan B'
AND NOT EXISTS (
  SELECT 1 FROM jadwal WHERE lapangan_id = lapangan.id AND tanggal = '2026-07-02' AND jam_mulai = '11:00:00' AND jam_selesai = '12:00:00'
);

INSERT INTO jadwal (lapangan_id, tanggal, jam_mulai, jam_selesai, status)
SELECT id, '2026-07-03', '18:00:00', '19:00:00', 'tersedia'
FROM lapangan WHERE nama_lapangan = 'Lapangan C'
AND NOT EXISTS (
  SELECT 1 FROM jadwal WHERE lapangan_id = lapangan.id AND tanggal = '2026-07-03' AND jam_mulai = '18:00:00' AND jam_selesai = '19:00:00'
);

INSERT INTO booking (user_id, lapangan_id, jadwal_id, tanggal_booking, total_harga, status)
SELECT u.id, l.id, j.id, '2026-07-01', 150000, 'dibayar'
FROM users u
INNER JOIN lapangan l ON l.nama_lapangan = 'Lapangan A'
INNER JOIN jadwal j ON j.lapangan_id = l.id AND j.tanggal = '2026-07-01' AND j.jam_mulai = '08:00:00' AND j.jam_selesai = '09:00:00'
WHERE u.email = 'userdemo@bookingfutsal.local'
AND NOT EXISTS (
  SELECT 1 FROM booking WHERE user_id = u.id AND jadwal_id = j.id
);

INSERT INTO pembayaran (booking_id, metode, status, transaction_id)
SELECT b.id, 'midtrans', 'sukses', 'TRX-DEMO-001'
FROM booking b
INNER JOIN users u ON u.id = b.user_id
WHERE u.email = 'userdemo@bookingfutsal.local'
AND b.tanggal_booking = '2026-07-01'
AND NOT EXISTS (
  SELECT 1 FROM pembayaran p WHERE p.booking_id = b.id
);
