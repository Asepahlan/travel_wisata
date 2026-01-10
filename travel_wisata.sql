-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2026 at 11:32 AM
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
(1, 'admin', '$2y$10$NIxx8333nO89egqz.kUPN.Up5ciWrZbQaWj4HkRT5IqY/cyssaR6S', 'Administrator', 'admin@travelwisata.com', '081234567890', '2026-01-09 15:33:26', '2026-01-09 15:38:13');

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
(1, 'Toyota Hiace', 'B 5678 EF', 'Mikro Bus', 12, 'AC, Reclining Seats, Bantal & Selimut', 'dipesan', '2026-01-07 17:11:13', '2026-01-08 16:02:08'),
(2, 'Isuzu Elf', 'B 3099 TB', 'Innova', 18, 'AC, Reclining Seats, TV, Charger', 'tersedia', '2026-01-07 17:11:13', '2026-01-09 13:26:28'),
(3, 'Toyota Hiace Premium', 'B 3040 TB', 'Mikro Bus', 10, 'AC, Reclining Seats, Bantal & Selimut, TV, Charger', 'dipesan', '2026-01-07 17:11:13', '2026-01-08 16:02:08'),
(4, 'Mercedes-Benz Sprinter', 'B 7890 GH', 'Big Bus', 30, 'AC, Reclining Seats, TV, Toilet, Charger', 'dipesan', '2026-01-07 17:11:13', '2026-01-08 16:02:08'),
(5, 'Toyota Hiace', 'B 5678 EF', 'Minibus', 12, 'AC, Musik, Nyaman', 'dipesan', '2026-01-08 15:52:07', '2026-01-09 14:48:35'),
(6, 'Daihatsu Grand Max', 'B 1234 AB', 'Elf', 8, 'AC, Ekonomis', 'dipesan', '2026-01-08 15:52:07', '2026-01-09 14:46:31'),
(7, 'Isuzu Elf', 'B 2345 BC', 'Bus Kecil', 16, 'AC, Toilet, TV', 'tersedia', '2026-01-08 15:52:07', '2026-01-08 16:02:08'),
(8, 'Toyota Avanza', 'B 4567 DE', 'MPV', 6, 'AC, Nyaman untuk perjalanan dekat', 'tersedia', '2026-01-08 15:52:07', '2026-01-08 16:02:08'),
(9, 'Mitsubishi L300', 'B 3456 CD', 'Minibus', 10, 'Ekonomis, Cocok untuk rombongan', 'tersedia', '2026-01-08 15:52:07', '2026-01-08 16:02:08');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `kode_booking`, `nama_pemesan`, `no_wa`, `id_paket`, `id_armada`, `id_supir`, `tanggal_berangkat`, `total_harga`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(4, 'BOOK202601085CDB6B', 'ahlan boys', '23423423423', 5, 2, NULL, '2026-01-09', 4000000.00, 'menunggu', '', '2026-01-08 12:31:33', '2026-01-08 12:31:33'),
(5, 'BOOK2026010824BA26', 'ahlan boys', '23423423423', 5, 4, NULL, '2026-01-09', 4000000.00, 'ditolak', '', '2026-01-08 12:31:46', '2026-01-08 15:34:21'),
(6, 'BOOK2026010812FCFB', 'John Doe', '23423423423', 1, 1, NULL, '2026-01-10', 2500000.00, 'selesai', '', '2026-01-08 12:47:13', '2026-01-10 05:17:30'),
(7, 'BOOK20260108DD8FF3', 'ahlan boys', '23423423423', 1, 3, NULL, '2026-01-09', 2500000.00, 'menunggu', '', '2026-01-08 13:14:53', '2026-01-10 05:17:17'),
(9, 'BOOK2026010989DE2D', 'ahlan boyssdaaaaaaaa', '2344534345', 2, NULL, NULL, '2026-01-10', 2000000.00, 'menunggu', 'ASasAS', '2026-01-08 17:11:52', '2026-01-10 05:17:17'),
(10, 'BOOK202601092915A9', 'asd', '81234567890', 1, 6, NULL, '2026-01-23', 2500000.00, 'dikonfirmasi', 'asdasd', '2026-01-08 17:14:10', '2026-01-09 11:41:24'),
(11, 'TRV-696114C76D5D4', 'Asep Muhammad Ahlan Selan', '23423423423', 1, 6, NULL, '2026-01-09', 2500000.00, 'dikonfirmasi', '', '2026-01-09 14:46:31', '2026-01-10 10:30:43'),
(12, 'TRV-6961154305ABA', 'ahlan boys', '81234567890', 1, 5, NULL, '2026-01-24', 2500000.00, 'dikonfirmasi', '', '2026-01-09 14:48:35', '2026-01-10 10:25:38');

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
(1, 'Jakarta - Bandung All In', 1, 'all_in', 2500000.00, 'Termasuk bbm, tol, supir, makan siang, dan parkir', '2026-01-07 17:11:13', '2026-01-07 17:11:13', 'aktif'),
(2, 'Jakarta - Bandung Non All In', 1, 'non_all_in', 2000000.00, 'Harga hanya untuk sewa kendaraan dan supir', '2026-01-07 17:11:13', '2026-01-07 17:11:13', 'aktif'),
(3, 'Jakarta - Yogyakarta All In', 2, 'all_in', 4500000.00, 'Termasuk bbm, tol, supir, makan siang, makan malam, dan parkir', '2026-01-07 17:11:13', '2026-01-07 17:11:13', 'aktif'),
(4, 'Jakarta - Surabaya All In', 3, 'all_in', 6500000.00, 'Termasuk bbm, tol, supir, makan siang, makan malam, dan parkir', '2026-01-07 17:11:13', '2026-01-07 17:11:13', 'aktif'),
(5, 'Bandung - Yogyakarta All In', 4, 'all_in', 4000000.00, 'Termasuk bbm, tol, supir, makan siang, dan parkir', '2026-01-07 17:11:13', '2026-01-08 16:21:03', 'aktif');

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
(1, 'Jakarta', 'Bandung', 'aktif', 150.50, NULL, NULL, NULL, 3, '2026-01-07 17:11:13', '2026-01-07 17:11:13'),
(2, 'Jakarta', 'Yogyakarta', 'aktif', 550.00, NULL, NULL, NULL, 10, '2026-01-07 17:11:13', '2026-01-07 17:11:13'),
(3, 'Jakarta', 'Surabaya', 'aktif', 800.00, NULL, NULL, NULL, 15, '2026-01-07 17:11:13', '2026-01-07 17:11:13'),
(4, 'Bandung', 'Yogyakarta', 'aktif', 450.00, NULL, NULL, NULL, 8, '2026-01-07 17:11:13', '2026-01-07 17:11:13'),
(5, 'Bandung', 'Surabaya', 'aktif', 700.00, NULL, 'tol Tasik', NULL, 13, '2026-01-07 17:11:13', '2026-01-09 13:37:23'),
(6, 'Tasikmalaya', 'Bandung', 'aktif', 700.00, NULL, NULL, NULL, 5, '2026-01-08 15:42:19', '2026-01-08 15:42:19');

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
  ADD KEY `id_supir` (`id_supir`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `paket`
--
ALTER TABLE `paket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rute`
--
ALTER TABLE `rute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supir`
--
ALTER TABLE `supir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`id_paket`) REFERENCES `paket` (`id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`id_armada`) REFERENCES `armada` (`id`),
  ADD CONSTRAINT `booking_ibfk_supir` FOREIGN KEY (`id_supir`) REFERENCES `supir` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
