<?php
include '../includes/koneksi.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

$view_status = $_GET['view'] ?? 'aktif';

// 1. PROSES ARCHIVE/RESTORE
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['s'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['s'] == 'aktif' ? 'aktif' : 'non-aktif';
    
    $conn->query("UPDATE barang_varian SET status = '$status' WHERE id_varian = $id");
    header("Location: stok_barang.php?view=$view_status");
    exit;
}

// 2. PROSES HAPUS PERMANEN (Hanya jika belum pernah dijual)
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_varian = (int)$_GET['id'];
    
    // Cek apakah sudah ada transaksi
    $cek = $conn->query("SELECT COUNT(*) as jml FROM detail_penjualan WHERE id_varian = $id_varian")->fetch_assoc();
    if ($cek['jml'] > 0) {
        echo "<script>alert('Gagal: Varian ini sudah memiliki histori transaksi dan tidak bisa dihapus permanen.'); window.location='stok_barang.php';</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        // Ambil id_barang sebelum dihapus
        $vData = $conn->query("SELECT id_barang FROM barang_varian WHERE id_varian = $id_varian")->fetch_assoc();
        $id_barang = $vData['id_barang'];

        // Hapus varian
        $conn->query("DELETE FROM barang_varian WHERE id_varian = $id_varian");

        // Jika tidak ada varian tersisa, hapus barang utama
        $cekLagi = $conn->query("SELECT COUNT(*) as sisa FROM barang_varian WHERE id_barang = $id_barang")->fetch_assoc();
        $sisaVarian = $cekLagi['sisa'];
        
        if ($sisaVarian == 0) {
            // Hapus sisa riwayat yang mungkin hanya terikat ke id_barang
            $conn->query("DELETE FROM riwayat_stok WHERE id_barang = $id_barang");
            $conn->query("DELETE FROM barang WHERE id_barang = $id_barang");
        }
        
        $conn->commit();
        echo "<script>alert('Barang berhasil dihapus!'); window.location='stok_barang.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menghapus: " . $e->getMessage() . "'); window.location='stok_barang.php';</script>";
        exit;
    }
}

// 3. STATISTIK RINGKASAN STOK
$qTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_varian WHERE status='aktif'");
$totalBarang = mysqli_fetch_assoc($qTotal)['total'] ?? 0;

$qLow = mysqli_query($conn, "SELECT COUNT(*) as low FROM barang_varian v JOIN barang b ON v.id_barang = b.id_barang WHERE v.stok > 0 AND v.stok <= b.stok_min AND v.status='aktif'");
$totalLow = mysqli_fetch_assoc($qLow)['low'] ?? 0;

$qEmpty = mysqli_query($conn, "SELECT COUNT(*) as jml_habis FROM barang_varian WHERE stok <= 0 AND status='aktif'");
$totalEmpty = mysqli_fetch_assoc($qEmpty)['jml_habis'] ?? 0;

$qUnits = mysqli_query($conn, "SELECT SUM(stok) as total_unit FROM barang_varian WHERE status='aktif'");
$totalUnits = mysqli_fetch_assoc($qUnits)['total_unit'] ?? 0;

// 4. FILTER KARTU (Clickable Cards)
$filter_type = $_GET['filter'] ?? 'all';
$filter_sql = "";
if ($filter_type == 'low') {
    $filter_sql = " AND v.stok > 0 AND v.stok <= b.stok_min ";
} elseif ($filter_type == 'empty') {
    $filter_sql = " AND v.stok <= 0 ";
}

// Gabungkan barang dan varian dengan filter status + cek apakah sudah pernah dijual
// Menggunakan LEFT JOIN agar data varian tetap muncul meskipun ada isu di tabel barang
$sql = "
  SELECT b.id_barang, b.nama_barang, v.id_varian, v.warna, v.ukuran, 
         v.harga_beli, v.harga_jual, v.stok, v.status as varian_status,
         b.stok_min, b.stok_max,
         (SELECT COUNT(*) FROM detail_penjualan dp WHERE dp.id_varian = v.id_varian) AS total_terjual
  FROM barang_varian v
  LEFT JOIN barang b ON v.id_barang = b.id_barang
  WHERE v.status = '$view_status' $filter_sql
  ORDER BY b.nama_barang ASC, v.warna ASC, v.ukuran ASC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Stok Barang - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/stok_barang.css?v=<?= time() ?>">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <div class="page-content">
        <!-- PREMIUM HEADER AREA -->
        <div class="page-header-redesign">
          <div class="header-left">
            <h1>📦 Manajemen Stok</h1>
            <div class="tab-navigation">
              <a href="?view=aktif" class="tab-item <?= $view_status == 'aktif' ? 'active' : '' ?>">Barang Aktif</a>
              <a href="?view=non-aktif" class="tab-item <?= $view_status == 'non-aktif' ? 'active' : '' ?>">Arsip (Non-Aktif)</a>
            </div>
          </div>
          <div class="header-right">
            <button class="btn-primary" onclick="window.location.href='tambah_barang.php'">+ Tambah Barang Baru</button>
          </div>
        </div>

        <!-- SUMMARY CARDS V2 REFINED -->
        <div class="summary-cards" style="grid-template-columns: repeat(4, 1fr);">
          <a href="?view=<?= $view_status ?>&filter=all" class="summary-card card-total <?= $filter_type == 'all' ? 'active-filter' : '' ?>" style="text-decoration: none;">
            <div class="summary-header">
              <span class="summary-title">Total Varian</span>
              <span class="summary-badge"><?= $totalBarang ?></span>
            </div>
            <div class="summary-value"><?= number_format($totalBarang, 0, ',', '.') ?> <small>Item</small></div>
            <span class="summary-label">Varian produk aktif</span>
          </a>

          <a href="#" class="summary-card card-units" style="text-decoration: none; cursor: default;">
            <div class="summary-header">
              <span class="summary-title">Total Unit (Stok)</span>
              <span class="summary-badge"><?= number_format($totalUnits, 0, ',', '.') ?></span>
            </div>
            <div class="summary-value" style="color: #6366f1;"><?= number_format($totalUnits, 0, ',', '.') ?> <small>Unit</small></div>
            <span class="summary-label">Jumlah fisik di gudang</span>
          </a>

          <a href="?view=<?= $view_status ?>&filter=low" class="summary-card card-warning <?= $filter_type == 'low' ? 'active-filter' : '' ?>" style="text-decoration: none;">
            <div class="summary-header">
              <span class="summary-title">Stok Menipis</span>
              <span class="summary-badge"><?= $totalLow ?></span>
            </div>
            <div class="summary-value" style="color: #ea580c;"><?= number_format($totalLow, 0, ',', '.') ?> <small>Varian</small></div>
            <span class="summary-label">Perlu restock segera</span>
          </a>

          <a href="?view=<?= $view_status ?>&filter=empty" class="summary-card card-danger <?= $filter_type == 'empty' ? 'active-filter' : '' ?>" style="text-decoration: none;">
            <div class="summary-header">
              <span class="summary-title">Stok Habis</span>
              <span class="summary-badge"><?= $totalEmpty ?></span>
            </div>
            <div class="summary-value" style="color: #dc2626;"><?= number_format($totalEmpty, 0, ',', '.') ?> <small>Varian</small></div>
            <span class="summary-label">Stok ketersediaan nol</span>
          </a>
        </div>

        <section class="table-section">
          <!-- SEARCH AREA FULL WIDTH -->
          <div class="search-section-full">
              <label>Pencarian Inventori</label>
              <div class="search-box">
                  <input type="text" id="searchInput" placeholder="Cari berdasarkan nama, warna, ukuran, atau kode..." onkeyup="instantSearch()">
                  <button type="button" disabled>🔍</button>
              </div>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th width="50">No</th>
                  <th>Nama Barang</th>
                  <th>Warna</th>
                  <th>Ukuran</th>
                  <th>Harga Beli</th>
                  <th>Harga Jual</th>
                  <th>Stok</th>
                  <th>Status</th>
                  <th style="text-align: center;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $no = 1;
              $barang_rendah = 0;
              $total_barang = 0;
              $total_modal = 0;

              if ($result->num_rows === 0) {
                echo '<tr class="no-data"><td colspan="9" style="text-align:center;padding:40px;color:#9ca3af;">Tidak ada barang di tab ini.</td></tr>';
              } else {
                while ($row = $result->fetch_assoc()) {
                  if ($row['stok'] <= $row['stok_min']) {
                    $statusTxt = "Rendah"; $class="status-low"; $barang_rendah++;
                  } elseif ($row['stok'] >= $row['stok_max']) {
                    $statusTxt = "Berlebih"; $class="status-high";
                  } else {
                    $statusTxt = "Normal"; $class="status-normal";
                  }
                  $total_barang++;
                  $total_modal += ($row['harga_beli'] * $row['stok']);

                  // Menambahkan data-search agar JS mudah memfilter
                  $searchData = strtolower($row['nama_barang'] . ' ' . $row['warna'] . ' ' . $row['ukuran']);
              ?>
                  <tr class='data-row' data-search='<?= $searchData ?>'>
                    <td style='color:#9ca3af;'><?= $no ?></td>
                    <td style='font-weight:500;'><?= htmlspecialchars($row['nama_barang'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['warna']) ?></td>
                    <td><?= htmlspecialchars($row['ukuran']) ?></td>
                    <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                    <td style='font-weight:600;'><?= $row['stok'] ?></td>
                    <td><span class='badge <?= $class ?>'><?= $statusTxt ?></span></td>
                    <td style='text-align: center;'>
                      <div class='action-buttons-premium'>
                        <?php if ($row['varian_status'] == 'aktif'): ?>
                          <a href='stok_barang.php?view=<?= $view_status ?>&action=status&id=<?= $row['id_varian'] ?>&s=non-aktif' 
                             class='btn-action delete' 
                             onclick="return confirm('Arsipkan barang ini?')"
                             title='Arsipkan'>
                             <span class='icon'>📦</span> Arsip
                          </a>
                        <?php else: ?>
                          <a href='stok_barang.php?view=<?= $view_status ?>&action=status&id=<?= $row['id_varian'] ?>&s=aktif' 
                             class='btn-action edit' 
                           onclick="return confirm('Aktifkan kembali?')"
                           title='Aktifkan Kembali'>
                           <span class='icon'>🔄</span> Balikkan
                          </a>
                        <?php endif; ?>
                        
                        <?php if ((int)$row['total_terjual'] == 0): ?>
                          <a href='edit_barang.php?id=<?= $row['id_varian'] ?>' 
                             class='btn-action edit' 
                             title='Edit Barang'>
                             <span class='icon'>✏️</span> Edit
                          </a>
                          <a href='stok_barang.php?action=hapus&id=<?= $row['id_varian'] ?>' 
                             class='btn-action delete' 
                             onclick="return confirm('Hapus barang ini secara permanen?')"
                             title='Hapus Barang'>
                             <span class='icon'>🗑️</span> Hapus
                          </a>
                        <?php else: ?>
                          <a href='edit_barang.php?id=<?= $row['id_varian'] ?>' 
                             class='btn-action edit' 
                             title='Edit Barang'>
                             <span class='icon'>✏️</span> Edit
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
              <?php
                  $no++;
                }
              }
              ?>
              <tr id="noResults" style="display:none;">
                <td colspan="9" style="text-align:center;padding:40px;color:#9ca3af;">Kata kunci tidak ditemukan.</td>
              </tr>
              </tbody>
            </table>

            <div class="info-bar">
              <p>⚠️ <?= $barang_rendah ?> barang di bawah stok minimum</p>
              <p>Total: <strong><span id="totalVisible"><?= $total_barang ?></span> Varian</strong> | 
                 Estimasi Modal: <strong>Rp <?= number_format($total_modal, 0, ',', '.') ?></strong></p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    function instantSearch() {
      let input = document.getElementById('searchInput').value.toLowerCase();
      let rows = document.querySelectorAll('.data-row');
      let visibleCount = 0;
      let noResults = document.getElementById('noResults');

      rows.forEach(row => {
        let searchContent = row.getAttribute('data-search');
        if (searchContent.includes(input)) {
          row.style.display = "";
          visibleCount++;
        } else {
          row.style.display = "none";
        }
      });

      document.getElementById('totalVisible').innerText = visibleCount;
      noResults.style.display = (visibleCount === 0) ? "" : "none";
    }
  </script>
</body>
</html>
