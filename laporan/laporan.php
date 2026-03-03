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
    .laporan-card h3 {
      margin-bottom: 15px;
      color: #1e293b;
      font-size: 18px;
    }
    .table-summary, .table-detail {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 30px;
    }
    .table-summary th, .table-summary td {
      border: 1px solid #e5e7eb;
      padding: 12px 16px;
      text-align: center;
    }
    .table-summary th {
      background: #2563eb;
      color: #fff;
    }
    .table-detail th, .table-detail td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
    }
    .table-detail th {
      background: #f8fafc;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.05em;
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
      margin-bottom: 25px;
    }
    .filter-form label {
      font-size: 14px;
      color: #374151;
    }
    .filter-form input[type="date"] {
      padding: 8px 12px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      outline: none;
    }
    .filter-form button {
      padding: 9px 22px;
      background: #2563eb;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    .filter-form button:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
    }
    .badge-kategori {
      background: #f1f5f9;
      color: #475569;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
    }
    .txt-right { text-align: right !important; }
    .txt-center { text-align: center !important; }
  </style>
</head>
<body>
<div class="container"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content"><?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
      <div class="page-header">
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
      </div>

      <div class="laporan-card">
        <h3>💰 Ringkasan Laba/Rugi</h3>
        <table class="table-summary">
          <tr>
            <th>Keterangan</th>
            <th>Nominal (Rp)</th>
          </tr>
          <tr>
            <td><b>Total Omzet (Penjualan Selesai)</b></td>
            <td class="txt-right"><?= number_format($total_omzet, 0, ',', '.') ?></td>
          </tr>
          <tr>
            <td><b>Total Pengeluaran</b></td>
            <td class="txt-right"><?= number_format($total_pengeluaran, 0, ',', '.') ?></td>
          </tr>
          <tr class="row-laba">
            <td>Laba Bersih</td>
            <td class="txt-right"><?= number_format($laba_bersih, 0, ',', '.') ?></td>
          </tr>
        </table>

        <!-- RINCIAN PENJUALAN -->
        <h3 style="margin-top: 40px;">🛒 Rincian Penjualan</h3>
        <table class="table-detail">
          <thead>
            <tr>
              <th class="txt-center">No. Inv</th>
              <th>Tanggal</th>
              <th>Pelanggan</th>
              <th class="txt-right">Total (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($query_rincian_penjualan) == 0): ?>
              <tr><td colspan="4" class="txt-center" style="color:#94a3b8; padding:20px;">Tidak ada penjualan dalam rentang waktu ini.</td></tr>
            <?php else: ?>
              <?php while ($rp = mysqli_fetch_assoc($query_rincian_penjualan)): ?>
                <tr>
                  <td class="txt-center">#INV-<?= $rp['id_penjualan'] ?></td>
                  <td><?= date('d/m/Y', strtotime($rp['tanggal'])) ?></td>
                  <td><?= htmlspecialchars($rp['pelanggan'] ?: '-') ?></td>
                  <td class="txt-right"><b><?= number_format($rp['total'], 0, ',', '.') ?></b></td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- RINCIAN PENGELUARAN -->
        <h3 style="margin-top: 40px;">💸 Rincian Pengeluaran</h3>
        <table class="table-detail">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Keterangan</th>
              <th class="txt-right">Nominal (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($query_rincian_pengeluaran) == 0): ?>
              <tr><td colspan="4" class="txt-center" style="color:#94a3b8; padding:20px;">Tidak ada pengeluaran dalam rentang waktu ini.</td></tr>
            <?php else: ?>
              <?php while ($rex = mysqli_fetch_assoc($query_rincian_pengeluaran)): ?>
                <tr>
                  <td><?= date('d/m/Y', strtotime($rex['tanggal'])) ?></td>
                  <td><span class="badge-kategori"><?= htmlspecialchars($rex['kategori'] ?: 'Umum') ?></span></td>
                  <td><?= htmlspecialchars($rex['deskripsi']) ?></td>
                  <td class="txt-right"><b><?= number_format($rex['nominal'], 0, ',', '.') ?></b></td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>
</body>
</html>
