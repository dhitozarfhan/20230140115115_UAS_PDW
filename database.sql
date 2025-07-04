-- Tabel users (Anda sudah punya ini, pastikan strukturnya seperti ini)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel mata_praktikum (untuk use case Mengelola Mata Praktikum & Mencari Mata Praktikum)
CREATE TABLE `mata_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_praktikum` varchar(255) NOT NULL,
  `deskripsi` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pendaftaran_praktikum (untuk use case Mendaftar ke Praktikum)
CREATE TABLE `pendaftaran_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` int(11) NOT NULL,
  `praktikum_id` int(11) NOT NULL,
  `status` enum('terdaftar', 'selesai', 'dibatalkan') DEFAULT 'terdaftar',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mahasiswa_praktikum_unique` (`mahasiswa_id`,`praktikum_id`),
  CONSTRAINT `fk_mahasiswa_id` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_praktikum_id` FOREIGN KEY (`praktikum_id`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel modul_praktikum (untuk use case Mengelola Modul & Mengunduh Materi)
CREATE TABLE `modul_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `praktikum_id` int(11) NOT NULL,
  `nama_modul` varchar(255) NOT NULL,
  `deskripsi` text,
  `file_materi` varchar(255), -- Menyimpan nama file materi
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_modul_praktikum_id` FOREIGN KEY (`praktikum_id`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel laporan_praktikum (untuk use case Mengumpulkan Laporan, Melihat Nilai, Melihat Laporan Masuk, Memberi Nilai Laporan)
CREATE TABLE `laporan_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` int(11) NOT NULL,
  `praktikum_id` int(11) NOT NULL,
  `modul_id` int(11) NOT NULL,
  `file_laporan` varchar(255) NOT NULL, -- Menyimpan nama file laporan
  `nilai` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mahasiswa_modul_unique` (`mahasiswa_id`,`modul_id`),
  CONSTRAINT `fk_laporan_mahasiswa_id` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_laporan_praktikum_id` FOREIGN KEY (`praktikum_id`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_laporan_modul_id` FOREIGN KEY (`modul_id`) REFERENCES `modul_praktikum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;