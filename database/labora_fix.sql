-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: labo_ra
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang` (
  `id_barang` int NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(200) NOT NULL,
  `warna` varchar(50) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `harga_beli` decimal(12,2) DEFAULT '0.00',
  `harga_jual` decimal(12,2) DEFAULT '0.00',
  `stok` int DEFAULT '0',
  `stok_min` int DEFAULT '10',
  `stok_max` int DEFAULT '9999',
  PRIMARY KEY (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang`
--

LOCK TABLES `barang` WRITE;
/*!40000 ALTER TABLE `barang` DISABLE KEYS */;
INSERT INTO `barang` VALUES (1,'baju cewek',NULL,NULL,0.00,0.00,0,10,9999),(2,'baju pria',NULL,NULL,0.00,0.00,0,10,9999),(3,'legging',NULL,NULL,0.00,0.00,0,10,9999);
/*!40000 ALTER TABLE `barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barang_varian`
--

DROP TABLE IF EXISTS `barang_varian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang_varian` (
  `id_varian` int NOT NULL AUTO_INCREMENT,
  `id_barang` int NOT NULL,
  `warna` varchar(50) NOT NULL,
  `ukuran` varchar(50) NOT NULL,
  `stok` int DEFAULT '0',
  `harga_beli` decimal(12,2) DEFAULT '0.00',
  `harga_jual` decimal(12,2) DEFAULT '0.00',
  `stok_min` int DEFAULT '10',
  `stok_max` int DEFAULT '9999',
  PRIMARY KEY (`id_varian`),
  KEY `id_barang` (`id_barang`),
  CONSTRAINT `barang_varian_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang_varian`
--

LOCK TABLES `barang_varian` WRITE;
/*!40000 ALTER TABLE `barang_varian` DISABLE KEYS */;
INSERT INTO `barang_varian` VALUES (1,1,'hitam','m',0,0.00,0.00,10,9999),(2,1,'putih','l',30,0.00,0.00,10,9999),(3,1,'navi','xxl',20,0.00,0.00,10,9999),(4,2,'hitam','m',15,0.00,0.00,10,9999),(5,2,'putih','l',40,0.00,0.00,10,9999),(6,3,'putih','l',70,0.00,0.00,10,9999),(7,3,'navi','s',10,0.00,0.00,10,9999),(8,3,'putih','m',10,0.00,50000.00,10,9999),(9,2,'putih','xxl',10,0.00,0.00,10,9999);
/*!40000 ALTER TABLE `barang_varian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_penjualan`
--

DROP TABLE IF EXISTS `detail_penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_penjualan` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `id_penjualan` (`id_penjualan`),
  KEY `id_barang` (`id_barang`),
  CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`),
  CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_penjualan`
--

LOCK TABLES `detail_penjualan` WRITE;
/*!40000 ALTER TABLE `detail_penjualan` DISABLE KEYS */;
INSERT INTO `detail_penjualan` VALUES (28,22,1,1,5,34000.00,170000.00,NULL,'aktif','hitam','m'),(29,23,1,1,2,21.00,42.00,NULL,'aktif','hitam','m'),(30,24,1,1,1,50000.00,50000.00,NULL,'aktif','hitam','m'),(31,24,1,1,2,50000.00,100000.00,NULL,'aktif','hitam','m'),(32,26,2,4,1,50000.00,50000.00,NULL,'aktif','hitam','m'),(33,26,2,4,4,21.00,84.00,NULL,'aktif','hitam','m');
/*!40000 ALTER TABLE `detail_penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengeluaran`
--

DROP TABLE IF EXISTS `pengeluaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int NOT NULL AUTO_INCREMENT,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `kategori` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `nominal` decimal(14,2) DEFAULT '0.00',
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengeluaran`),
  KEY `input_by` (`dibuat_oleh`),
  CONSTRAINT `pengeluaran_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengeluaran`
--

LOCK TABLES `pengeluaran` WRITE;
/*!40000 ALTER TABLE `pengeluaran` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengeluaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penjualan`
--

DROP TABLE IF EXISTS `penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penjualan` (
  `id_penjualan` int NOT NULL AUTO_INCREMENT,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `pelanggan` varchar(100) DEFAULT NULL,
  `total` decimal(14,2) DEFAULT '0.00',
  `keuntungan` decimal(14,2) DEFAULT '0.00',
  `kasir` varchar(100) DEFAULT NULL,
  `status` enum('aktif','selesai','batal') DEFAULT 'aktif',
  PRIMARY KEY (`id_penjualan`),
  KEY `kasir` (`kasir`),
  CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`kasir`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penjualan`
--

LOCK TABLES `penjualan` WRITE;
/*!40000 ALTER TABLE `penjualan` DISABLE KEYS */;
INSERT INTO `penjualan` VALUES (22,'2025-10-11 16:25:45','farah',170000.00,0.00,'owner1','selesai'),(23,'2025-10-11 16:45:40','salam',42.00,0.00,'owner1','selesai'),(24,'2025-10-11 16:45:52','habib',150000.00,0.00,'owner1','selesai'),(26,'2025-10-11 16:48:30','',50084.00,0.00,'owner1','aktif'),(28,'2025-10-11 22:40:43','',0.00,0.00,'owner1','aktif'),(29,'2025-10-11 22:40:51','salam',0.00,0.00,'owner1','aktif');
/*!40000 ALTER TABLE `penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `riwayat_stok`
--

DROP TABLE IF EXISTS `riwayat_stok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riwayat_stok` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_barang` int NOT NULL,
  `id_varian` int DEFAULT NULL,
  `jumlah` int NOT NULL,
  `tipe` enum('penambahan','pengurangan') NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_barang` (`id_barang`),
  KEY `fk_riwayat_varian` (`id_varian`),
  CONSTRAINT `fk_riwayat_varian` FOREIGN KEY (`id_varian`) REFERENCES `barang_varian` (`id_varian`) ON DELETE SET NULL,
  CONSTRAINT `riwayat_stok_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riwayat_stok`
--

LOCK TABLES `riwayat_stok` WRITE;
/*!40000 ALTER TABLE `riwayat_stok` DISABLE KEYS */;
INSERT INTO `riwayat_stok` VALUES (1,1,NULL,10,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:19:38'),(2,1,NULL,30,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:19:58'),(3,1,NULL,20,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:20:09'),(4,2,NULL,20,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:20:27'),(5,2,NULL,40,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:20:37'),(6,3,NULL,60,'penambahan','Varian baru ditambahkan ke sistem','2025-10-10 21:20:47'),(7,1,3,7,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 3)','2025-10-10 21:30:35'),(8,3,NULL,10,'penambahan','Varian baru ditambahkan ke sistem','2025-10-11 13:56:19'),(9,3,7,5,'pengurangan','Penjualan varian \'legging\' (ID Varian: 7)','2025-10-11 13:56:42'),(10,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 1)','2025-10-11 13:59:25'),(11,1,1,1,'penambahan','Transaksi #3 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 14:03:17'),(12,1,1,5,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 1)','2025-10-11 14:03:53'),(13,1,1,5,'penambahan','Transaksi #4 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 14:04:04'),(14,1,1,5,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 1)','2025-10-11 14:04:23'),(15,2,4,5,'pengurangan','Penjualan varian \'baju pria\' (ID Varian: 4)','2025-10-11 14:04:35'),(16,2,4,5,'penambahan','Transaksi #5 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 14:04:54'),(17,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 1)','2025-10-11 14:05:56'),(18,3,NULL,10,'penambahan','Varian baru ditambahkan ke sistem','2025-10-11 14:10:36'),(19,3,8,5,'pengurangan','Penjualan varian \'legging\' (ID Varian: 8)','2025-10-11 14:10:56'),(20,1,1,5,'pengurangan','Penjualan varian \'baju cewek\' (ID Varian: 1)','2025-10-11 14:16:32'),(21,1,1,5,'penambahan','Transaksi #8 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 14:16:50'),(22,3,NULL,10,'penambahan','Stok varian ditambah','2025-10-11 14:46:51'),(23,2,NULL,10,'penambahan','Varian baru ditambahkan ke sistem','2025-10-11 15:16:15'),(24,2,4,5,'pengurangan','Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)','2025-10-11 15:29:59'),(25,1,1,6,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 15:32:00'),(26,2,4,5,'penambahan','Transaksi #14 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 15:35:52'),(27,1,1,6,'penambahan','Transaksi #14 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 15:35:52'),(28,3,8,5,'penambahan','Transaksi #7 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 15:36:03'),(29,1,3,5,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 15:36:58'),(30,1,3,5,'penambahan','Transaksi #15 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 15:40:58'),(31,1,1,5,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 15:41:13'),(32,1,3,6,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 15:47:05'),(33,1,3,6,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 15:47:07'),(34,1,3,6,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 15:47:07'),(35,1,3,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 15:47:31'),(36,2,4,1,'pengurangan','Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)','2025-10-11 15:47:42'),(37,2,4,1,'pengurangan','Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)','2025-10-11 15:47:59'),(38,1,3,1,'penambahan','Item transaksi dihapus, stok dikembalikan (oleh owner1)','2025-10-11 16:12:38'),(39,1,3,6,'penambahan','Item transaksi dihapus, stok dikembalikan (oleh owner1)','2025-10-11 16:12:42'),(40,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:13:59'),(41,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:14:57'),(42,1,1,1,'penambahan','Transaksi #17 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:16:20'),(43,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:16:29'),(44,1,1,1,'penambahan','Transaksi #6 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:40'),(45,1,3,6,'penambahan','Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:55'),(46,1,3,6,'penambahan','Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:55'),(47,2,4,1,'penambahan','Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:55'),(48,2,4,1,'penambahan','Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:55'),(49,1,1,1,'penambahan','Transaksi #16 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:17:55'),(50,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:18:13'),(51,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:18:47'),(52,1,1,1,'penambahan','Transaksi #20 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:18:55'),(53,1,1,1,'penambahan','Transaksi #19 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:21:41'),(54,1,3,5,'pengurangan','Penjualan varian \'baju cewek\' (Warna: navi, Ukuran: xxl)','2025-10-11 16:23:15'),(55,1,3,7,'penambahan','Transaksi #21 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 16:23:42'),(56,1,1,5,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:25:57'),(57,1,1,2,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:45:47'),(58,1,1,1,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:46:01'),(59,1,1,2,'pengurangan','Penjualan varian \'baju cewek\' (Warna: hitam, Ukuran: m)','2025-10-11 16:46:09'),(60,2,4,1,'pengurangan','Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)','2025-10-11 16:48:36'),(61,2,4,4,'pengurangan','Penjualan varian \'baju pria\' (Warna: hitam, Ukuran: m)','2025-10-11 16:48:46'),(62,3,7,1,'pengurangan','Penjualan varian \'legging\' (Warna: navi, Ukuran: s)','2025-10-11 17:09:32'),(63,3,7,2,'pengurangan','Penjualan varian \'legging\' (Warna: navi, Ukuran: s)','2025-10-11 17:09:46'),(64,3,7,2,'penambahan','Transaksi #27 dihapus, stok varian dikembalikan (oleh owner1)','2025-10-11 17:10:16');
/*!40000 ALTER TABLE `riwayat_stok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','kasir') DEFAULT 'kasir',
  `nama_lengkap` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'owner1','password123','owner','Owner LABORA');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-28  3:17:34
