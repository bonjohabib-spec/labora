-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 12, 2026 at 09:57 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `labo_ra`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `warna` varchar(50) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `harga_beli` decimal(12,2) DEFAULT '0.00',
  `harga_jual` decimal(12,2) DEFAULT '0.00',
  `stok` int DEFAULT '0',
  `stok_min` int DEFAULT '10',
  `stok_max` int DEFAULT '9999'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `warna`, `ukuran`, `harga_beli`, `harga_jual`, `stok`, `stok_min`, `stok_max`) VALUES
(1, 'baju cewek', NULL, NULL, 0.00, 0.00, 0, 10, 9999),
(2, 'baju pria', NULL, NULL, 0.00, 0.00, 0, 10, 9999),
(3, 'legging', NULL, NULL, 0.00, 0.00, 0, 10, 9999);

-- --------------------------------------------------------

--
-- Table structure for table `barang_varian`
--

CREATE TABLE `barang_varian` (
  `id_varian` int NOT NULL,
  `id_barang` int NOT NULL,
  `warna` varchar(50) NOT NULL,
  `ukuran` varchar(50) NOT NULL,
  `stok` int DEFAULT '0',
  `status` enum('aktif','non-aktif') DEFAULT 'aktif',
  `harga_beli` decimal(12,2) DEFAULT '0.00',
  `harga_jual` decimal(12,2) DEFAULT '0.00',
  `stok_min` int DEFAULT '10',
  `stok_max` int DEFAULT '9999'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang_varian`
--

INSERT INTO `barang_varian` (`id_varian`, `id_barang`, `warna`, `ukuran`, `stok`, `status`, `harga_beli`, `harga_jual`, `stok_min`, `stok_max`) VALUES
(1, 1, 'hitam', 'm', 1, 'non-aktif', 0.00, 0.00, 10, 9999),
(2, 1, 'putih', 'l', 1, 'aktif', 0.00, 0.00, 10, 9999),
(3, 1, 'navi', 'xxl', 1, 'aktif', 0.00, 0.00, 10, 9999),
(4, 2, 'hitam', 'm', 14, 'aktif', 0.00, 0.00, 10, 9999),
(5, 2, 'putih', 'l', 40, 'aktif', 0.00, 0.00, 10, 9999),
(6, 3, 'putih', 'l', 65, 'aktif', 0.00, 0.00, 10, 9999),
(7, 3, 'navi', 's', 5, 'aktif', 0.00, 0.00, 10, 9999),
(8, 3, 'putih', 'm', 10, 'aktif', 0.00, 0.00, 10, 9999),
(9, 2, 'putih', 'xxl', 10, 'aktif', 0.00, 0.00, 10, 9999);

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `id` int NOT NULL,
  `id_penjualan` int DEFAULT NULL,
  `id_barang` int DEFAULT NULL,
  `id_varian` int DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(14,2) DEFAULT NULL,
  `pelanggan` varchar(100) DEFAULT NULL,
  `status` enum('aktif','batal','selesai') DEFAULT 'aktif',
  `warna` varchar(50) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `harga_beli` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`id`, `id_penjualan`, `id_barang`, `id_varian`, `qty`, `harga_jual`, `subtotal`, `pelanggan`, `status`, `warna`, `ukuran`, `harga_beli`) VALUES
(39, 46, 1, 1, 1, 10000.00, 10000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(40, 49, 2, 4, 4, 5000.00, 20000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(44, 62, 1, 1, 5, 30000.00, 150000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(45, 62, 3, 7, 5, 40000.00, 200000.00, NULL, 'aktif', 'navi', 's', 0.00),
(46, 68, 1, 1, 4, 30000.00, 120000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(47, 70, 1, 1, 2, 20000.00, 40000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(48, 72, 1, 3, 6, 10000.00, 60000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(49, 74, 1, 3, 3, 10000.00, 30000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(50, 75, 1, 2, 3, 5000.00, 15000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(51, 78, 3, 6, 5, 50000.00, 250000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(52, 88, 1, 1, 1, 20000.00, 20000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(53, 93, 1, 1, 1, 10000.00, 10000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(68, 122, 1, 2, 4, 40000.00, 160000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(69, 129, 1, 2, 4, 20000.00, 80000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(70, 131, 1, 2, 2, 50000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(71, 133, 1, 2, 2, 50000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(72, 136, 1, 2, 4, 40000.00, 160000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(73, 139, 1, 2, 2, 10000.00, 20000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(75, 141, 1, 3, 1, 50000.00, 50000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(76, 142, 1, 2, 1, 40000.00, 40000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(77, 145, 1, 2, 1, 60000.00, 60000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(78, 151, 1, 3, 1, 50000.00, 50000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(79, 153, 2, 4, 1, 60000.00, 60000.00, NULL, 'aktif', 'hitam', 'm', 0.00),
(80, 155, 1, 3, 2, 50000.00, 100000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(81, 157, 1, 3, 2, 60000.00, 120000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(82, 159, 1, 2, 2, 50000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(83, 161, 1, 3, 1, 100000.00, 100000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(84, 163, 1, 3, 1, 40000.00, 40000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(85, 165, 1, 2, 1, 100000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(86, 167, 1, 2, 1, 100000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(87, 169, 1, 2, 1, 100000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(88, 170, 1, 3, 1, 100000.00, 100000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(89, 172, 1, 3, 1, 100000.00, 100000.00, NULL, 'aktif', 'navi', 'xxl', 0.00),
(90, 175, 1, 2, 1, 100000.00, 100000.00, NULL, 'aktif', 'putih', 'l', 0.00),
(91, 177, 2, 4, 1, 100000.00, 100000.00, NULL, 'aktif', 'hitam', 'm', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `kas_shift`
--

CREATE TABLE `kas_shift` (
  `id_shift` int NOT NULL,
  `kasir` varchar(50) NOT NULL,
  `waktu_buka` datetime NOT NULL,
  `waktu_tutup` datetime DEFAULT NULL,
  `saldo_awal` decimal(15,2) DEFAULT '0.00',
  `omset_tunai` decimal(15,2) DEFAULT '0.00',
  `omset_transfer` decimal(15,2) DEFAULT '0.00',
  `piutang_tunai` decimal(15,2) DEFAULT '0.00',
  `piutang_transfer` decimal(15,2) DEFAULT '0.00',
  `saldo_akhir_sistem` decimal(15,2) DEFAULT '0.00',
  `saldo_akhir_fisik` decimal(15,2) DEFAULT '0.00',
  `selisih` decimal(15,2) DEFAULT '0.00',
  `status` enum('open','closed') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kas_shift`
--

INSERT INTO `kas_shift` (`id_shift`, `kasir`, `waktu_buka`, `waktu_tutup`, `saldo_awal`, `omset_tunai`, `omset_transfer`, `piutang_tunai`, `piutang_transfer`, `saldo_akhir_sistem`, `saldo_akhir_fisik`, `selisih`, `status`) VALUES
(1, 'labora', '2026-03-07 18:22:07', '2026-03-07 19:07:56', 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 150000.00, 0.00, 'closed'),
(2, 'labora', '2026-03-07 19:08:03', '2026-03-07 23:20:42', 100000.00, 0.00, 0.00, 0.00, 0.00, 240000.00, 340000.00, 0.00, 'closed'),
(3, 'labora', '2026-03-07 23:24:41', '2026-03-07 23:24:59', 5000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5000000.00, 0.00, 'closed'),
(4, 'labora', '2026-03-07 23:25:17', '2026-03-08 16:37:51', 0.00, 0.00, 0.00, 0.00, 0.00, 100000.00, 100000.00, 0.00, 'closed'),
(5, 'owner1', '2026-03-07 23:44:24', NULL, 100000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open'),
(6, 'labora', '2026-03-08 16:37:53', '2026-03-08 16:38:52', 0.00, 0.00, 0.00, 0.00, 0.00, 40000.00, 40000.00, 0.00, 'closed'),
(7, 'labora', '2026-03-08 16:39:06', '2026-03-11 16:21:54', 100000.00, 0.00, 0.00, 0.00, 0.00, 200000.00, 300000.00, 0.00, 'closed'),
(8, 'labora', '2026-03-11 16:22:00', '2026-03-12 20:41:10', 0.00, 0.00, 60000.00, 50000.00, 50000.00, 50000.00, 0.00, -50000.00, 'closed'),
(9, 'kasir', '2026-03-12 15:25:10', '2026-03-12 16:57:57', 50000.00, 0.00, 0.00, 0.00, 0.00, 10000.00, 10000.00, -50000.00, 'closed'),
(10, 'kasir', '2026-03-12 19:21:29', '2026-03-12 19:22:58', 50000.00, 0.00, 0.00, 0.00, 0.00, 50000.00, 30000.00, -70000.00, 'closed'),
(11, 'kasir', '2026-03-12 19:33:15', '2026-03-12 19:38:48', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 60000.00, 60000.00, 'closed'),
(12, 'kasir', '2026-03-12 20:17:07', '2026-03-12 20:22:49', 50000.00, 0.00, 100000.00, 70000.00, 0.00, 70000.00, 30000.00, -90000.00, 'closed'),
(13, 'kasir', '2026-03-12 20:26:11', '2026-03-12 20:27:30', 50000.00, 0.00, 100000.00, 50000.00, 0.00, 50000.00, 50000.00, -50000.00, 'closed'),
(14, 'labora', '2026-03-12 20:41:19', '2026-03-12 20:42:34', 50000.00, 0.00, 50000.00, 50000.00, 0.00, 50000.00, 50000.00, -50000.00, 'closed'),
(15, 'labora', '2026-03-12 20:42:38', '2026-03-13 01:22:19', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'closed'),
(16, 'kasir', '2026-03-12 20:43:03', '2026-03-12 20:43:42', 50000.00, 0.00, 50000.00, 50000.00, 0.00, 50000.00, 50000.00, -50000.00, 'closed'),
(17, 'kasir', '2026-03-12 20:50:52', '2026-03-12 21:01:51', 0.00, 50000.00, 50000.00, 0.00, 0.00, 50000.00, 50000.00, 0.00, 'closed'),
(18, 'kasir', '2026-03-12 21:16:54', '2026-03-12 21:17:26', 0.00, 50000.00, 0.00, 0.00, 0.00, 50000.00, 50000.00, 0.00, 'closed'),
(19, 'kasir', '2026-03-12 21:48:04', '2026-03-12 21:54:36', 50000.00, 50000.00, 0.00, 0.00, 0.00, 50000.00, 50000.00, -50000.00, 'closed'),
(20, 'kasir', '2026-03-12 22:01:58', '2026-03-12 22:03:24', 50000.00, 50000.00, 0.00, 0.00, 0.00, 50000.00, 50000.00, -50000.00, 'closed'),
(21, 'kasir', '2026-03-13 01:27:09', '2026-03-13 01:37:49', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'closed'),
(22, 'labora', '2026-03-13 02:16:51', NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_piutang`
--

CREATE TABLE `pembayaran_piutang` (
  `id_bayar` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `tanggal` datetime NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `metode_pembayaran` enum('tunai','transfer') DEFAULT 'tunai',
  `id_shift` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembayaran_piutang`
--

INSERT INTO `pembayaran_piutang` (`id_bayar`, `id_penjualan`, `tanggal`, `nominal`, `metode_pembayaran`, `id_shift`) VALUES
(1, 122, '2026-03-07 22:52:09', 50000.00, 'tunai', 2),
(2, 122, '2026-03-07 22:52:40', 50000.00, 'tunai', 2),
(3, 131, '2026-03-08 16:37:20', 50000.00, 'tunai', 4),
(4, 133, '2026-03-08 16:39:33', 60000.00, 'tunai', 7),
(5, 145, '2026-03-08 20:25:33', 10000.00, 'tunai', 7),
(6, 142, '2026-03-08 20:33:43', 20000.00, 'transfer', 7),
(7, 142, '2026-03-08 20:34:07', 10000.00, 'tunai', 7),
(8, 142, '2026-03-08 20:34:12', 9980.00, 'transfer', 7),
(9, 139, '2026-03-11 19:21:21', 20000.00, 'transfer', 8),
(10, 136, '2026-03-11 19:21:37', 30000.00, 'tunai', 8),
(11, 136, '2026-03-11 19:21:41', 30000.00, 'transfer', 8),
(12, 151, '2026-03-12 15:36:39', 20000.00, 'transfer', 9),
(13, 151, '2026-03-12 15:38:27', 10000.00, 'tunai', 9),
(14, 151, '2026-03-12 19:21:34', 20000.00, 'tunai', 10),
(15, 153, '2026-03-12 19:22:13', 30000.00, 'tunai', 10),
(16, 159, '2026-03-12 20:18:03', 70000.00, 'tunai', 12),
(17, 161, '2026-03-12 20:27:19', 50000.00, 'tunai', 13),
(18, 163, '2026-03-12 20:40:15', 20000.00, 'tunai', 8),
(19, 165, '2026-03-12 20:42:20', 50000.00, 'tunai', 14),
(20, 167, '2026-03-12 20:43:32', 50000.00, 'tunai', 16);

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `alamat` text,
  `telepon` varchar(20) DEFAULT NULL,
  `footer_nota` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_toko`, `alamat`, `telepon`, `footer_nota`) VALUES
(1, 'LABORA', 'Jl. Baru No. 1', '0812-3456-7890', 'Terima Kasih Atas Kunjungan Anda\r\nBarang yang sudah dibeli tidak dapat ditukar');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int NOT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `kategori` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `nominal` decimal(14,2) DEFAULT '0.00',
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengeluaran`
--

INSERT INTO `pengeluaran` (`id_pengeluaran`, `tanggal`, `kategori`, `deskripsi`, `nominal`, `dibuat_oleh`, `updated_at`) VALUES
(2, '2026-03-03 00:00:00', 'Operasional Toko', 'sewa toko', 500000.00, 'labora', '2026-03-07 15:49:21'),
(3, '2026-03-03 00:00:00', 'Gaji Karyawan', 'gaji karyawan', 1000000.00, 'labora', '2026-03-07 15:49:21'),
(4, '2026-03-05 00:00:00', 'Operasional Toko', 'sewa toko', 40000000.00, 'labora', '2026-03-07 15:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` int NOT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `pelanggan` varchar(100) DEFAULT NULL,
  `total` decimal(14,2) DEFAULT '0.00',
  `bayar` decimal(14,2) DEFAULT '0.00',
  `kembali` decimal(14,2) DEFAULT '0.00',
  `keuntungan` decimal(14,2) DEFAULT '0.00',
  `kasir` varchar(100) DEFAULT NULL,
  `status` enum('aktif','selesai','batal') DEFAULT 'aktif',
  `metode_pembayaran` enum('tunai','transfer','piutang') DEFAULT 'tunai',
  `jumlah_bayar` decimal(15,2) DEFAULT '0.00',
  `sisa_piutang` decimal(15,2) DEFAULT '0.00',
  `jatuh_tempo` date DEFAULT NULL,
  `id_shift` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `tanggal`, `pelanggan`, `total`, `bayar`, `kembali`, `keuntungan`, `kasir`, `status`, `metode_pembayaran`, `jumlah_bayar`, `sisa_piutang`, `jatuh_tempo`, `id_shift`) VALUES
(46, '2026-03-03 21:40:22', 'salam', 10000.00, 0.00, 0.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(49, '2026-03-03 21:46:05', 'hari', 20000.00, 0.00, 0.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(62, '2026-03-03 21:59:58', 'farah', 350000.00, 0.00, 0.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(68, '2026-03-05 17:44:03', 'yuka', 120000.00, 0.00, 0.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(70, '2026-03-05 17:57:39', 'guys', 40000.00, 0.00, 0.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(72, '2026-03-05 17:58:26', 'andika', 60000.00, 100000.00, 40000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(74, '2026-03-05 18:01:06', 'zahra', 30000.00, 50000.00, 20000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(75, '2026-03-05 18:11:47', 'salam', 15000.00, 50000.00, 35000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(78, '2026-03-05 18:14:50', 'fahri', 250000.00, 500000.00, 250000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(88, '2026-03-05 21:34:12', 'Abi', 20000.00, 50000.00, 30000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(93, '2026-03-06 00:43:50', 'Azam', 10000.00, 50000.00, 40000.00, 0.00, 'labora', 'selesai', 'tunai', 0.00, 0.00, NULL, NULL),
(122, '2026-03-07 19:18:09', 'mipan', 160000.00, 60000.00, 0.00, 160000.00, 'labora', 'selesai', 'piutang', 160000.00, 0.00, NULL, 2),
(129, '2026-03-07 23:18:52', 'escape', 80000.00, 80000.00, 0.00, 80000.00, 'labora', 'selesai', 'tunai', 80000.00, 0.00, NULL, 2),
(131, '2026-03-07 23:37:48', 'nada', 100000.00, 50000.00, 0.00, 100000.00, 'labora', 'selesai', 'piutang', 100000.00, 0.00, '2026-03-21', 4),
(133, '2026-03-08 16:37:56', 'cip', 100000.00, 40000.00, 0.00, 100000.00, 'labora', 'selesai', 'piutang', 100000.00, 0.00, '2026-03-22', 6),
(136, '2026-03-08 19:04:04', 'tepen', 160000.00, 100000.00, 0.00, 160000.00, 'labora', 'selesai', 'piutang', 160000.00, 0.00, '2026-03-22', 7),
(139, '2026-03-08 19:31:46', 'karbon', 20000.00, 0.00, 0.00, 20000.00, 'labora', 'selesai', 'piutang', 20000.00, 0.00, '2026-03-09', 7),
(141, '2026-03-08 19:33:09', 'felix', 50000.00, 50.00, 0.00, 50000.00, 'labora', 'selesai', 'transfer', 50.00, 0.00, NULL, 7),
(142, '2026-03-08 19:37:39', 'koy', 40000.00, 20.00, 0.00, 40000.00, 'labora', 'selesai', 'piutang', 40000.00, 0.00, '2026-03-22', 7),
(145, '2026-03-08 20:01:39', 'den', 60000.00, 50000.00, 0.00, 60000.00, 'labora', 'selesai', 'transfer', 60000.00, 0.00, '2026-03-09', 7),
(151, '2026-03-12 15:36:00', 'alloy', 50000.00, 0.00, 0.00, 50000.00, 'kasir', 'selesai', 'piutang', 50000.00, 0.00, '2026-03-26', 9),
(153, '2026-03-12 19:21:39', 'ogi', 60000.00, 30000.00, 0.00, 60000.00, 'kasir', 'selesai', 'transfer', 60000.00, 0.00, '2026-03-26', 10),
(155, '2026-03-12 19:24:05', 'jaya', 100000.00, 40000.00, 0.00, 100000.00, 'labora', 'selesai', 'transfer', 40000.00, 60000.00, '2026-03-26', 8),
(157, '2026-03-12 19:30:29', 'sai', 120000.00, 0.00, 0.00, 120000.00, 'labora', 'selesai', 'piutang', 0.00, 120000.00, '2026-03-26', 8),
(159, '2026-03-12 20:17:08', 'gery', 100000.00, 30000.00, 0.00, 100000.00, 'kasir', 'selesai', 'transfer', 100000.00, 0.00, '2026-03-26', 12),
(161, '2026-03-12 20:26:12', 'padil', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'transfer', 100000.00, 0.00, '2026-03-26', 13),
(163, '2026-03-12 20:39:43', 'arab', 40000.00, 20000.00, 0.00, 40000.00, 'labora', 'selesai', 'transfer', 40000.00, 0.00, '2026-03-26', 8),
(165, '2026-03-12 20:41:57', 'mau', 100000.00, 50000.00, 0.00, 100000.00, 'labora', 'selesai', 'transfer', 100000.00, 0.00, '2026-03-26', 14),
(167, '2026-03-12 20:43:04', 'niko', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'transfer', 100000.00, 0.00, '2026-03-26', 16),
(169, '2026-03-12 20:50:53', 'yuka', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'transfer', 50000.00, 50000.00, '2026-03-26', 17),
(170, '2026-03-12 20:51:17', 'brev', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'tunai', 50000.00, 50000.00, '2026-03-26', 17),
(172, '2026-03-12 21:16:55', 'timo', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'tunai', 50000.00, 50000.00, '2026-03-26', 18),
(175, '2026-03-12 21:53:30', 'Poke', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'tunai', 50000.00, 50000.00, '2026-03-26', 19),
(177, '2026-03-12 22:02:06', 'Nur', 100000.00, 50000.00, 0.00, 100000.00, 'kasir', 'selesai', 'tunai', 50000.00, 50000.00, '2026-03-26', 20);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_stok`
--

CREATE TABLE `riwayat_stok` (
  `id` int NOT NULL,
  `id_barang` int NOT NULL,
  `id_varian` int DEFAULT NULL,
  `jumlah` int NOT NULL,
  `tipe` enum('penambahan','pengurangan') NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `riwayat_stok`
--

INSERT INTO `riwayat_stok` (`id`, `id_barang`, `id_varian`, `jumlah`, `tipe`, `keterangan`, `tanggal`) VALUES
(1, 1, NULL, 10, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:19:38'),
(2, 1, NULL, 30, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:19:58'),
(3, 1, NULL, 20, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:20:09'),
(4, 2, NULL, 20, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:20:27'),
(5, 2, NULL, 40, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:20:37'),
(6, 3, NULL, 60, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-10 21:20:47'),
(7, 1, 3, 7, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 3)', '2025-10-10 21:30:35'),
(8, 3, NULL, 10, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-11 13:56:19'),
(9, 3, 7, 5, 'pengurangan', 'Penjualan varian \'legging\' (ID Varian: 7)', '2025-10-11 13:56:42'),
(10, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 1)', '2025-10-11 13:59:25'),
(11, 1, 1, 1, 'penambahan', 'Transaksi #3 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 14:03:17'),
(12, 1, 1, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 1)', '2025-10-11 14:03:53'),
(13, 1, 1, 5, 'penambahan', 'Transaksi #4 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 14:04:04'),
(14, 1, 1, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 1)', '2025-10-11 14:04:23'),
(15, 2, 4, 5, 'pengurangan', 'Penjualan varian \'baju pria\' (ID Varian: 4)', '2025-10-11 14:04:35'),
(16, 2, 4, 5, 'penambahan', 'Transaksi #5 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 14:04:54'),
(17, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 1)', '2025-10-11 14:05:56'),
(18, 3, NULL, 10, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-11 14:10:36'),
(19, 3, 8, 5, 'pengurangan', 'Penjualan varian \'legging\' (ID Varian: 8)', '2025-10-11 14:10:56'),
(20, 1, 1, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (ID Varian: 1)', '2025-10-11 14:16:32'),
(21, 1, 1, 5, 'penambahan', 'Transaksi #8 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 14:16:50'),
(22, 3, NULL, 10, 'penambahan', 'Stok varian ditambah', '2025-10-11 14:46:51'),
(23, 2, NULL, 10, 'penambahan', 'Varian baru ditambahkan ke sistem', '2025-10-11 15:16:15'),
(24, 2, 4, 5, 'pengurangan', 'Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)', '2025-10-11 15:29:59'),
(25, 1, 1, 6, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 15:32:00'),
(26, 2, 4, 5, 'penambahan', 'Transaksi #14 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 15:35:52'),
(27, 1, 1, 6, 'penambahan', 'Transaksi #14 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 15:35:52'),
(28, 3, 8, 5, 'penambahan', 'Transaksi #7 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 15:36:03'),
(29, 1, 3, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 15:36:58'),
(30, 1, 3, 5, 'penambahan', 'Transaksi #15 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 15:40:58'),
(31, 1, 1, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 15:41:13'),
(32, 1, 3, 6, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 15:47:05'),
(33, 1, 3, 6, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 15:47:07'),
(34, 1, 3, 6, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 15:47:07'),
(35, 1, 3, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 15:47:31'),
(36, 2, 4, 1, 'pengurangan', 'Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)', '2025-10-11 15:47:42'),
(37, 2, 4, 1, 'pengurangan', 'Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)', '2025-10-11 15:47:59'),
(38, 1, 3, 1, 'penambahan', 'Item transaksi dihapus, stok dikembalikan (oleh owner1)', '2025-10-11 16:12:38'),
(39, 1, 3, 6, 'penambahan', 'Item transaksi dihapus, stok dikembalikan (oleh owner1)', '2025-10-11 16:12:42'),
(40, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:13:59'),
(41, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:14:57'),
(42, 1, 1, 1, 'penambahan', 'Transaksi #17 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:16:20'),
(43, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:16:29'),
(44, 1, 1, 1, 'penambahan', 'Transaksi #6 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:40'),
(45, 1, 3, 6, 'penambahan', 'Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:55'),
(46, 1, 3, 6, 'penambahan', 'Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:55'),
(47, 2, 4, 1, 'penambahan', 'Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:55'),
(48, 2, 4, 1, 'penambahan', 'Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:55'),
(49, 1, 1, 1, 'penambahan', 'Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:17:55'),
(50, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:18:13'),
(51, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:18:47'),
(52, 1, 1, 1, 'penambahan', 'Transaksi #20 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:18:55'),
(53, 1, 1, 1, 'penambahan', 'Transaksi #19 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:21:41'),
(54, 1, 3, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)', '2025-10-11 16:23:15'),
(55, 1, 3, 7, 'penambahan', 'Transaksi #21 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 16:23:42'),
(56, 1, 1, 5, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:25:57'),
(57, 1, 1, 2, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:45:47'),
(58, 1, 1, 1, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:46:01'),
(59, 1, 1, 2, 'pengurangan', 'Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:46:09'),
(60, 2, 4, 1, 'pengurangan', 'Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:48:36'),
(61, 2, 4, 4, 'pengurangan', 'Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)', '2025-10-11 16:48:46'),
(62, 3, 7, 1, 'pengurangan', 'Penjualan varian \'legging\' (Warna: navi, Ukuran: s)', '2025-10-11 17:09:32'),
(63, 3, 7, 2, 'pengurangan', 'Penjualan varian \'legging\' (Warna: navi, Ukuran: s)', '2025-10-11 17:09:46'),
(64, 3, 7, 2, 'penambahan', 'Transaksi #27 dihapus, stok varian dikembalikan (oleh owner1)', '2025-10-11 17:10:16'),
(65, 2, 4, 1, 'penambahan', 'Transaksi #26 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:04'),
(66, 2, 4, 4, 'penambahan', 'Transaksi #26 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:04'),
(67, 1, 1, 1, 'penambahan', 'Transaksi #24 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:08'),
(68, 1, 1, 2, 'penambahan', 'Transaksi #24 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:08'),
(69, 1, 1, 2, 'penambahan', 'Transaksi #23 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:11'),
(70, 1, 1, 5, 'penambahan', 'Transaksi #22 dihapus, stok varian dikembalikan (oleh owner1)', '2026-03-03 17:27:13'),
(72, 1, 1, 3, 'pengurangan', 'Penjualan #42: baju cewek (hitam - m) (oleh owner1)', '2026-03-03 18:18:39'),
(73, 1, 1, 5, 'pengurangan', 'Penjualan #43: baju cewek (hitam - m) (oleh owner1)', '2026-03-03 18:35:23'),
(74, 3, 7, 3, 'pengurangan', 'Penjualan #43: legging (navi - s) (oleh owner1)', '2026-03-03 18:35:23'),
(75, 3, 7, 4, 'pengurangan', 'Penjualan #44: legging (navi - s) (oleh owner1)', '2026-03-03 18:36:47'),
(76, 1, 1, 1, 'pengurangan', 'Penjualan #46: baju cewek (hitam - m) (oleh owner1)', '2026-03-03 21:40:41'),
(77, 3, 7, 4, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #44, stok dikembalikan (oleh owner1)', '2026-03-03 21:44:38'),
(78, 1, 1, 5, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #43, stok dikembalikan (oleh owner1)', '2026-03-03 21:44:42'),
(79, 3, 7, 3, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #43, stok dikembalikan (oleh owner1)', '2026-03-03 21:44:42'),
(80, 1, 1, 3, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #42, stok dikembalikan (oleh owner1)', '2026-03-03 21:44:45'),
(81, 2, 4, 4, 'pengurangan', 'Penjualan #49: baju pria (hitam - m) (oleh owner1)', '2026-03-03 21:46:26'),
(82, 3, 8, 4, 'pengurangan', 'Penjualan #59: legging (putih - m) (oleh owner1)', '2026-03-03 21:54:40'),
(83, 1, 3, 6, 'pengurangan', 'Penjualan #60: baju cewek (navi - xxl) (oleh owner1)', '2026-03-03 21:55:52'),
(84, 1, 1, 1, 'pengurangan', 'Penjualan #60: baju cewek (hitam - m) (oleh owner1)', '2026-03-03 21:55:52'),
(85, 1, 1, 5, 'pengurangan', 'Penjualan #62: baju cewek (hitam - m) (oleh owner1)', '2026-03-03 22:00:35'),
(86, 3, 7, 5, 'pengurangan', 'Penjualan #62: legging (navi - s) (oleh owner1)', '2026-03-03 22:00:35'),
(87, 1, 3, 6, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #60, stok dikembalikan (oleh owner1)', '2026-03-05 17:33:41'),
(88, 1, 1, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #60, stok dikembalikan (oleh owner1)', '2026-03-05 17:33:41'),
(89, 3, 8, 4, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #59, stok dikembalikan (oleh owner1)', '2026-03-05 17:33:44'),
(90, 1, NULL, 5, 'penambahan', 'Stok varian ditambah', '2026-03-05 17:43:12'),
(91, 1, 1, 4, 'pengurangan', 'Penjualan #68: baju cewek (hitam - m) (oleh owner1)', '2026-03-05 17:45:45'),
(92, 1, 1, 2, 'pengurangan', 'Penjualan #70: baju cewek (hitam - m) (oleh owner1)', '2026-03-05 17:58:01'),
(93, 1, 3, 6, 'pengurangan', 'Penjualan #72: baju cewek (navi - xxl) (oleh owner1)', '2026-03-05 17:59:02'),
(94, 1, 3, 3, 'pengurangan', 'Penjualan #74: baju cewek (navi - xxl) (oleh owner1)', '2026-03-05 18:01:26'),
(95, 1, 2, 3, 'pengurangan', 'Penjualan #75: baju cewek (putih - l) (oleh owner1)', '2026-03-05 18:12:26'),
(96, 3, 6, 5, 'pengurangan', 'Penjualan #78: legging (putih - l) (oleh owner1)', '2026-03-05 18:15:11'),
(97, 1, 1, 1, 'pengurangan', 'Penjualan #88: baju cewek (hitam - m) (oleh kasir)', '2026-03-05 21:34:42'),
(98, 1, 1, 1, 'pengurangan', 'Penjualan #93: baju cewek (hitam - m) (oleh kasir)', '2026-03-06 00:44:17'),
(99, 1, 2, 2, 'pengurangan', 'Penjualan #95: baju cewek (putih - l) (oleh labora)', '2026-03-07 15:49:59'),
(100, 1, 2, 5, 'pengurangan', 'Penjualan #97: baju cewek (putih - l) (oleh labora)', '2026-03-07 15:51:00'),
(101, 2, 4, 6, 'pengurangan', 'Penjualan #98: baju pria (hitam - m) (oleh labora)', '2026-03-07 15:51:22'),
(102, 1, 3, 3, 'pengurangan', 'Penjualan #100: baju cewek (navi - xxl) (oleh labora)', '2026-03-07 15:54:52'),
(103, 1, 3, 1, 'pengurangan', 'Penjualan #101: baju cewek (navi - xxl) (oleh labora)', '2026-03-07 15:55:14'),
(104, 2, 5, 1, 'pengurangan', 'Penjualan #102: baju pria (putih - l) (oleh labora)', '2026-03-07 15:55:36'),
(105, 1, 2, 1, 'pengurangan', 'Penjualan #104: baju cewek (putih - l) (oleh labora)', '2026-03-07 15:56:12'),
(106, 1, 2, 1, 'pengurangan', 'Penjualan #105: baju cewek (putih - l) (oleh labora)', '2026-03-07 15:56:33'),
(107, 1, 2, 1, 'pengurangan', 'Penjualan #107: baju cewek (putih - l) (oleh labora)', '2026-03-07 15:57:05'),
(108, 3, 6, 3, 'pengurangan', 'Penjualan #108: legging (putih - l) (oleh labora)', '2026-03-07 15:57:25'),
(109, 1, 2, 1, 'pengurangan', 'Penjualan #110: baju cewek (putih - l) (oleh labora)', '2026-03-07 16:34:48'),
(111, 1, 2, 4, 'pengurangan', 'Penjualan #121: baju cewek (putih - l) (oleh labora)', '2026-03-07 19:18:07'),
(112, 1, 2, 4, 'pengurangan', 'Penjualan #122: baju cewek (putih - l) (oleh labora)', '2026-03-07 19:18:39'),
(113, 1, 2, 2, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #95, stok dikembalikan (oleh labora)', '2026-03-07 22:53:44'),
(114, 1, 2, 5, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #97, stok dikembalikan (oleh labora)', '2026-03-07 22:53:53'),
(115, 2, 4, 6, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #98, stok dikembalikan (oleh labora)', '2026-03-07 22:53:57'),
(116, 1, 3, 3, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #100, stok dikembalikan (oleh labora)', '2026-03-07 22:54:00'),
(117, 1, 3, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #101, stok dikembalikan (oleh labora)', '2026-03-07 22:54:04'),
(118, 2, 5, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #102, stok dikembalikan (oleh labora)', '2026-03-07 22:54:06'),
(119, 1, 2, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #104, stok dikembalikan (oleh labora)', '2026-03-07 22:54:09'),
(120, 1, 2, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #105, stok dikembalikan (oleh labora)', '2026-03-07 22:54:10'),
(121, 1, 2, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #107, stok dikembalikan (oleh labora)', '2026-03-07 22:54:12'),
(122, 3, 6, 3, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #108, stok dikembalikan (oleh labora)', '2026-03-07 22:54:14'),
(123, 1, 2, 1, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #110, stok dikembalikan (oleh labora)', '2026-03-07 22:54:15'),
(124, 1, 2, 4, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #121, stok dikembalikan (oleh labora)', '2026-03-07 22:55:01'),
(125, 1, 2, 4, 'pengurangan', 'Penjualan #129: baju cewek (putih - l) (oleh labora)', '2026-03-07 23:19:19'),
(126, 1, 2, 2, 'pengurangan', 'Penjualan #131: baju cewek (putih - l) (oleh labora)', '2026-03-07 23:38:17'),
(127, 1, 2, 2, 'pengurangan', 'Penjualan #133: baju cewek (putih - l) (oleh labora)', '2026-03-08 16:38:28'),
(128, 1, 2, 4, 'pengurangan', 'Penjualan #136: baju cewek (putih - l) (oleh labora)', '2026-03-08 19:04:32'),
(129, 1, 2, 2, 'pengurangan', 'Penjualan #139: baju cewek (putih - l) (oleh labora)', '2026-03-08 19:32:33'),
(130, 1, 3, 3, 'pengurangan', 'Penjualan #140: baju cewek (navi - xxl) (oleh labora)', '2026-03-08 19:33:08'),
(132, 1, 3, 1, 'pengurangan', 'Penjualan #141: baju cewek (navi - xxl) (oleh labora)', '2026-03-08 19:37:34'),
(133, 1, 2, 1, 'pengurangan', 'Penjualan #142: baju cewek (putih - l) (oleh labora)', '2026-03-08 19:38:59'),
(134, 1, 2, 1, 'pengurangan', 'Penjualan #145: baju cewek (putih - l) (oleh labora)', '2026-03-08 20:21:04'),
(135, 1, 3, 1, 'pengurangan', 'Penjualan #151: baju cewek (navi - xxl) (oleh kasir)', '2026-03-12 15:36:26'),
(136, 1, 3, 3, 'penambahan', 'Pembatalan/Hapus Transaksi Selesai #140, stok dikembalikan (oleh kasir)', '2026-03-12 15:39:09'),
(137, 2, 4, 1, 'pengurangan', 'Penjualan #153: baju pria (hitam - m) (oleh kasir)', '2026-03-12 19:22:03'),
(138, 1, 3, 2, 'pengurangan', 'Penjualan #155: baju cewek (navi - xxl) (oleh labora)', '2026-03-12 19:24:32'),
(139, 1, 3, 2, 'pengurangan', 'Penjualan #157: baju cewek (navi - xxl) (oleh labora)', '2026-03-12 19:30:49'),
(140, 1, 2, 2, 'pengurangan', 'Penjualan #159: baju cewek (putih - l) (oleh kasir)', '2026-03-12 20:17:33'),
(142, 1, 3, 1, 'pengurangan', 'Penjualan #161: baju cewek (navi - xxl) (oleh kasir)', '2026-03-12 20:27:07'),
(143, 1, 3, 1, 'pengurangan', 'Penjualan #163: baju cewek (navi - xxl) (oleh labora)', '2026-03-12 20:40:03'),
(144, 1, 2, 1, 'pengurangan', 'Penjualan #165: baju cewek (putih - l) (oleh labora)', '2026-03-12 20:42:14'),
(145, 1, 2, 1, 'pengurangan', 'Penjualan #167: baju cewek (putih - l) (oleh kasir)', '2026-03-12 20:43:24'),
(146, 1, 2, 1, 'pengurangan', 'Penjualan #169: baju cewek (putih - l) (oleh kasir)', '2026-03-12 20:51:16'),
(150, 1, 3, 1, 'pengurangan', 'Penjualan #170: baju cewek (navi - xxl) (oleh kasir)', '2026-03-12 21:01:27'),
(151, 1, 3, 1, 'pengurangan', 'Penjualan #172: baju cewek (navi - xxl) (oleh kasir)', '2026-03-12 21:17:15'),
(152, 1, 2, 1, 'pengurangan', 'Penjualan #175: baju cewek (putih - l) (oleh kasir)', '2026-03-12 21:54:11'),
(153, 2, 4, 1, 'pengurangan', 'Penjualan #177: baju pria (hitam - m) (oleh kasir)', '2026-03-12 22:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','kasir') DEFAULT 'kasir',
  `nama_lengkap` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama_lengkap`) VALUES
(4, 'labora', 'pass123', 'owner', NULL),
(5, 'kasir', 'pass123', 'kasir', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `barang_varian`
--
ALTER TABLE `barang_varian`
  ADD PRIMARY KEY (`id_varian`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penjualan` (`id_penjualan`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `kas_shift`
--
ALTER TABLE `kas_shift`
  ADD PRIMARY KEY (`id_shift`);

--
-- Indexes for table `pembayaran_piutang`
--
ALTER TABLE `pembayaran_piutang`
  ADD PRIMARY KEY (`id_bayar`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id_pengeluaran`),
  ADD KEY `input_by` (`dibuat_oleh`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `kasir` (`kasir`);

--
-- Indexes for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `fk_riwayat_varian` (`id_varian`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `barang_varian`
--
ALTER TABLE `barang_varian`
  MODIFY `id_varian` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `kas_shift`
--
ALTER TABLE `kas_shift`
  MODIFY `id_shift` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pembayaran_piutang`
--
ALTER TABLE `pembayaran_piutang`
  MODIFY `id_bayar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id_pengeluaran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_varian`
--
ALTER TABLE `barang_varian`
  ADD CONSTRAINT `barang_varian_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`),
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Constraints for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `pengeluaran_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`username`);

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`kasir`) REFERENCES `users` (`username`);

--
-- Constraints for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD CONSTRAINT `fk_riwayat_varian` FOREIGN KEY (`id_varian`) REFERENCES `barang_varian` (`id_varian`) ON DELETE SET NULL,
  ADD CONSTRAINT `riwayat_stok_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
