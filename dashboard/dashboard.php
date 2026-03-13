<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';

// Cek login dan role
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['user_role'] === 'kasir') {
    header("Location: ../penjualan/penjualan.php");
    exit();
}

if ($_SESSION['user_role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

$kasir = $_SESSION['username'];
$active_shift = checkShift($conn, $kasir); // Wajib buka kasir dulu

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 1. Data Hari Ini
$stmt1 = $conn->prepare("SELECT SUM(total) AS omzet FROM penjualan WHERE DATE(tanggal)=? AND status='selesai'");
$stmt1->bind_param("s", $today);
$stmt1->execute();
$omzet = $stmt1->get_result()->fetch_assoc()['omzet'] ?? 0;

$stmt2 = $conn->prepare("SELECT SUM(keuntungan) AS untung FROM penjualan WHERE DATE(tanggal)=? AND status='selesai'");
$stmt2->bind_param("s", $today);
$stmt2->execute();
$keuntungan = $stmt2->get_result()->fetch_assoc()['untung'] ?? 0;

$stmt3 = $conn->prepare("SELECT COUNT(*) AS trx FROM penjualan WHERE DATE(tanggal)=? AND status='selesai'");
$stmt3->bind_param("s", $today);
$stmt3->execute();
$transaksi = $stmt3->get_result()->fetch_assoc()['trx'] ?? 0;

// 2. Data Kemarin (Untuk Perbandingan)
$stmt4 = $conn->prepare("SELECT SUM(total) AS omzet FROM penjualan WHERE DATE(tanggal)=? AND status='selesai'");
$stmt4->bind_param("s", $yesterday);
$stmt4->execute();
$omzetYest = $stmt4->get_result()->fetch_assoc()['omzet'] ?? 0;

$stmt5 = $conn->prepare("SELECT SUM(keuntungan) AS untung FROM penjualan WHERE DATE(tanggal)=? AND status='selesai'");
$stmt5->bind_param("s", $yesterday);
$stmt5->execute();
$keuntunganYest = $stmt5->get_result()->fetch_assoc()['untung'] ?? 0;

// Hitung Persentase Pertumbuhan
function getGrowth($current, $past) {
    if ($past <= 0) return $past == 0 && $current > 0 ? 100 : 0;
    return (($current - $past) / $past) * 100;
}

$growthOmzet = getGrowth((float)$omzet, (float)$omzetYest);
$growthUntung = getGrowth((float)$keuntungan, (float)$keuntunganYest);

// 3. Leaderboard 7 Hari Terakhir
$stmt6 = $conn->prepare("
    SELECT b.nama_barang, v.warna, v.ukuran, SUM(dp.qty) AS terjual
    FROM detail_penjualan dp
    JOIN barang_varian v ON dp.id_varian = v.id_varian
    JOIN barang b ON v.id_barang = b.id_barang
    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
    WHERE p.status='selesai' AND DATE(p.tanggal) BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND ?
    GROUP BY v.id_varian
    ORDER BY terjual DESC
    LIMIT 5
");
$stmt6->bind_param("ss", $today, $today);
$stmt6->execute();
$resBarang = $stmt6->get_result();

// 4. Pengeluaran Terbaru
$stmt7 = $conn->prepare("SELECT * FROM pengeluaran ORDER BY tanggal DESC LIMIT 5");
$stmt7->execute();
$resPengeluaran = $stmt7->get_result();

// 5. Notifikasi Stok
$stmt8 = $conn->prepare("SELECT b.nama_barang, v.warna, v.ukuran FROM barang_varian v JOIN barang b ON v.id_barang = b.id_barang WHERE v.stok <= 0 AND v.status='aktif'");
$stmt8->execute();
$resHabis = $stmt8->get_result();

$stmt9 = $conn->prepare("SELECT b.nama_barang, v.warna, v.ukuran, v.stok FROM barang_varian v JOIN barang b ON v.id_barang = b.id_barang WHERE v.stok > 0 AND v.stok <= b.stok_min AND v.status='aktif'");
$stmt9->execute();
$resMenipis = $stmt9->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
</head>
<body>

<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-content">
      <div class="welcome-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
          <h1>Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Owner') ?>! 👋</h1>
          <p>Ringkasan aktivitas toko hari ini, <?= date('d M Y') ?>. 
             <span class="badge" style="background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; margin-left: 10px;">
                🟢 SHIFT AKTIF (Sejak <?= date('H:i', strtotime($active_shift['waktu_buka'])) ?>)
             </span>
          </p>
        </div>
      </div>

      <!-- Quick Action Bar -->
      <div class="quick-actions">
        <a href="../penjualan/penjualan.php?action=baru" class="action-btn primary">
          <span class="btn-icon">🛒</span>
          <div class="btn-text">
            <strong>Kasir Baru</strong>
            <small>Buat transaksi penjualan</small>
          </div>
        </a>
        <a href="../barang/tambah_barang.php" class="action-btn secondary">
          <span class="btn-icon">📦</span>
          <div class="btn-text">
            <strong>Tambah SKU</strong>
            <small>Input nama barang</small>
          </div>
        </a>
        <a href="../pengeluaran/tambah_pengeluaran.php" class="action-btn warning">
          <span class="btn-icon">💸</span>
          <div class="btn-text">
            <strong>Catat Biaya</strong>
            <small>Input pengeluaran kas</small>
          </div>
        </a>
      </div>

      <div class="stat-cards">
        <div class="stat-card card-omzet">
          <div class="stat-info">
            <label>Omzet Hari Ini</label>
            <div class="val-flex">
              <h3>Rp<?= number_format($omzet, 0, ',', '.') ?></h3>
              <span class="growth-tag <?= $growthOmzet >= 0 ? 'up' : 'down' ?>">
                <?= $growthOmzet >= 0 ? '↑' : '↓' ?> <?= abs(round($growthOmzet)) ?>%
              </span>
            </div>
          </div>
        </div>
        
        <div class="stat-card card-laba">
          <div class="stat-info">
            <label>Laba Bersih Hari Ini</label>
            <div class="val-flex">
              <h3 style="color: #059669;">Rp<?= number_format($keuntungan, 0, ',', '.') ?></h3>
              <span class="growth-tag <?= $growthUntung >= 0 ? 'up' : 'down' ?>">
                <?= $growthUntung >= 0 ? '↑' : '↓' ?> <?= abs(round($growthUntung)) ?>%
              </span>
            </div>
          </div>
        </div>

        <div class="stat-card card-transaksi">
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
                <?php $rank = 1; while ($b = $resBarang->fetch_assoc()): ?>
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
            <h3>⚠️ Status Stok & Pengeluaran</h3>
          </div>
          <div class="notif-container">
            <!-- PENGELUARAN TERAKHIR -->
             <div class="recent-expenses">
               <label>💸 Biaya Terakhir</label>
               <?php if ($resPengeluaran->num_rows > 0): ?>
                <div class="expense-list">
                  <?php while ($xp = $resPengeluaran->fetch_assoc()): ?>
                    <div class="expense-item">
                      <div>
                        <div class="product-name"><?= htmlspecialchars($xp['deskripsi']) ?></div>
                        <div class="product-meta"><?= date('d M', strtotime($xp['tanggal'])) ?> • <?= htmlspecialchars($xp['kategori']) ?></div>
                      </div>
                      <div class="expense-amount">- Rp<?= number_format($xp['nominal'], 0, ',', '.') ?></div>
                    </div>
                  <?php endwhile; ?>
                </div>
               <?php else: ?>
                <p class="empty-notif">Belum ada catatan biaya.</p>
               <?php endif; ?>
             </div>

            <div class="notif-box danger">
              <div class="notif-box-icon">🛑</div>
              <div class="notif-box-content">
                <strong>Stok Habis (Sangat Penting)</strong>
                <ul>
                  <?php if ($resHabis->num_rows > 0): ?>
                    <?php while ($h = $resHabis->fetch_assoc()): ?>
                      <li><?= htmlspecialchars($h['nama_barang']) ?> (<?= htmlspecialchars($h['warna']) ?>-<?= htmlspecialchars($h['ukuran']) ?>)</li>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <li class="empty-notif">Tidak ada stok yang kosong total.</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>

            <div class="notif-box warning">
              <div class="notif-box-icon">⚠️</div>
              <div class="notif-box-content">
                <strong>Stok Menipis</strong>
                <ul>
                  <?php if ($resMenipis->num_rows > 0): ?>
                    <?php while ($m = $resMenipis->fetch_assoc()): ?>
                      <li><?= htmlspecialchars($m['nama_barang']) ?> (<?= $m['warna'] ?>-<?= $m['ukuran'] ?>) - Sisa <strong><?= $m['stok'] ?></strong></li>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <li class="empty-notif">Stok varian lainnya masih aman.</li>
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
