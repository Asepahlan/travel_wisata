-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2026 at 02:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel_wisata`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$d7XxJqrPX6pM/wlvkP1ss.AhnhY4YiVTkbnn2BSTV8u35CV65bvfa', 'Administrator', 'admin@travelwisata.com', '081234567890', '2026-01-09 15:33:26', '2026-01-10 10:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `armada`
--

CREATE TABLE `armada` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nomor_polisi` varchar(20) DEFAULT NULL,
  `jenis` varchar(50) NOT NULL,
  `kapasitas` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('tersedia','dipesan','dalam_perjalanan','maintenance') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `armada`
--

INSERT INTO `armada` (`id`, `nama`, `nomor_polisi`, `jenis`, `kapasitas`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(12, 'Hiece', 'B 3080 TB', '', 16, 'ASas', 'tersedia', '2026-01-11 07:35:54', '2026-01-11 07:38:02'),
(13, 'Hiece', 'B 1234 AB', '', 12, '', 'dipesan', '2026-01-11 07:38:44', '2026-01-11 11:55:28'),
(14, 'Hiece', 'B 1234 ABC', '', 14, '', 'tersedia', '2026-01-11 07:39:45', '2026-01-11 07:39:45'),
(15, 'Hiece', 'B 5678 DEF', '', 12, '', 'tersedia', '2026-01-11 07:40:01', '2026-01-11 07:40:01'),
(16, 'Apv', 'D 9012 GHI', '', 7, '', 'tersedia', '2026-01-11 07:40:47', '2026-01-11 07:40:47'),
(18, 'sigra', 'B 1123 KLM', '', 6, '', 'tersedia', '2026-01-11 07:41:57', '2026-01-11 07:41:57');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `kode_booking` varchar(20) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `id_armada` int(11) DEFAULT NULL,
  `id_supir` int(11) DEFAULT NULL,
  `tanggal_berangkat` date NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `status` enum('menunggu','dikonfirmasi','ditolak','selesai') DEFAULT 'menunggu',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `jenis_booking` enum('wisata','dropan') NOT NULL DEFAULT 'wisata',
  `id_jadwal_dropan` int(11) DEFAULT NULL,
  `jumlah_penumpang` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `kode_booking`, `nama_pemesan`, `no_wa`, `id_paket`, `id_armada`, `id_supir`, `tanggal_berangkat`, `total_harga`, `status`, `catatan`, `created_at`, `updated_at`, `jenis_booking`, `id_jadwal_dropan`, `jumlah_penumpang`) VALUES
(14, 'TRV-69638FB0D699D', 'ahlan boys', '23423423423', 8, 13, NULL, '2026-01-13', 1750000.00, 'menunggu', '', '2026-01-11 11:55:28', '2026-01-11 11:55:28', 'wisata', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_dropan`
--

CREATE TABLE `jadwal_dropan` (
  `id` int(11) NOT NULL,
  `id_rute` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_berangkat` time NOT NULL,
  `id_armada` int(11) NOT NULL,
  `harga_per_orang` decimal(12,2) NOT NULL,
  `total_kursi` int(11) NOT NULL,
  `kursi_terisi` int(11) DEFAULT 0,
  `status` enum('aktif','penuh','selesai') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paket`
--

CREATE TABLE `paket` (
  `id` int(11) NOT NULL,
  `nama_paket` varchar(100) NOT NULL,
  `id_rute` int(11) NOT NULL,
  `jenis_layanan` enum('all_in','non_all_in') NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paket`
--

INSERT INTO `paket` (`id`, `nama_paket`, `id_rute`, `jenis_layanan`, `harga`, `deskripsi`, `created_at`, `updated_at`, `status`) VALUES
(8, 'Tasikmlaya - Bandung', 4, 'all_in', 1750000.00, 'sudah bahan bakar supir diluar tol', '2026-01-11 07:34:23', '2026-01-11 07:34:23', 'aktif'),
(9, 'Tasikmlaya - Jakarta', 5, 'all_in', 1700000.00, 'drop bandung 1,5\r\n2.500,000 diluar tol\r\nklo pp \r\n3.500,000', '2026-01-11 07:43:06', '2026-01-11 07:43:06', 'aktif'),
(10, 'Tasikmlaya - Jawa Timur', 7, 'all_in', 1600000.00, 'ke jawa timur di hitung 3 hari\r\nAll in sudah termasuk supir dan bahan bakar \r\nparkir ,tol dari konsumen\r\n24 jam kebanyakan narik malam\r\nsupir frelance ,yang tetap 2 orang \r\n6 unit travel wisata\r\nHAIC 1', '2026-01-11 07:44:40', '2026-01-11 07:44:40', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `rute`
--

CREATE TABLE `rute` (
  `id` int(11) NOT NULL,
  `asal` varchar(100) NOT NULL,
  `tujuan` varchar(100) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `jarak` decimal(10,2) NOT NULL,
  `waktu_tempuh` varchar(50) DEFAULT NULL,
  `rute_via` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `durasi_jam` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rute`
--

INSERT INTO `rute` (`id`, `asal`, `tujuan`, `status`, `jarak`, `waktu_tempuh`, `rute_via`, `keterangan`, `durasi_jam`, `created_at`, `updated_at`) VALUES
(4, 'Tasikmalaya', 'Bandung', 'aktif', 116.00, NULL, 'Jl. Raya Sumedang - Cibeureum', NULL, 3, '2026-01-07 17:11:13', '2026-01-11 06:57:28'),
(5, 'Tasikmalaya', 'Jakarta', 'aktif', 263.00, NULL, 'Jl. Tol Purbaleunyi', NULL, 5, '2026-01-07 17:11:13', '2026-01-11 06:57:08'),
(7, 'Tasikmalaya', 'Jawa Timur', 'aktif', 594.00, NULL, 'Jl. Tol Kertosono - Solo/Jl. Tol Salatiga - Kertosono', NULL, 8, '2026-01-11 06:48:03', '2026-01-11 06:56:40');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supir`
--

CREATE TABLE `supir` (
  `id` int(11) NOT NULL,
  `nama_supir` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_sim` varchar(50) NOT NULL,
  `id_armada` int(11) DEFAULT NULL,
  `status` enum('tersedia','tidak_tersedia') NOT NULL DEFAULT 'tersedia',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supir`
--

INSERT INTO `supir` (`id`, `nama_supir`, `no_telepon`, `alamat`, `no_sim`, `id_armada`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Enjah', '123456789223', '', 'QE32423', NULL, 'tersedia', '2026-01-11 13:39:24', NULL),
(6, 'Akmal', '234234123124', '', 'QE32424', NULL, 'tersedia', '2026-01-11 13:39:43', NULL),
(7, 'Asep', '456678234567', '', 'QE32425', NULL, 'tersedia', '2026-01-11 13:40:01', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `armada`
--
ALTER TABLE `armada`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `id_paket` (`id_paket`),
  ADD KEY `id_armada` (`id_armada`),
  ADD KEY `id_supir` (`id_supir`),
  ADD KEY `fk_booking_dropan` (`id_jadwal_dropan`);

--
-- Indexes for table `jadwal_dropan`
--
ALTER TABLE `jadwal_dropan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dropan_rute` (`id_rute`),
  ADD KEY `fk_dropan_armada` (`id_armada`);

--
-- Indexes for table `paket`
--
ALTER TABLE `paket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_rute` (`id_rute`);

--
-- Indexes for table `rute`
--
ALTER TABLE `rute`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `supir`
--
ALTER TABLE `supir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_sim` (`no_sim`),
  ADD KEY `id_armada` (`id_armada`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `armada`
--
ALTER TABLE `armada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `jadwal_dropan`
--
ALTER TABLE `jadwal_dropan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paket`
--
ALTER TABLE `paket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rute`
--
ALTER TABLE `rute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supir`
--
ALTER TABLE `supir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`id_paket`) REFERENCES `paket` (`id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`id_armada`) REFERENCES `armada` (`id`),
  ADD CONSTRAINT `booking_ibfk_supir` FOREIGN KEY (`id_supir`) REFERENCES `supir` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_dropan` FOREIGN KEY (`id_jadwal_dropan`) REFERENCES `jadwal_dropan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jadwal_dropan`
--
ALTER TABLE `jadwal_dropan`
  ADD CONSTRAINT `fk_dropan_armada` FOREIGN KEY (`id_armada`) REFERENCES `armada` (`id`),
  ADD CONSTRAINT `fk_dropan_rute` FOREIGN KEY (`id_rute`) REFERENCES `rute` (`id`);

--
-- Constraints for table `paket`
--
ALTER TABLE `paket`
  ADD CONSTRAINT `paket_ibfk_1` FOREIGN KEY (`id_rute`) REFERENCES `rute` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supir`
--
ALTER TABLE `supir`
  ADD CONSTRAINT `supir_ibfk_1` FOREIGN KEY (`id_armada`) REFERENCES `armada` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
