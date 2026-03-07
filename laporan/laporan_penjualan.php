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

// 1. Ringkasan Laporan (Kartu Utama)
$query_summary = "SELECT 
                    SUM(total) as total_omset,
                    SUM(sisa_piutang) as total_piutang
                  FROM penjualan 
                  WHERE status='selesai' 
                  AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$res_summary = mysqli_query($conn, $query_summary);
$summ = mysqli_fetch_assoc($res_summary);

$total_omset = $summ['total_omset'] ?? 0;
$total_piutang = $summ['total_piutang'] ?? 0;

// Kas Masuk = (Total Omset - Sisa Piutang) - (Cicilan untuk nota di periode ini) + (Semua Cicilan yang masuk di periode ini)
// Logika: (Omset - Sisa) memberikan total uang yang SUDAH masuk untuk nota tsb sampai detik ini.
// Karena kita juga menjumlahkan $kas_cicilan secara global, maka cicilan nota baru (yang sudah terhitung di $kas_cicilan) harus kita kurangi di sisi Penjualan/DP.
$qKasCicilan = $conn->query("SELECT SUM(nominal) as total FROM pembayaran_piutang WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'");
$kas_cicilan = $qKasCicilan->fetch_assoc()['total'] ?? 0;

$qCicilanNotaBaru = $conn->query("SELECT SUM(nominal) FROM pembayaran_piutang 
                                  WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                                  AND id_penjualan IN (SELECT id_penjualan FROM penjualan WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir')");
$cicilan_nota_baru = $qCicilanNotaBaru->fetch_row()[0] ?? 0;

$total_kas_masuk = ($total_omset - $total_piutang) - $cicilan_nota_baru + $kas_cicilan;

// 2. Data Penjualan Terperinci
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

// Paginasi Item (Barang Terlaris)
$item_per_page = 5;
$item_page = isset($_GET['item_page']) ? max(1, intval($_GET['item_page'])) : 1;
$item_offset = ($item_page - 1) * $item_per_page;

$query_item_count = "SELECT COUNT(DISTINCT dp.id_varian) as total
                     FROM detail_penjualan dp
                     JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                     WHERE p.status='selesai'
                     AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$res_item_count = mysqli_query($conn, $query_item_count);
$item_total_data = mysqli_fetch_assoc($res_item_count)['total'];
$item_total_pages = ceil($item_total_data / $item_per_page);

// 3. Riset: Barang Terlaris (Detail per Varian) dengan Paginasi
$query_top_items = "SELECT b.nama_barang, v.warna, v.ukuran, SUM(dp.qty) as total_qty, SUM(dp.subtotal) as total_omzet
                    FROM detail_penjualan dp
                    JOIN barang_varian v ON dp.id_varian = v.id_varian
                    JOIN barang b ON v.id_barang = b.id_barang
                    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                    WHERE p.status='selesai'
                    AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    GROUP BY dp.id_varian
                    ORDER BY total_qty DESC LIMIT $item_per_page OFFSET $item_offset";
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

      <!-- SUMMARY CARDS V2 -->
      <div class="summary-stats">
        <div class="stat-card">
          <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;">💰</div>
          <div class="stat-info">
            <span class="stat-label">Total Omset</span>
            <span class="stat-value">Rp <?= number_format($total_omset, 0, ',', '.') ?></span>
            <span class="stat-desc">Volume penjualan kotor</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background: #f0fdf4; color: #22c55e;">💵</div>
          <div class="stat-info">
            <span class="stat-label">Total Kas Masuk</span>
            <span class="stat-value">Rp <?= number_format($total_kas_masuk, 0, ',', '.') ?></span>
            <span class="stat-desc">Tunai + DP + Cicilan</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent" style="background: #f97316;"></div>
          <div class="stat-icon" style="background: #fff7ed; color: #f97316;">📋</div>
          <div class="stat-info">
            <span class="stat-label">Total Piutang</span>
            <span class="stat-value">Rp <?= number_format($total_piutang, 0, ',', '.') ?></span>
            <span class="stat-desc">Sisa tagihan menggantung</span>
          </div>
        </div>
      </div>

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

          <?php if ($item_total_pages > 1): ?>
          <div class="pagination" style="margin-top: 10px; padding: 5px 0;">
            <span class="page-info" style="font-size: 10px;"><?= $item_page ?> / <?= $item_total_pages ?></span>
            <div class="page-nav">
              <?php 
              // Base URL harus membawa semua parameter agar tidak hilang saat navigasi
              $item_base_url = "?tanggal_awal=$tanggal_awal&tanggal_akhir=$tanggal_akhir&search=$search&page=$page&item_page=";
              ?>
              <?php if ($item_page > 1): ?>
                <a href="<?= $item_base_url ?>1" class="page-btn" style="width:24px; height:24px; font-size:12px;" title="Awal">«</a>
                <a href="<?= $item_base_url ?><?= $item_page - 1 ?>" class="page-btn" style="width:24px; height:24px; font-size:12px;" title="Sebelumnya">‹</a>
              <?php endif; ?>

              <?php if ($item_page < $item_total_pages): ?>
                <a href="<?= $item_base_url ?><?= $item_page + 1 ?>" class="page-btn" style="width:24px; height:24px; font-size:12px;" title="Selanjutnya">›</a>
                <a href="<?= $item_base_url ?><?= $item_total_pages ?>" class="page-btn" style="width:24px; height:24px; font-size:12px;" title="Akhir">»</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
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
              <?php while($row = mysqli_fetch_assoc($res_detail)): 
                $status_lunas = $row['sisa_piutang'] <= 0;
              ?>
              <tr>
                <td>
                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">#INV-<?= $row['id_penjualan'] ?></div>
                    <span class="status-badge <?= $status_lunas ? 'status-lunas' : 'status-piutang' ?>">
                        <?= $status_lunas ? '✅ Selesai' : '⏳ Cicil' ?>
                    </span>
                </td>
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
            $base_url = "?tanggal_awal=$tanggal_awal&tanggal_akhir=$tanggal_akhir&search=$search&item_page=$item_page&page=";
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
