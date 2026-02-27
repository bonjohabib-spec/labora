<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

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

// Hitung laba bersih
$laba_bersih = $total_omzet - $total_pengeluaran;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
      .laporan-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 25px;
        margin-top: 20px;
      }
      .laporan-card table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
      }
      .laporan-card th, .laporan-card td {
        border: 1px solid #e5e7eb;
        padding: 12px 16px;
        text-align: center;
      }
      .laporan-card th {
        background: #2563eb;
        color: #fff;
      }
      .row-laba {
        background: #d1fae5;
        font-weight: bold;
      }
      .filter-form {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 10px;
      }
      .filter-form label {
        font-size: 14px;
        color: #374151;
      }
      .filter-form input[type="date"] {
        padding: 6px 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
      }
      .filter-form button {
        padding: 8px 18px;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
      }
      .filter-form button:hover {
        background: #1d4ed8;
      }
    </style>
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <div class="page-content">
        <h1>📊 Laporan Keuangan</h1>

        <form method="GET" class="filter-form">
          <div>
            <label>Dari Tanggal</label><br>
            <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
          </div>
          <div>
            <label>Sampai Tanggal</label><br>
            <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
          </div>
          <div>
            <button type="submit">Tampilkan</button>
          </div>
        </form>

        <div class="laporan-card">
          <table>
            <tr>
              <th>Keterangan</th>
              <th>Nominal (Rp)</th>
            </tr>
            <tr>
              <td><b>Total Omzet</b></td>
              <td><?= number_format($total_omzet, 0, ',', '.') ?></td>
            </tr>
            <tr>
              <td><b>Total Pengeluaran</b></td>
              <td><?= number_format($total_pengeluaran, 0, ',', '.') ?></td>
            </tr>
            <tr class="row-laba">
              <td>Laba Bersih</td>
              <td><?= number_format($laba_bersih, 0, ',', '.') ?></td>
            </tr>
          </table>
        </div>

      </div>
    </div>
  </div>
</body>
</html>
