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

    <h2>Dashboard - LABORA</h2>

    <div class="page-content">
      <div class="cards">
        <div class="card">
          <p>Omzet Hari Ini</p>
          <h2>Rp<?= number_format($omzet, 0, ',', '.') ?></h2>
        </div>
        <div class="card">
          <p>Keuntungan Hari Ini</p>
          <h2>Rp<?= number_format($keuntungan, 0, ',', '.') ?></h2>
        </div>
        <div class="card">
          <p>Transaksi Hari Ini</p>
          <h2><?= $transaksi ?></h2>
        </div>
      </div>

      <div class="content-grid">
        <div class="barang-terlaris">
          <div class="header">
            <h3>Barang Terjual Terbanyak</h3>
            <a href="#">Lihat Semua</a>
          </div>
          <table>
            <thead>
              <tr>
                <th>Barang</th>
                <th>Warna</th>
                <th>Ukuran</th>
                <th>Terjual</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($b = mysqli_fetch_assoc($qBarang)): ?>
                <tr>
                  <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                  <td><?= htmlspecialchars($b['warna']) ?></td>
                  <td><?= htmlspecialchars($b['ukuran']) ?></td>
                  <td><?= $b['terjual'] ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="notifikasi-stok">
          <h3>Notifikasi Stok</h3>
          <div class="notif">
            <p class="title red">Stok Menipis</p>
            <ul>
              <?php while ($m = mysqli_fetch_assoc($qMenipis)): ?>
                <li><?= htmlspecialchars($m['nama_barang']) ?></li>
              <?php endwhile; ?>
            </ul>
          </div>
          <div class="notif">
            <p class="title green">Stok Melebihi Batas</p>
            <ul>
              <?php while ($l = mysqli_fetch_assoc($qLebih)): ?>
                <li><?= htmlspecialchars($l['nama_barang']) ?></li>
              <?php endwhile; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
