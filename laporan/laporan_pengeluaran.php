<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

// 1. Data Pengeluaran Terperinci
$query_detail = "SELECT * FROM pengeluaran 
                 WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                 ORDER BY tanggal DESC";
$res_detail = mysqli_query($conn, $query_detail);

// 2. Analisis: Pengeluaran per Kategori
$query_category = "SELECT kategori, COUNT(*) as jumlah_transaksi, SUM(nominal) as total_nominal 
                   FROM pengeluaran 
                   WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                   GROUP BY kategori 
                   ORDER BY total_nominal DESC";
$res_category = mysqli_query($conn, $query_category);

// 3. Hitung Total untuk Persentase
$query_total = "SELECT SUM(nominal) as grand_total FROM pengeluaran 
                WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$grand_total = mysqli_fetch_assoc(mysqli_query($conn, $query_total))['grand_total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rincian Pengeluaran - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan_pengeluaran.css?v=<?= time() ?>">
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-content">
      <div style="margin-bottom: 20px;">
        <a href="laporan.php" style="text-decoration: none; color: #64748b; font-size: 14px;">← Kembali ke Ringkasan</a>
        <h2 style="margin-top: 10px;">💸 Rincian Laporan Pengeluaran</h2>
      </div>

      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <div class="input-control">
            <label>Dari Tanggal</label>
            <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>">
          </div>
          <div class="input-control">
            <label>Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
          </div>
          <button type="submit" class="btn-filter">Tampilkan</button>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 12px; color: #64748b;">Total Pengeluaran Periode Ini</div>
          <div style="font-size: 24px; font-weight: bold; color: #ef4444;">Rp <?= number_format($grand_total, 0, ',', '.') ?></div>
        </div>
      </form>

      <div class="analytics-grid">
        <div class="analytics-card">
          <h4>📊 Alokasi Dana per Kategori</h4>
          <?php if ($grand_total > 0): ?>
            <?php while($cat = mysqli_fetch_assoc($res_category)): 
              $persen = ($cat['total_nominal'] / $grand_total) * 100;
            ?>
            <div class="category-row">
              <div class="category-info">
                <span class="category-name"><?= htmlspecialchars($cat['kategori'] ?: 'Lain-lain') ?></span>
                <span class="category-val">Rp <?= number_format($cat['total_nominal'], 0, ',', '.') ?> (<?= round($persen, 1) ?>%)</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $persen ?>%;"></div>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada data pengeluaran.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-card">
        <h4>📄 Daftar Rincian Pengeluaran</h4>
        <div class="table-container">
          <table class="table-mini">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th style="text-align: right;">Nominal (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = mysqli_fetch_assoc($res_detail)): ?>
              <tr>
                <td style="color: #64748b;"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><span class="badge-kategori"><?= htmlspecialchars($row['kategori'] ?: 'Lain-lain') ?></span></td>
                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                <td style="text-align: right; font-weight: 700; color: #ef4444;">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if (mysqli_num_rows($res_detail) == 0): ?>
              <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;">Tidak ada data pengeluaran ditemukan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
