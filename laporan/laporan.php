<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil filter tanggal jika ada
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

// Total omzet (total penjualan)
$query_penjualan = mysqli_query($conn, "
    SELECT SUM(total) AS total_omzet 
    FROM penjualan 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
      AND status = 'selesai'
");
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$total_omzet = $data_penjualan['total_omzet'] ?? 0;

// Total pengeluaran
$query_pengeluaran = mysqli_query($conn, "
    SELECT SUM(nominal) AS total_pengeluaran 
    FROM pengeluaran 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
");
$data_pengeluaran = mysqli_fetch_assoc($query_pengeluaran);
$total_pengeluaran = $data_pengeluaran['total_pengeluaran'] ?? 0;

// Rincian Penjualan
$query_rincian_penjualan = mysqli_query($conn, "
    SELECT id_penjualan, tanggal, pelanggan, total 
    FROM penjualan 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
      AND status = 'selesai'
    ORDER BY tanggal DESC
");

// Rincian Pengeluaran
$query_rincian_pengeluaran = mysqli_query($conn, "
    SELECT tanggal, kategori, deskripsi, nominal 
    FROM pengeluaran 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    ORDER BY tanggal DESC
");

// Hitung laba bersih
$laba_bersih = $total_omzet - $total_pengeluaran;
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Keuangan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan.css?v=<?= time() ?>">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>
      
      <div class="page-content">
        <div class="welcome-header">
            <h1>📊 Laporan Keuangan</h1>
            <p>Pantau omzet, pengeluaran, dan laba bersih toko Anda.</p>
        </div>

        <form action="" method="GET" class="filter-form">
          <div class="form-group">
            <label>Mulai Tanggal</label>
            <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>">
          </div>
          <div class="form-group">
            <label>Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
          </div>
          <button type="submit">Filter Laporan</button>
        </form>

        <div class="stat-cards">
          <div class="stat-card">
            <div class="stat-icon purple">💰</div>
            <div class="stat-info">
              <label>Total Omzet</label>
              <h3>Rp<?= number_format($total_omzet, 0, ',', '.') ?></h3>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon danger">💸</div>
            <div class="stat-info">
              <label>Total Pengeluaran</label>
              <h3 style="color: #ef4444;">Rp<?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
            </div>
          </div>

          <div class="stat-card laba">
            <div class="stat-icon emerald">📈</div>
            <div class="stat-info">
              <label>Laba Bersih</label>
              <h3 style="color: #059669;">Rp<?= number_format($laba_bersih, 0, ',', '.') ?></h3>
            </div>
          </div>
        </div>

        <div class="report-grid">
          <a href="laporan_penjualan.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="report-card">
            <h3>🛒 Laporan Penjualan Detail</h3>
            <p>Lihat rincian transaksi per invoice, analisis pelanggan terbanyak, dan riset barang terlaris untuk periode ini.</p>
            <div class="btn-go">Buka Rincian →</div>
          </a>

          <a href="laporan_pengeluaran.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="report-card">
            <h3>💸 Laporan Pengeluaran Detail</h3>
            <p>Lihat rincian biaya operasional, gaji, dan stok. Analisis alokasi dana berdasarkan kategori pengeluaran.</p>
            <div class="btn-go">Buka Rincian →</div>
          </a>
        </div>

      </div> <!-- .page-content -->
    </div> <!-- .main-content -->
  </div> <!-- .container -->
</body>
</html>
