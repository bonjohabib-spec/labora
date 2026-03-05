<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login dan role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: ../auth/index.php");
    exit();
}

$today = date('Y-m-d');

// Query data
$qOmzet = mysqli_query($conn, "SELECT SUM(total) AS omzet FROM penjualan WHERE DATE(tanggal)='$today'");
$omzet = mysqli_fetch_assoc($qOmzet)['omzet'] ?? 0;

$qKeuntungan = mysqli_query($conn, "SELECT SUM(keuntungan) AS untung FROM penjualan WHERE DATE(tanggal)='$today'");
$keuntungan = mysqli_fetch_assoc($qKeuntungan)['untung'] ?? 0;

$qTransaksi = mysqli_query($conn, "SELECT COUNT(*) AS trx FROM penjualan WHERE DATE(tanggal)='$today'");
$transaksi = mysqli_fetch_assoc($qTransaksi)['trx'] ?? 0;

// ================================
// Barang terlaris + warna & ukuran
// ================================
$qBarang = mysqli_query($conn, "
    SELECT b.nama_barang, v.warna, v.ukuran, SUM(dp.qty) AS terjual
    FROM detail_penjualan dp
    JOIN barang_varian v ON dp.id_varian = v.id_varian
    JOIN barang b ON v.id_barang = b.id_barang
    GROUP BY b.nama_barang, v.warna, v.ukuran
    ORDER BY terjual DESC
    LIMIT 5
");

$qMenipis = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE stok < stok_min");
$qLebih = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE stok > stok_max");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-content">
      <div class="welcome-header">
        <h1>Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Owner') ?>! 👋</h1>
        <p>Inilah ringkasan aktivitas toko Anda hari ini, <?= date('d M Y') ?>.</p>
      </div>

      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-icon purple">💰</div>
          <div class="stat-info">
            <label>Omzet Hari Ini</label>
            <h3>Rp<?= number_format($omzet, 0, ',', '.') ?></h3>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon emerald">📈</div>
          <div class="stat-info">
            <label>Keuntungan Hari Ini</label>
            <h3 style="color: #059669;">Rp<?= number_format($keuntungan, 0, ',', '.') ?></h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon blue">🛒</div>
          <div class="stat-info">
            <label>Total Transaksi</label>
            <h3><?= number_format($transaksi, 0, ',', '.') ?> <small>Trx</small></h3>
          </div>
        </div>
      </div>

      <div class="dashboard-grid">
        <!-- BARANG TERLARIS -->
        <div class="dashboard-panel">
          <div class="panel-header">
            <h3>🏆 Leaderboard Penjualan</h3>
          </div>
          <div class="table-responsive">
            <table class="premium-table">
              <thead>
                <tr>
                  <th width="50">Pos</th>
                  <th>Produk & Varian</th>
                  <th class="txt-center">Terjual</th>
                </tr>
              </thead>
              <tbody>
                <?php $rank = 1; while ($b = mysqli_fetch_assoc($qBarang)): ?>
                  <tr>
                    <td class="txt-center"><span class="rank-badge rank-<?= $rank ?>"><?= $rank++ ?></span></td>
                    <td>
                      <div class="product-name"><?= htmlspecialchars($b['nama_barang']) ?></div>
                      <div class="product-meta"><?= htmlspecialchars($b['warna']) ?> - <?= htmlspecialchars($b['ukuran']) ?></div>
                    </td>
                    <td class="txt-center"><strong><?= number_format($b['terjual'], 0, ',', '.') ?></strong> <small>Pcs</small></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- NOTIFIKASI STOK -->
        <div class="dashboard-panel">
          <div class="panel-header">
            <h3>⚠️ Notifikasi Inventori</h3>
          </div>
          <div class="notif-container">
            <div class="notif-box danger">
              <div class="notif-box-icon">🚨</div>
              <div class="notif-box-content">
                <strong>Stok Menipis</strong>
                <ul>
                  <?php if (mysqli_num_rows($qMenipis) > 0): ?>
                    <?php while ($m = mysqli_fetch_assoc($qMenipis)): ?>
                      <li><?= htmlspecialchars($m['nama_barang']) ?></li>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <li class="empty-notif">Semua stok barang tercukupi.</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>

            <div class="notif-box success">
              <div class="notif-box-icon">📦</div>
              <div class="notif-box-content">
                <strong>Stok Melebihi Batas</strong>
                <ul>
                  <?php if (mysqli_num_rows($qLebih) > 0): ?>
                    <?php while ($l = mysqli_fetch_assoc($qLebih)): ?>
                      <li><?= htmlspecialchars($l['nama_barang']) ?></li>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <li class="empty-notif">Tidak ada stok berlebih.</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
