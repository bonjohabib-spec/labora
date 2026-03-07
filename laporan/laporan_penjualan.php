<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// 1. Data Penjualan Terperinci
$query_detail = "SELECT * FROM penjualan 
                 WHERE status='selesai' 
                 AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
if ($search) {
    $query_detail .= " AND pelanggan LIKE '%$search%'";
}
$query_detail .= " ORDER BY tanggal DESC";
$res_detail = mysqli_query($conn, $query_detail);

// 2. Riset: Pelanggan Terbanyak (berdasarkan total belanja)
$query_top_customers = "SELECT pelanggan, COUNT(*) as transaksi, SUM(total) as total_belanja 
                        FROM penjualan 
                        WHERE status='selesai' 
                        AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                        GROUP BY pelanggan 
                        ORDER BY total_belanja DESC LIMIT 5";
$res_customers = mysqli_query($conn, $query_top_customers);

// 3. Riset: Barang Terlaris
$query_top_items = "SELECT b.nama_barang, SUM(dp.qty) as total_qty, SUM(dp.subtotal) as total_omzet
                    FROM detail_penjualan dp
                    JOIN barang b ON dp.id_barang = b.id_barang
                    WHERE dp.status='selesai'
                    AND DATE(dp.id_penjualan IN (SELECT id_penjualan FROM penjualan WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'))
                    GROUP BY dp.id_barang
                    ORDER BY total_qty DESC LIMIT 5";
// Koreksi query top items agar filter tanggalnya benar
$query_top_items = "SELECT b.nama_barang, SUM(dp.qty) as total_qty, SUM(dp.subtotal) as total_omzet
                    FROM detail_penjualan dp
                    JOIN barang b ON dp.id_barang = b.id_barang
                    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                    WHERE p.status='selesai'
                    AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    GROUP BY dp.id_barang
                    ORDER BY total_qty DESC LIMIT 5";
$res_items = mysqli_query($conn, $query_top_items);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rincian Penjualan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan_penjualan.css?v=<?= time() ?>">
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-content">
      <div style="margin-bottom: 20px;">
        <a href="laporan.php" style="text-decoration: none; color: #64748b; font-size: 14px;">← Kembali ke Ringkasan</a>
        <h2 style="margin-top: 10px;">🛒 Rincian Laporan Penjualan</h2>
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
          <div class="input-control">
            <label>Cari Pelanggan</label>
            <input type="text" name="search" placeholder="Nama pelanggan..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <button type="submit" class="btn-filter">Tampilkan</button>
        </div>
      </form>

      <div class="analytics-grid">
        <!-- TOP CUSTOMERS -->
        <div class="analytics-card">
          <h4>🏆 Pelanggan Terbanyak (Riset)</h4>
          <table class="table-mini">
            <thead>
              <tr>
                <th>Pelanggan</th>
                <th style="text-align: center;">Transaksi</th>
                <th style="text-align: right;">Total Belanja</th>
              </tr>
            </thead>
            <tbody>
              <?php $rank = 1; while($c = mysqli_fetch_assoc($res_customers)): ?>
              <tr>
                <td><span class="rank-badge <?= $rank==1?'rank-1':'' ?>"><?= $rank++ ?></span> <?= htmlspecialchars($c['pelanggan'] ?: '-') ?></td>
                <td style="text-align: center;"><?= $c['transaksi'] ?>x</td>
                <td style="text-align: right; font-weight: 600;">Rp <?= number_format($c['total_belanja'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if (mysqli_num_rows($res_customers) == 0): ?>
              <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada data.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- TOP ITEMS -->
        <div class="analytics-card">
          <h4>🔥 Barang Terlaris (Riset)</h4>
          <table class="table-mini">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th style="text-align: center;">Terjual</th>
                <th style="text-align: right;">Omzet</th>
              </tr>
            </thead>
            <tbody>
              <?php $rank = 1; while($i = mysqli_fetch_assoc($res_items)): ?>
              <tr>
                <td><span class="rank-badge <?= $rank==1?'rank-1':'' ?>"><?= $rank++ ?></span> <?= htmlspecialchars($i['nama_barang']) ?></td>
                <td style="text-align: center;"><?= $i['total_qty'] ?> unit</td>
                <td style="text-align: right; font-weight: 600;">Rp <?= number_format($i['total_omzet'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if (mysqli_num_rows($res_items) == 0): ?>
              <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada data.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="detail-card">
        <h4>📄 Daftar Transaksi Penjualan</h4>
        <table class="table-mini" style="font-size: 14px;">
          <thead>
            <tr style="background: #f8fafc;">
              <th style="padding: 15px;">No. Invoice</th>
              <th>Tanggal</th>
              <th>Pelanggan</th>
              <th>Kasir</th>
              <th style="text-align: right; padding-right: 15px;">Total (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($res_detail)): ?>
            <tr>
              <td style="padding: 15px; font-weight: 600; color: #2563eb;">#INV-<?= $row['id_penjualan'] ?></td>
              <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
              <td><?= htmlspecialchars($row['pelanggan'] ?: '-') ?></td>
              <td><?= htmlspecialchars($row['kasir']) ?></td>
              <td style="text-align: right; padding-right: 15px; font-weight: bold;">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($res_detail) == 0): ?>
            <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Tidak ada data transaksi ditemukan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body>
</html>
