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

// Paginasi
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$query_count = "SELECT COUNT(*) as total FROM penjualan WHERE status='selesai' AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
if ($search) {
    $query_count .= " AND pelanggan LIKE '%$search%'";
}
$res_count = mysqli_query($conn, $query_count);
$total_data = mysqli_fetch_assoc($res_count)['total'];
$total_pages = ceil($total_data / $per_page);

// 1. Data Penjualan Terperinci
$query_detail = "SELECT * FROM penjualan 
                 WHERE status='selesai' 
                 AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
if ($search) {
    $query_detail .= " AND pelanggan LIKE '%$search%'";
}
$query_detail .= " ORDER BY tanggal DESC LIMIT $per_page OFFSET $offset";
$res_detail = mysqli_query($conn, $query_detail);

// 2. Riset: Pelanggan Terbanyak (berdasarkan total belanja)
$query_top_customers = "SELECT pelanggan, COUNT(*) as transaksi, SUM(total) as total_belanja 
                        FROM penjualan 
                        WHERE status='selesai' 
                        AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                        GROUP BY pelanggan 
                        ORDER BY transaksi DESC LIMIT 5";
$res_customers = mysqli_query($conn, $query_top_customers);

// 3. Riset: Barang Terlaris
$query_top_items = "SELECT b.nama_barang, SUM(dp.qty) as total_qty, SUM(dp.subtotal) as total_omzet
                    FROM detail_penjualan dp
                    JOIN barang b ON dp.id_barang = b.id_barang
                    WHERE dp.status='selesai'
                    AND DATE(dp.id_penjualan IN (SELECT id_penjualan FROM penjualan WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'))
                    GROUP BY dp.id_barang
                    ORDER BY total_qty DESC LIMIT 5";
// 3. Riset: Barang Terlaris (Detail per Varian)
$query_top_items = "SELECT b.nama_barang, v.warna, v.ukuran, SUM(dp.qty) as total_qty, SUM(dp.subtotal) as total_omzet
                    FROM detail_penjualan dp
                    JOIN barang_varian v ON dp.id_varian = v.id_varian
                    JOIN barang b ON v.id_barang = b.id_barang
                    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                    WHERE p.status='selesai'
                    AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    GROUP BY dp.id_varian
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
                 <td>
                   <span class="rank-badge <?= $rank==1?'rank-1':'' ?>"><?= $rank++ ?></span> 
                   <strong><?= htmlspecialchars($i['nama_barang']) ?></strong>
                   <div style="font-size: 10px; color: #64748b; margin-left: 22px; margin-top: 2px;">
                     <?= htmlspecialchars($i['warna'] ?: '-') ?> - <?= htmlspecialchars($i['ukuran'] ?: '-') ?>
                   </div>
                 </td>
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
        <div class="table-container">
          <table class="table-mini">
            <thead>
              <tr>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Kasir</th>
                <th style="text-align: right;">Total (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = mysqli_fetch_assoc($res_detail)): ?>
              <tr>
                <td style="font-weight: 600; color: var(--primary-blue, #3b82f6);">#INV-<?= $row['id_penjualan'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                <td><?= htmlspecialchars($row['pelanggan'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['kasir']) ?></td>
                <td style="text-align: right; font-weight: 700;">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if (mysqli_num_rows($res_detail) == 0): ?>
              <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Tidak ada data transaksi ditemukan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <span class="page-info">Halaman <?= $page ?> / <?= $total_pages ?></span>
          <div class="page-nav">
            <?php 
            $base_url = "?tanggal_awal=$tanggal_awal&tanggal_akhir=$tanggal_akhir&search=$search&page=";
            ?>
            <?php if ($page > 1): ?>
              <a href="<?= $base_url ?>1" class="page-btn" title="Awal">«</a>
              <a href="<?= $base_url ?><?= $page - 1 ?>" class="page-btn" title="Sebelumnya">‹</a>
            <?php else: ?>
              <span class="page-btn disabled">«</span>
              <span class="page-btn disabled">‹</span>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
              <a href="<?= $base_url ?><?= $page + 1 ?>" class="page-btn" title="Selanjutnya">›</a>
              <a href="<?= $base_url ?><?= $total_pages ?>" class="page-btn" title="Akhir">»</a>
            <?php else: ?>
              <span class="page-btn disabled">›</span>
              <span class="page-btn disabled">»</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>
</body>
</html>
