-- phpMyAdmin SQL Dump
-- Database: `fino_motor` (Versi PPL E-Commerce & POS)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Struktur dari tabel `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','karyawan','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `nama_lengkap`, `username`, `email`, `no_telp`, `alamat`, `password`, `role`, `created_at`) VALUES
(2, 'dbro', 'dbro', 'dbro@gmail.com', '081234560001', 'Bangkalan, Madura', '$2a$12$2FUf4gj7BNyzsTCYDEyIpOIl8Z5rzv6RNWkTmrsLq9aWhQSaFP87e', 'pelanggan', '2026-06-29 06:35:42'),
(3, 'Karyawan Handal', 'karyawan', 'karyawan@finomotor.com', '081234567890', 'Bangkalan, Madura', '$2a$12$PG4GddH1XojLyLBc9AqIm.gWp1j3zrnr6//tdapusl3QeK.IkPnUG', 'karyawan', '2026-06-29 07:03:38'),
(4, 'Owner Fino Motor', 'owner1', 'owner@finomotor.com', '089876543210', 'Bangkalan, Madura', '$2a$12$RsoHwh/WW2DnPLbm.WkyfOwAWWZhe4cVfyWgg8sGbzoaRjsMTRgie', 'owner', '2026-06-29 07:03:38');

-- --------------------------------------------------------
-- Struktur dari tabel `items`
-- --------------------------------------------------------

CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `tipe` enum('jasa','barang') NOT NULL DEFAULT 'barang',
  `is_oli` tinyint(1) NOT NULL DEFAULT 0,
  `kategori` varchar(50) DEFAULT 'Umum',
  `deskripsi` text DEFAULT NULL,
  `berat` int(11) NOT NULL DEFAULT 800 COMMENT 'Berat dalam gram untuk API Ongkir',
  `harga` int(11) NOT NULL,
  `harga_pasang` int(11) NOT NULL DEFAULT 0,
  `stok` int(11) DEFAULT 0,
  `stok_minimal` int(11) NOT NULL DEFAULT 3 COMMENT 'Batas Rule-Based peringatan stok tipis',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `items` (`id`, `nama_item`, `tipe`, `is_oli`, `kategori`, `deskripsi`, `berat`, `harga`, `harga_pasang`, `stok`, `stok_minimal`, `gambar`, `created_at`) VALUES
(6, 'Oli Mesin MPX2 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Honda AHM MPX2 10W-30 kemasan 0.8 Liter.', 850, 83000, 88000, 4, 3, 'https://www.hondacengkareng.com/wp-content/uploads/2021/04/Oli-MPX2.jpg', '2026-07-07 11:47:58'),
(7, 'Oli Mesin SPX2 0.8', 'barang', 1, 'Oli', 'Oli full synthetic motor matic Honda AHM SPX2 10W-30 0.8 Liter.', 850, 95000, 100000, 4, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTTm2jrZtsNFJTWCF8pVGQfhevI6GayizaeDF9a9eoErg&s=10', '2026-07-07 11:49:42'),
(8, 'Oli Gear AHM 120ml', 'barang', 1, 'Oli', 'Oli transmisi / gardan motor matic Honda AHM 120ml.', 150, 23000, 28000, 9, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTq2X2lbhZF8Qh-P1HwJ_O04Sn28Jq4OWtHX_d-jbApog&s=10', '2026-07-07 11:52:36'),
(10, 'Oli Mesin Mesran Super 0.8', 'barang', 1, 'Oli', 'Oli mesin Pertamina Mesran Super SAE 20W-50 kemasan 0.8 Liter.', 850, 50000, 55000, 5, 3, 'https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/img/VqbcmM/2023/8/8/f9f3dee9-6712-4c10-bded-2dc40ead15bc.jpg~tplv-aphluv4xwc-resize-jpeg:700:0.jpg', '2026-07-07 11:56:04'),
(11, 'Oli Mesin Shell Advance AX7 0.8', 'barang', 1, 'Oli', 'Oli sintetik Shell Advance AX7 10W-40 0.8 Liter.', 850, 75000, 80000, 9, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQN-ilW5BKI7tlLsLEn6MtFxR386gOBNa3r8FUtxqa7G09EPX8u3QyWJRg&s=10', '2026-07-07 12:28:25'),
(12, 'Oli Mesin Deltalube 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Deltalube Daily 757 Premium 10W-30 0.8 Liter.', 850, 100000, 105000, 8, 3, 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/113/MTA-179488337/deltalube_oli_deltalube_matic_0-8_ml_oli_mesin_motor_matic_deltalube_daily_800ml_full01_bq28kw3r.jpg', '2026-07-07 12:58:16'),
(13, 'Oli Mesin Shell Advance AX5 0.8', 'barang', 1, 'Oli', 'Oli mesin mineral premium Shell Advance AX5 15W-40 0.8 Liter.', 850, 55000, 60000, 7, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR6Ax187xU6lhJvOb5RK0wJ5A58CEPmI3ZdFERFnSllNw&s=10', '2026-07-08 02:40:54'),
(14, 'Oli Mesin Shell Advance AX3 0.8', 'barang', 1, 'Oli', 'Oli mesin motor bebek/standar Shell Advance AX3 0.8 Liter.', 850, 50000, 55000, 1, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTgdElkhrclKH3lYLr3gJcVuel3n70Q9FqYyjwDkb78JA&s=10', '2026-07-08 05:13:53'),
(15, 'Oli Mesin Yamalube Matic 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Yamaha Yamalube Matic 20W-40 0.8 Liter.', 850, 60000, 65000, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT-xALT6vy6qAEVTquUQ0hU3Ea_pEMijdp4Rd_J_xctbg&s=10', '2026-07-08 05:15:57'),
(16, 'Oli Mesin Yamalube Gold 0.8', 'barang', 1, 'Oli', 'Oli mesin motor Yamaha Yamalube Gold 10W-40 0.8 Liter.', 850, 65000, 70000, 3, 3, 'https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcRpKxqntm0I4wMuvfe6l_UAeFYHUtLDuKeVYUcu7WPYTttpO-NP', '2026-07-08 05:18:09'),
(17, 'Oli Mesin Yamalube Super Matic 1L', 'barang', 1, 'Oli', 'Oli full synthetic Yamaha NMAX/Aerox Yamalube Super Matic 1 Liter.', 1050, 88000, 93000, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSBdaARj2nAmpgJ_tQbdobckVeL10N4JL5pn9eLHjU43g&s=10', '2026-07-08 05:20:39'),
(18, 'Oli Mesin Castrol Activ 1L', 'barang', 1, 'Oli', 'Oli mesin motor 4T Castrol Activ 20W-40 kemasan 1 Liter.', 1050, 80000, 85000, 2, 3, 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/MTA-17921697/castrol_castrol_active_4t_oli_mesin_motor_-1_l-_original-_full01_mmi0j3bd.jpg', '2026-07-08 05:23:41'),
(19, 'Oli Mesin Castrol Power 1 Matic 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Castrol Power 1 Scooter 10W-30 0.8 Liter.', 850, 60000, 65000, 1, 3, 'https://ledengmotor.com/storage/products/woocommerce/castrol-power1-matic-10w30-oli-08l/main-13b6a0accd.webp', '2026-07-08 05:28:06'),
(20, 'Oli Mesin Castrol Activ Matic 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Castrol Activ Matic 10W-30 0.8 Liter.', 850, 50000, 55000, 1, 3, 'https://ledengmotor.com/storage/products/woocommerce/castrol-activ-matic-10w30-oli-08l/main-659352bf48.webp', '2026-07-08 05:29:44'),
(21, 'Oli Mesin Castrol Activ Essential 0.8', 'barang', 1, 'Oli', 'Oli mesin Castrol Activ Essential 20W-40 0.8 Liter.', 850, 50000, 55000, 1, 3, 'https://id-test-11.slatic.net/p/0f7aa58ddd06e27c6762085b749adbcb.jpg', '2026-07-08 05:32:20'),
(22, 'Oli Mesin Enduro Racing 0.8', 'barang', 1, 'Oli', 'Oli mesin motor Pertamina Enduro 4T Racing 10W-40 0.8 Liter.', 850, 60000, 65000, 2, 3, 'https://down-id.img.susercontent.com/file/sg-11134201-824if-mf4clxt1rd3c38', '2026-07-08 05:36:36'),
(23, 'Oli Mesin Enduro 4T 1L', 'barang', 1, 'Oli', 'Oli mesin motor Pertamina Enduro 4T 20W-50 kemasan 1 Liter.', 1050, 60000, 65000, 2, 3, 'https://img.lazcdn.com/g/p/99e1469e698c75f59fef3549268eb873.jpg_720x720q80.jpg', '2026-07-08 05:38:43'),
(24, 'Oli Mesin PrimaXP 1L', 'barang', 1, 'Oli', 'Oli mesin Pertamina Prima XP 20W-50 kemasan 1 Liter.', 1050, 55000, 60000, 1, 3, 'https://media.monotaro.id/mid01/big/Otomotif%2C%20Truk%20%26%20Sepeda%20Motor/Oli%2C%20Bahan%20Kimia%2C%20Perbaikan/Oli%20Otomotive/Oli%20Mesin/Pertamina%20%C3%8Bngine%20Oil%20Prima%20XP%20Min%2020W-50%20API%20SL%20(Oli%20Mesin)/g6P104857582-1.jpg', '2026-07-08 05:40:22'),
(25, 'Oli Mesin Motul Scooter Le Mb 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Motul Scooter LE 10W-30 0.8 Liter.', 850, 80000, 85000, 1, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR25nH5QDPvHcL7iu8ByoM6mNtmjGtYnxjMrDQuE-qMwtJ-SfrBMsHbqtk&s=10', '2026-07-08 05:44:04'),
(26, 'Oli Mesin Motul GP Matic 1L', 'barang', 1, 'Oli', 'Oli mesin motor matic Motul GP Matic 4T 10W-40 1 Liter.', 1050, 90000, 95000, 1, 3, 'https://down-id.img.susercontent.com/file/id-11134207-7rbk0-m9so659zg2y548', '2026-07-08 05:47:32'),
(27, 'Oli Mesin Evalube Runner 4T', 'barang', 1, 'Oli', 'Oli mesin motor 4T Evalube Runner 20W-40 0.8 Liter.', 850, 40000, 45000, 4, 3, 'https://static.vecteezy.com/system/resources/previews/035/927/453/large_2x/surakarta-indonesia-2023-evalube-runner-motor-oil-high-quality-motorcycle-oil-four-stroke-4t-800ml-evalube-green-bottle-packaging-free-photo.jpg', '2026-07-08 05:50:10'),
(28, 'Oli Mesin MPX1 1L', 'barang', 1, 'Oli', 'Oli mesin motor bebek/sport Honda AHM MPX1 10W-30 1 Liter.', 1050, 90000, 95000, 2, 3, 'https://www.hondacengkareng.com/wp-content/uploads/2021/08/MPX1-1L.jpg', '2026-07-08 05:54:57'),
(29, 'Air Radiator Coolent AHM 500ml', 'barang', 0, 'Perawatan Motor', 'Cairan pendingin radiator Honda AHM Pre-mixed Coolant 500ml.', 550, 25000, 0, 2, 3, 'https://www.hondacengkareng.com/wp-content/uploads/2016/04/Cairan-Pendingin-Coolant-600x600.jpg', '2026-07-08 05:59:09'),
(30, 'Air Radiator Coolent Unoco 1L', 'barang', 0, 'Perawatan Motor', 'Cairan air radiator coolant Unoco kemasan 1 Liter.', 1050, 15000, 0, 2, 3, 'https://id-test-11.slatic.net/p/41476444ca0aad5ded163fd0b276cf0b.jpg', '2026-07-08 06:04:51'),
(31, 'Oli Mesin Mesran Super 1L', 'barang', 1, 'Oli', 'Oli mesin Pertamina Mesran Super 20W-50 kemasan 1 Liter.', 1050, 58000, 63000, 2, 3, 'https://down-id.img.susercontent.com/file/id-11134207-7r98x-llkqfzcghbd0ac', '2026-07-08 06:07:58'),
(32, 'Oli Mesin Federal Ultratec 0.8', 'barang', 1, 'Oli', 'Oli mesin motor bebek Federal Ultratec 20W-50 0.8 Liter.', 850, 60000, 65000, 2, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ35Ge3kOZxM8-UGxAGFdheVbR3AaIUF3UPztyeCGLL1w&s=10', '2026-07-08 06:10:35'),
(33, 'Oli Mesin Federal Ultratec XX 0.8', 'barang', 1, 'Oli', 'Oli mesin motor Federal Ultratec XX 10W-40 0.8 Liter.', 850, 65000, 70000, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTwzozykm57-PHUlAodaiUhFC-B09yaJEMr7LUfNfr3JA&s=10', '2026-07-08 06:11:49'),
(34, 'Oli Mesin Federal Matic Ultratec 0.8', 'barang', 1, 'Oli', 'Oli mesin motor matic Federal Matic Ultratec 10W-30 0.8 Liter.', 850, 55000, 60000, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWSkLAGY3zR6qEu9ndXkaJKUogfwZsnzAper58q5-flQ&s=10', '2026-07-08 06:13:07'),
(35, 'Oli Samping Mesrania 2T 1L', 'barang', 1, 'Oli', 'Oli samping motor 2 tak Pertamina Mesrania 2T OB 1 Liter.', 1050, 55000, 0, 2, 3, 'https://down-id.img.susercontent.com/file/id-11134207-7r98s-lvmqv8xdbv7p64', '2026-07-08 06:15:30'),
(36, 'Oli Samping Evalube Pro 2T 0.7', 'barang', 1, 'Oli', 'Oli samping sintetik Evalube 2T Pro 700ml.', 750, 40000, 0, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRcGoN0cHfjAFdVCnoVzR56BOo9eqSlFTnPzvQF95sF8Q&s=10', '2026-07-08 06:17:31'),
(37, 'Oli Samping Evalube 2T 0.7', 'barang', 1, 'Oli', 'Oli samping motor 2 tak Evalube 2T hijau 700ml.', 750, 35000, 0, 1, 3, 'https://img.lazcdn.com/g/p/a7c856b2fa5300cb94a3bff3488f41c1.jpg_720x720q80.jpg', '2026-07-08 06:22:41'),
(38, 'Oli Gear Yamalube 100ml', 'barang', 1, 'Oli', 'Oli gardan / transmisi matic Yamaha Yamalube 100ml.', 130, 20000, 25000, 2, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQo1FTASgP8fJ0WKqtx-hvIiJYIr6zYOpqIaDukCj_R8g&s=10', '2026-07-08 06:24:41'),
(39, 'Oli Gear Yamalube 150ml', 'barang', 1, 'Oli', 'Oli gardan matic besar Yamaha Yamalube 150ml.', 180, 25000, 30000, 3, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTKyRmzFX6UPqxEKkA7OqS9HppjpWxxHaiviudcgZP34g&s=10', '2026-07-08 06:25:25'),
(40, 'Oli Samping Orange Tropical 2T 0.7', 'barang', 1, 'Oli', 'Oli samping wangi Orange Tropical 2T kemasan 700ml.', 750, 40000, 0, 1, 3, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6uCG0rIMyLtFqPV5kkPkz0LCgtoo72TA74xiLsFtQEhX4C40eXoKfjfsN&s=10', '2026-07-08 06:30:23'),
(41, 'Cuci Sepeda Motor (Ukuran Sedang)', 'jasa', 0, 'Jasa', 'Layanan cuci bersih motor matic/bebek ukuran sedang.', 0, 15000, 0, 0, 0, '1783952699_WhatsApp Image 2026-07-13 at 21.12.12.jpeg', '2026-07-13 14:24:59'),
(42, 'Cuci Sepeda Motor (Ukuran Besar)', 'jasa', 0, 'Jasa', 'Layanan cuci bersih motor matic besar (NMAX/PCX) atau sport.', 0, 18000, 0, 0, 0, '1783953209_file_000000007250720792e061b09423a6ee.png', '2026-07-13 14:33:29'),
(43, 'Cuci Sepeda Listrik (Ukuran Kecil)', 'jasa', 0, 'Jasa', 'Layanan cuci aman untuk sepeda listrik ukuran kecil.', 0, 10000, 0, 0, 0, '1783953266_file_00000000d2b47207a6ed8a0817451a36.png', '2026-07-13 14:34:26'),
(44, 'Tambal Ban Dalam', 'jasa', 0, 'Jasa', 'Jasa tambal ban dalam sepeda motor.', 0, 15000, 0, 0, 0, '1783954144_file_00000000732c7206b523953c83802607.png', '2026-07-13 14:49:04'),
(45, 'Tambal Ban Tubles', 'jasa', 0, 'Jasa', 'Jasa tambal ban tubeless sepeda motor.', 0, 15000, 0, 0, 0, '1783954180_file_00000000c6547207a2883176d2a730ad.png', '2026-07-13 14:49:40');

-- --------------------------------------------------------
-- Struktur dari tabel `pengeluaran`
-- --------------------------------------------------------

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `nominal` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pengeluaran_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pengeluaran` (`id`, `tanggal`, `keterangan`, `nominal`, `user_id`, `created_at`) VALUES
(3, '2026-07-07', 'beli sabun ', 10000, 3, '2026-07-07 14:40:12'),
(4, '2026-07-08', 'beli bensin', 13000, 3, '2026-07-08 02:38:17');

-- --------------------------------------------------------
-- Struktur dari tabel `transactions`
-- --------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `ongkir` int(11) NOT NULL DEFAULT 0,
  `kurir` varchar(50) DEFAULT NULL,
  `payment_token` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `tanggal_booking` date DEFAULT NULL,
  `waktu_booking` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transactions` (`id`, `user_id`, `total_harga`, `ongkir`, `kurir`, `payment_token`, `status`, `tanggal_booking`, `waktu_booking`, `created_at`) VALUES
(1, 3, 20000, 0, NULL, NULL, 'selesai', '2026-07-01', '11:05:50', '2026-07-01 04:05:50'),
(2, 3, 160000, 0, NULL, NULL, 'selesai', '2026-07-01', '11:07:01', '2026-07-01 04:07:01'),
(3, 2, 350000, 0, NULL, NULL, 'selesai', '2026-07-01', '12:19:00', '2026-07-01 04:19:38'),
(4, 2, 350000, 0, NULL, NULL, 'selesai', '2026-07-01', '11:53:00', '2026-07-01 04:49:28'),
(5, 3, 28000, 0, NULL, NULL, 'selesai', '2026-07-07', '21:17:38', '2026-07-07 14:17:38'),
(6, 2, 80000, 0, NULL, NULL, 'selesai', '2026-07-08', '10:00:00', '2026-07-08 02:36:30'),
(7, 3, 88000, 0, NULL, NULL, 'selesai', '2026-07-08', '09:37:40', '2026-07-08 02:37:40'),
(8, 4, 10000, 0, NULL, NULL, 'selesai', '2026-10-26', '10:50:46', '2026-10-26 03:50:46'),
(9, 2, 85000, 0, NULL, NULL, 'pending', '2026-10-26', '12:59:00', '2026-10-26 04:00:03');

-- --------------------------------------------------------
-- Struktur dari tabel `transaction_details`
-- --------------------------------------------------------

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transaction_details` (`id`, `transaction_id`, `item_id`, `qty`, `harga`, `subtotal`) VALUES
(1, 1, 38, 1, 20000, 20000),
(2, 2, 38, 1, 20000, 20000),
(3, 2, 15, 1, 65000, 65000),
(4, 2, 11, 1, 75000, 75000),
(5, 3, 12, 3, 100000, 350000),
(6, 4, 12, 3, 100000, 350000),
(7, 5, 8, 1, 28000, 28000),
(8, 6, 11, 1, 80000, 80000),
(9, 7, 6, 1, 88000, 88000),
(10, 8, 43, 1, 10000, 10000),
(11, 9, 29, 1, 25000, 25000),
(12, 9, 34, 1, 60000, 60000);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;