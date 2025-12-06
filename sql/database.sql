SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;
CREATE DATABASE IF NOT EXISTS `pbl-perpustakaan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE pbl-perpustakaan;

DROP TABLE IF EXISTS `akun`;
CREATE TABLE IF NOT EXISTS `akun` (
  `nomor_induk` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'NIM untuk mahasiswa atau NIP untuk dosen/staff, berfungsi sebagai primary key dan identifier unik user',
  `username` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama lengkap user yang ditampilkan di sistem',
  `password` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Password ter-hash menggunakan bcrypt ($2y$10$...), minimal 8 karakter',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Email institusi user untuk komunikasi dan notifikasi sistem',
  `status` enum('Aktif','Tidak Aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Status aktif/non-aktif akun. User baru dimulai dari Tidak Aktif hingga diverifikasi admin',
  `jurusan` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Jurusan user (wajib diisi untuk Mahasiswa, kosongkan untuk Dosen/Tenaga Pendidikan)',
  `prodi` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Program studi user (wajib diisi untuk Mahasiswa, kosongkan untuk Dosen/Tenaga Pendidikan)',
  `role` enum('Admin','Super Admin','Dosen','Mahasiswa','Tenaga Pendidikan','User') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Role akses: Admin (kelola ruangan/laporan), Super Admin (full access + booking eksternal), User (booking biasa). Legacy: Dosen/Mahasiswa/Tenaga Pendidikan',
  `foto_profil` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path relatif ke foto profil user di assets/uploads/images/, diupload setelah registrasi via halaman profile',
  `validasi_mahasiswa` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path ke screenshot KubacaPNJ untuk validasi mahasiswa, wajib diupload saat registrasi oleh mahasiswa',
  PRIMARY KEY (`nomor_induk`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `akun`;
INSERT INTO `akun` (`nomor_induk`, `username`, `password`, `email`, `status`, `jurusan`, `prodi`, `role`, `foto_profil`, `validasi_mahasiswa`) VALUES
('1985030001', 'admin_perpus', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'admin.perpus@staff.pnj.ac.id', 'Aktif', NULL, NULL, 'Admin', 'assets/uploads/images/profile_1985030001_1764772637.jpg', NULL),
('1992120001', 'super_admin', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'superadmin@staff.pnj.ac.id', 'Aktif', NULL, NULL, 'Super Admin', 'assets/uploads/images/profile_1992120001_1764772605.jpg', NULL),
('2401411001', 'Naqib Z', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'naqib.zuhair.al-hudri.tik24@stu.pnj.ac.id', 'Tidak Aktif', 'Teknik Sipil', 'D-IV (Sarjana Terapan) Teknik Konstruksi Gedung', 'User', NULL, 'assets/uploads/images/validasi_1764568514_1b939f23f73b7650.png'),
('2406411040', 'Yusuf Hamzah Taufiqurrahman', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'yusuf.hamzah.taufiqurrahman.tik24@stu.pnj.ac.id', 'Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', NULL, 'assets/uploads/images/validasi_1764568514_1b939f23f73b7650.png'),
('2407411032', 'Maulana Ibrahim', '$2y$10$K3aPu5hKSFXCjfxQUmRGnOuBpjBlNW9Xs9R4IT8naoqXhab8NjLG.', 'maulana.ibrahim.tik24@stu.pnj.ac.id', 'Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', 'assets/uploads/images/profile_2407411032_1764761522.png', 'assets/uploads/images/validasi_1764568514_1b939f23f73b7650.png'),
('2407411033', 'Riyan sa', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'riyan.sohibul.anam.tik24@stu.pnj.ac.id', 'Aktif', 'Teknik Grafika dan Penerbitan', 'D-III Teknik Grafika', 'User', NULL, 'assets/uploads/images/validasi_1764568514_1b939f23f73b7650.png'),
('2407411046', 'AKMAL MUSHAB ABDURAHMAN', '$2y$10$/AKqlbl0aRm/etD2lRaI2OAasWVwF3wqP5phK9h0xOKfigs9siJ46', 'akmal.mushab.abdurahman.tik24@stu.pnj.ac.id', 'Tidak Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', NULL, 'assets/uploads/images/validasi_1764781766_160b03118eedfe5b.jpeg'),
('2407411053', 'Dendy', '$2y$10$85pGYOkIiBtSDxmKw3JyF.4uOfBukv2QOy.t7K1/w993b2taQ5OY2', 'dendy.rafi.al.ghiffary.tik24@stu.pnj.ac.id', 'Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', 'assets/uploads/images/profile_2407411053_1764781605.jpg', 'assets/uploads/images/validasi_1764568514_1b939f23f73b7650.png'),
('2407411054', 'MAHFUDH AL RAFIF', '$2y$10$h52XV6VmJ3GUosrPohdLHeSkx817MNn9cwIFOMyCCRKJGYI/ihJu.', 'mahfudh.al.rafif.tik24@stu.pnj.ac.id', 'Tidak Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', NULL, 'assets/uploads/images/validasi_1764781883_a918811c656165f3.jpeg'),
('2407411056', 'MUHAMMAD REZA ARIFIN', '$2y$10$S9gyyZhxxkC5mAmzJmHIk.cPxjNkIJe2canOqj45FzwN.B3RRq71y', 'muhammad.reza.arifin.tik24@stu.pnj.ac.id', 'Tidak Aktif', 'Teknik Informatika dan Komputer', 'D-IV (Sarjana Terapan) Teknik Informatika', 'User', NULL, 'assets/uploads/images/validasi_1764782018_9e31cbbb9b5c84e6.jpeg'),
('9907411001', 'Orang PNJ 1', '$2y$10$SGSXW3wQS6IPSXT.bhDXQu2NJFi2DxvdQRtUPXoMCjikV8F7Tn7zq', 'orang.pnj.1@pnj.ac.id', 'Tidak Aktif', 'Teknik Sipil', 'D-III Konstruksi Gedung', 'User', NULL, 'assets/uploads/images/validasi_1764782382_9f3631b4cc0ba9da.jpeg');

DROP TABLE IF EXISTS `anggota_booking`;
CREATE TABLE IF NOT EXISTS `anggota_booking` (
  `id_anggota` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel anggota booking',
  `id_booking` int NOT NULL COMMENT 'Foreign key ke tabel booking, menghubungkan anggota dengan booking tertentu',
  `nomor_induk` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Foreign key ke tabel akun (NIM/NIP), identifikasi user yang tergabung dalam booking',
  `is_ketua` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Flag ketua booking (1=ketua, 0=anggota). Ketua bertanggung jawab untuk finish booking dan feedback',
  `is_checked_in` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Status check-in anggota (1=sudah check-in, 0=belum). Check-in dilakukan dengan scan barcode/QR code',
  `waktu_check_in` datetime DEFAULT NULL COMMENT 'Timestamp kapan anggota melakukan check-in, NULL jika belum check-in',
  PRIMARY KEY (`id_anggota`),
  KEY `fk_anggota_booking_booking` (`id_booking`),
  KEY `fk_anggota_booking_akun` (`nomor_induk`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `anggota_booking`;
INSERT INTO `anggota_booking` (`id_anggota`, `id_booking`, `nomor_induk`, `is_ketua`, `is_checked_in`, `waktu_check_in`) VALUES
(3, 2, '2407411032', 1, 0, NULL),
(4, 2, '2407411033', 0, 0, NULL),
(5, 3, '2407411032', 1, 0, NULL),
(6, 3, '2407411033', 0, 0, NULL),
(7, 4, '2407411032', 1, 0, NULL),
(8, 4, '2407411033', 0, 0, NULL),
(9, 5, '2407411032', 1, 0, NULL),
(10, 5, '2407411033', 0, 0, NULL),
(11, 6, '2407411032', 1, 0, NULL),
(12, 6, '2407411033', 0, 0, NULL),
(13, 7, '2407411032', 1, 0, NULL),
(14, 7, '2406411040', 0, 0, NULL),
(15, 8, '2407411032', 1, 1, '2025-12-04 08:33:13'),
(16, 8, '2407411033', 0, 1, '2025-12-04 08:33:13'),
(19, 10, '2407411032', 1, 1, '2025-12-04 08:41:28'),
(20, 10, '2407411033', 0, 1, '2025-12-04 08:41:28'),
(21, 12, '2407411032', 1, 1, '2025-12-04 08:50:25'),
(22, 12, '2407411033', 0, 1, '2025-12-04 08:50:25'),
(23, 13, '2407411032', 1, 1, NULL),
(24, 13, '2407411033', 0, 0, NULL),
(25, 14, '2407411032', 1, 0, NULL),
(26, 14, '2407411033', 0, 0, NULL),
(27, 15, '2407411032', 1, 1, '2025-12-04 10:19:51'),
(28, 15, '2407411033', 0, 1, '2025-12-04 10:19:51'),
(29, 16, '2407411032', 1, 0, NULL),
(30, 16, '2407411033', 0, 0, NULL),
(31, 17, '2407411032', 1, 0, NULL),
(32, 17, '2407411033', 0, 0, NULL);

DROP TABLE IF EXISTS `booking`;
CREATE TABLE IF NOT EXISTS `booking` (
  `id_booking` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel booking',
  `surat_lampiran` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path relatif ke file PDF surat resmi di assets/uploads/docs/, wajib untuk booking eksternal oleh Super Admin',
  `durasi_penggunaan` int NOT NULL COMMENT 'Durasi peminjaman ruangan dalam satuan menit, dihitung dari waktu_mulai sampai waktu_selesai di tabel schedule',
  `kode_booking` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Kode unik booking format BKxxxxx (contoh: BK00001), digunakan untuk identifikasi dan pencarian booking',
  `id_ruangan` int NOT NULL COMMENT 'Foreign key ke tabel ruangan, menentukan ruangan mana yang dibooking',
  `id_status_booking` int NOT NULL COMMENT 'Foreign key ke tabel status_booking (1=AKTIF, 2=SELESAI, 3=DIBATALKAN, 4=HANGUS), menunjukkan status lifecycle booking',
  `nama_instansi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nama instansi eksternal untuk booking external oleh Super Admin (contoh: Universitas Negeri Jakarta), NULL untuk booking internal',
  PRIMARY KEY (`id_booking`),
  KEY `fk_booking_ruangan` (`id_ruangan`),
  KEY `fk_booking_status` (`id_status_booking`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `booking`;
INSERT INTO `booking` (`id_booking`, `surat_lampiran`, `durasi_penggunaan`, `kode_booking`, `id_ruangan`, `id_status_booking`, `nama_instansi`) VALUES
(2, NULL, 60, 'CCB575E', 8, 3, NULL),
(3, NULL, 120, 'F689913', 8, 3, NULL),
(4, NULL, 60, '9027BA3', 8, 3, NULL),
(5, NULL, 60, '27460DA', 8, 4, NULL),
(6, NULL, 60, 'BCBD1CA', 8, 4, NULL),
(7, NULL, 60, '1C3E16F', 8, 4, NULL),
(8, NULL, 60, '37BBD6F', 8, 2, NULL),
(10, NULL, 60, '8C247CB', 8, 2, NULL),
(11, 'assets/uploads/docs/surat_1764812554_c73f48b4de4dd557.pdf', 60, 'EXT1339', 13, 2, 'PT Maju'),
(12, NULL, 60, '2F62B31', 1, 2, NULL),
(13, NULL, 103, 'F6F0CEB', 8, 2, NULL),
(14, NULL, 1, 'D538D36', 8, 2, NULL),
(15, NULL, 60, 'F003351', 8, 2, NULL),
(16, NULL, 60, 'E0D1120', 8, 4, NULL),
(17, NULL, 60, 'D537EC0', 8, 1, NULL);

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id_feedback` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel feedback',
  `id_booking` int NOT NULL COMMENT 'Foreign key ke tabel booking, menghubungkan feedback dengan booking yang sudah selesai (status=SELESAI)',
  `kritik_saran` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Gabungan kritik dan saran dari user dalam satu field textarea, diisi setelah ketua finish booking',
  `skala_kepuasan` int NOT NULL COMMENT 'Rating kepuasan user (1=tidak puas/emoji sedih, 5=sangat puas/emoji senyum), wajib dipilih sebelum submit feedback',
  PRIMARY KEY (`id_feedback`),
  KEY `fk_feedback_booking` (`id_booking`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `feedback`;
INSERT INTO `feedback` (`id_feedback`, `id_booking`, `kritik_saran`, `skala_kepuasan`) VALUES
(1, 8, 'boleh lah ya', 5),
(2, 10, 'Bagus', 5),
(3, 12, 'aa', 5),
(4, 13, '', 5),
(5, 15, '', 5);

DROP TABLE IF EXISTS `hari_libur`;
CREATE TABLE IF NOT EXISTS `hari_libur` (
  `id_hari_libur` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel hari libur',
  `tanggal` date NOT NULL COMMENT 'Tanggal libur/tidak bisa booking (format: YYYY-MM-DD)',
  `keterangan` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Alasan libur (contoh: Hari Raya Natal, Maintenance Gedung, Event Kampus)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp kapan hari libur ini ditambahkan',
  `created_by` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor induk Super Admin yang menambahkan (foreign key ke akun)',
  PRIMARY KEY (`id_hari_libur`),
  UNIQUE KEY `unique_tanggal` (`tanggal`),
  KEY `fk_hari_libur_akun` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Daftar tanggal libur/event khusus yang tidak bisa booking';

TRUNCATE TABLE `hari_libur`;
INSERT INTO `hari_libur` (`id_hari_libur`, `tanggal`, `keterangan`, `created_at`, `created_by`) VALUES
(1, '2025-12-25', 'Hari Raya Natal', '2025-12-03 22:03:50', '1992120001'),
(2, '2025-01-01', 'Tahun Baru 2025', '2025-12-03 22:03:50', '1992120001'),
(3, '2025-08-17', 'Hari Kemerdekaan RI', '2025-12-03 22:03:50', '1992120001');

DROP TABLE IF EXISTS `pelanggaran_suspensi`;
CREATE TABLE IF NOT EXISTS `pelanggaran_suspensi` (
  `id_suspend` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel pelanggaran dan suspensi',
  `nomor_induk` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Foreign key ke tabel akun (NIM/NIP), identifikasi user yang mendapat suspensi',
  `jenis_pelanggaran` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Jenis pelanggaran yang dilakukan (contoh: Tidak Check-in, Keterlambatan, Pelanggaran Tata Tertib)',
  `alasan_suspensi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Penjelasan detail alasan suspensi diberikan, termasuk informasi booking yang dilanggar',
  `tanggal_mulai` datetime NOT NULL COMMENT 'Timestamp mulai periode suspensi dengan presisi hingga detik, user tidak bisa booking selama periode ini',
  `tanggal_selesai` datetime NOT NULL COMMENT 'Timestamp selesai periode suspensi dengan presisi hingga detik, setelah waktu ini user bisa booking kembali',
  PRIMARY KEY (`id_suspend`),
  KEY `fk_pelanggaran_akun` (`nomor_induk`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `pelanggaran_suspensi`;
INSERT INTO `pelanggaran_suspensi` (`id_suspend`, `nomor_induk`, `jenis_pelanggaran`, `alasan_suspensi`, `tanggal_mulai`, `tanggal_selesai`) VALUES
(7, '2407411032', 'Keterlambatan Check-in', 'Tidak hadir (1/3). Diblokir booking 24 jam.', '2025-12-04 04:54:27', '2025-12-05 04:54:27'),
(8, '2407411033', 'Keterlambatan Check-in', 'Tidak hadir (1/3). Diblokir booking 24 jam.', '2025-12-04 04:54:36', '2025-12-05 04:54:36');

DROP TABLE IF EXISTS `ruangan`;
CREATE TABLE IF NOT EXISTS `ruangan` (
  `id_ruangan` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel ruangan',
  `nama_ruangan` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama ruangan yang ditampilkan di sistem (contoh: Ruang Layar, Zona Interaktif)',
  `jenis_ruangan` enum('Ruang Rapat','Ruang Umum') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Kategori ruangan: Ruang Rapat (booking dengan surat resmi) atau Ruang Umum (booking bebas untuk umum)',
  `maksimal_kapasitas_ruangan` int NOT NULL COMMENT 'Jumlah maksimal orang yang bisa menggunakan ruangan, divalidasi saat booking (jumlah anggota <= max)',
  `minimal_kapasitas_ruangan` int NOT NULL COMMENT 'Jumlah minimal orang yang harus hadir untuk booking ruangan, divalidasi saat booking (jumlah anggota >= min)',
  `deskripsi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Deskripsi singkat fasilitas dan fungsi ruangan (contoh: Audio Visual, R. Baca Kelompok)',
  `tata_tertib` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Peraturan penggunaan ruangan yang harus dipatuhi user (contoh: Jangan membuang sampah, Jaga kebersihan)',
  `status_ruangan` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Status ketersediaan ruangan: Tersedia, Sedang Digunakan, atau Dalam Perbaikan. Auto-update berdasarkan jadwal booking aktif',
  `foto_ruangan` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path relatif ke foto ruangan di assets/uploads/images/',
  PRIMARY KEY (`id_ruangan`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `ruangan`;
INSERT INTO `ruangan` (`id_ruangan`, `nama_ruangan`, `jenis_ruangan`, `maksimal_kapasitas_ruangan`, `minimal_kapasitas_ruangan`, `deskripsi`, `tata_tertib`, `status_ruangan`, `foto_ruangan`) VALUES
(1, 'Ruang Layar', 'Ruang Umum', 12, 5, 'Audio Visual', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(2, 'Ruang Sinergi', 'Ruang Umum', 12, 6, 'R. Telekonferensi', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(3, 'Zona Interaktif', 'Ruang Umum', 12, 6, 'R. Kreasi & Rekreasi', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(4, 'Sudut Pustaka', 'Ruang Umum', 12, 6, 'R. Baca Kelompok', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(5, 'Galeri Literasi', 'Ruang Umum', 12, 5, 'R. Baca Kelompok', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', ''),
(6, 'Ruang Cendekia', 'Ruang Umum', 12, 5, 'R. Baca Kelompok', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(7, 'Pusat Prancis', 'Ruang Umum', 12, 6, 'R. Koleksi Bahasa Perancis', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(8, 'Ruang Santai', 'Ruang Umum', 12, 2, 'R. Santai santai', '1. Jangan membuang sampah di ruangan tersebut.', 'Tersedia', NULL),
(10, 'Ruang Asa', 'Ruang Umum', 2, 3, 'R. Bimbingan & Konseling', NULL, 'Tersedia', NULL),
(11, 'Lentera Edukasi', 'Ruang Umum', 2, 4, 'R. Bimbingan & Konseling', NULL, 'Tersedia', NULL),
(12, 'R. Rapat Utama', 'Ruang Rapat', 12, 5, 'Ruangan meeting dengan fasilitas AC, proyektor HD, dan whiteboard.', 'Dilarang membawa makanan dan minuman.', 'Tersedia', NULL),
(13, 'R. Rapat Dosen', 'Ruang Rapat', 10, 3, 'Ruangan rapat khusus dosen.', 'Wajib reservasi.', 'Tidak Tersedia', NULL);

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id_schedule` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel schedule',
  `id_booking` int NOT NULL COMMENT 'Foreign key ke tabel booking, satu booking bisa punya multiple schedule untuk tracking riwayat reschedule',
  `tanggal_schedule` date NOT NULL COMMENT 'Tanggal jadwal booking (format: YYYY-MM-DD), user pilih saat buat/reschedule booking',
  `waktu_mulai` time NOT NULL COMMENT 'Waktu mulai penggunaan ruangan (format: HH:MM:SS), divalidasi tidak bentrok dengan booking lain di ruangan yang sama',
  `waktu_selesai` time NOT NULL COMMENT 'Waktu selesai penggunaan ruangan (format: HH:MM:SS), dihitung dari waktu_mulai + durasi_penggunaan',
  `alasan_reschedule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan user melakukan reschedule booking, wajib diisi saat request reschedule (minimal 1 jam sebelum waktu mulai)',
  `status_schedule` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Status schedule: AKTIF (jadwal sedang berlaku), DIRESCHEDULE (jadwal lama yang sudah diganti). Untuk tracking history reschedule',
  PRIMARY KEY (`id_schedule`),
  KEY `fk_schedule_booking` (`id_booking`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `schedule`;
INSERT INTO `schedule` (`id_schedule`, `id_booking`, `tanggal_schedule`, `waktu_mulai`, `waktu_selesai`, `alasan_reschedule`, `status_schedule`) VALUES
(2, 2, '2025-12-04', '10:41:00', '11:41:00', NULL, 'AKTIF'),
(3, 3, '2025-12-04', '08:00:00', '10:00:00', NULL, 'AKTIF'),
(4, 4, '2025-12-04', '08:00:00', '09:00:00', NULL, 'AKTIF'),
(5, 5, '2025-12-03', '08:00:00', '09:00:00', NULL, 'AKTIF'),
(6, 6, '2025-12-03', '08:00:00', '09:00:00', NULL, 'AKTIF'),
(7, 7, '2025-12-03', '08:00:00', '09:00:00', NULL, 'AKTIF'),
(8, 8, '2025-12-04', '08:31:00', '09:31:00', NULL, 'AKTIF'),
(10, 10, '2025-12-04', '08:40:00', '09:40:00', NULL, 'AKTIF'),
(11, 11, '2025-12-04', '08:42:00', '09:42:00', NULL, 'AKTIF'),
(12, 12, '2025-12-04', '08:44:00', '09:44:00', NULL, 'AKTIF'),
(13, 13, '2025-12-04', '09:28:00', '10:28:00', NULL, 'DIRESCHEDULE'),
(14, 13, '2025-12-05', '10:28:00', '11:28:00', '', 'DIRESCHEDULE'),
(15, 13, '2025-12-04', '10:28:00', '11:28:00', '', 'DIRESCHEDULE'),
(16, 13, '2025-12-04', '10:31:00', '11:28:00', '', 'DIRESCHEDULE'),
(17, 13, '2025-12-05', '10:31:00', '11:28:00', '', 'DIRESCHEDULE'),
(18, 13, '2025-12-04', '09:45:00', '11:28:00', '', 'AKTIF'),
(19, 14, '2025-12-05', '09:56:00', '09:57:00', NULL, 'DIRESCHEDULE'),
(20, 14, '2025-12-04', '09:56:00', '09:57:00', '', 'AKTIF'),
(21, 15, '2025-12-04', '10:18:00', '11:18:00', NULL, 'AKTIF'),
(22, 16, '2025-12-04', '10:20:00', '11:20:00', NULL, 'AKTIF'),
(23, 17, '2025-12-08', '08:26:00', '09:26:00', NULL, 'AKTIF');

DROP TABLE IF EXISTS `status_booking`;
CREATE TABLE IF NOT EXISTS `status_booking` (
  `id_status_booking` int NOT NULL COMMENT 'Primary key untuk tabel status booking (1=AKTIF, 2=SELESAI, 3=DIBATALKAN, 4=HANGUS)',
  `nama_status` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama status booking: AKTIF (booking confirmed, tunggu check-in), SELESAI (booking complete), DIBATALKAN (user/admin cancel), HANGUS (no check-in >10 menit setelah waktu mulai)',
  PRIMARY KEY (`id_status_booking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `status_booking`;
INSERT INTO `status_booking` (`id_status_booking`, `nama_status`) VALUES
(1, 'AKTIF'),
(2, 'SELESAI'),
(3, 'DIBATALKAN'),
(4, 'HANGUS');

DROP TABLE IF EXISTS `waktu_operasi`;
CREATE TABLE IF NOT EXISTS `waktu_operasi` (
  `id_waktu_operasi` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key untuk tabel waktu operasi',
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Hari dalam seminggu untuk pengaturan jam operasional',
  `jam_buka` time NOT NULL COMMENT 'Waktu mulai operasional booking (format: HH:MM:SS)',
  `jam_tutup` time NOT NULL COMMENT 'Waktu selesai operasional booking (format: HH:MM:SS)',
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status operasional hari ini (1=buka, 0=tutup/libur)',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp terakhir kali diubah',
  `updated_by` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nomor induk admin yang terakhir mengubah (foreign key ke akun)',
  PRIMARY KEY (`id_waktu_operasi`),
  UNIQUE KEY `unique_hari` (`hari`),
  KEY `fk_waktu_operasi_akun` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pengaturan jam operasional booking per hari dalam seminggu';

TRUNCATE TABLE `waktu_operasi`;
INSERT INTO `waktu_operasi` (`id_waktu_operasi`, `hari`, `jam_buka`, `jam_tutup`, `is_aktif`, `updated_at`, `updated_by`) VALUES
(1, 'Senin', '07:30:00', '16:00:00', 1, '2025-12-04 00:03:45', '1992120001'),
(2, 'Selasa', '07:30:00', '16:00:00', 1, '2025-12-04 00:04:01', '1992120001'),
(3, 'Rabu', '07:30:00', '16:00:00', 1, '2025-12-04 00:04:15', '1992120001'),
(4, 'Kamis', '07:30:00', '16:00:00', 1, '2025-12-04 00:04:29', '1992120001'),
(5, 'Jumat', '07:30:00', '16:00:00', 1, '2025-12-04 00:04:44', '1992120001'),
(6, 'Sabtu', '07:30:00', '16:00:00', 0, '2025-12-04 00:04:55', '1992120001'),
(7, 'Minggu', '07:30:00', '16:00:00', 0, '2025-12-04 00:05:29', '1992120001');


ALTER TABLE `anggota_booking`
  ADD CONSTRAINT `fk_anggota_booking_akun` FOREIGN KEY (`nomor_induk`) REFERENCES `akun` (`nomor_induk`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_booking_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_ruangan` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`),
  ADD CONSTRAINT `fk_booking_status` FOREIGN KEY (`id_status_booking`) REFERENCES `status_booking` (`id_status_booking`);

ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `hari_libur`
  ADD CONSTRAINT `fk_hari_libur_akun` FOREIGN KEY (`created_by`) REFERENCES `akun` (`nomor_induk`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `pelanggaran_suspensi`
  ADD CONSTRAINT `fk_pelanggaran_akun` FOREIGN KEY (`nomor_induk`) REFERENCES `akun` (`nomor_induk`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `schedule`
  ADD CONSTRAINT `fk_schedule_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `waktu_operasi`
  ADD CONSTRAINT `fk_waktu_operasi_akun` FOREIGN KEY (`updated_by`) REFERENCES `akun` (`nomor_induk`) ON DELETE SET NULL ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;
