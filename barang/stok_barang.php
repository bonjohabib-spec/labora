<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Proteksi role hanya untuk owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: ../penjualan/penjualan.php");
    exit();
}

if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'owner1';
    $_SESSION['user_role'] = 'owner';
}

// 1. LOGIKA PERUBAHAN STATUS (Non-aktifkan / Aktifkan)
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id'])) {
    $id_varian = intval($_GET['id']);
    $new_status = ($_GET['s'] == 'non-aktif') ? 'non-aktif' : 'aktif';
    
    $stmt = $conn->prepare("UPDATE barang_varian SET status = ? WHERE id_varian = ?");
    $stmt->bind_param("si", $new_status, $id_varian);
    
    if ($stmt->execute()) {
        $msg = ($new_status == 'non-aktif') ? "Barang berhasil diarsipkan." : "Barang berhasil diaktifkan kembali.";
        echo "<script>alert('$msg'); window.location='stok_barang.php';</script>";
        exit;
    }
}

// 2. FILTER TAB (Default: aktif)
$view_status = isset($_GET['view']) && $_GET['view'] == 'non-aktif' ? 'non-aktif' : 'aktif';

// Gabungkan barang dan varian dengan filter status
$sql = "
  SELECT b.nama_barang, v.id_varian, v.warna, v.ukuran, 
         v.harga_beli, v.harga_jual, v.stok, v.status as varian_status,
         b.stok_min, b.stok_max
  FROM barang b
  JOIN barang_varian v ON b.id_barang = v.id_barang
  WHERE v.status = '$view_status'
  ORDER BY b.nama_barang ASC
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
        <section class="table-section">
          
          <!-- Header Area -->
          <div class="page-header-redesign">
            <div class="header-left">
              <h1>Manajemen Stok</h1>
              <div class="tab-navigation">
                <a href="stok_barang.php" class="tab-item <?= $view_status == 'aktif' ? 'active' : '' ?>">
                  Stok Aktif
                </a>
                <a href="stok_barang.php?view=non-aktif" class="tab-item <?= $view_status == 'non-aktif' ? 'active' : '' ?>">
                  Arsip (Non-aktif)
                </a>
              </div>
            </div>
            
            <div class="header-right">
              <div class="search-box">
                <input type="text" id="searchInput" placeholder="Cari barang (Nama/Warna/Ukuran)..." onkeyup="instantSearch()">
                <button type="button" disabled>🔍</button>
              </div>
              <button class="btn-primary" onclick="window.location.href='tambah_barang.php'">+ Tambah Barang</button>
            </div>
          </div>

          <div class="table-wrapper">
            <table id="stokTable">
              <thead>
                <tr>
                  <th>No</th>
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

                  echo "<tr class='data-row' data-search='{$searchData}'>
                          <td style='color:#9ca3af;'>{$no}</td>
                          <td style='font-weight:500;'>" . htmlspecialchars($row['nama_barang']) . "</td>
                          <td>" . htmlspecialchars($row['warna']) . "</td>
                          <td>" . htmlspecialchars($row['ukuran']) . "</td>
                          <td>Rp " . number_format($row['harga_beli'],0,',','.') . "</td>
                          <td>Rp " . number_format($row['harga_jual'],0,',','.') . "</td>
                          <td style='font-weight:600;'>{$row['stok']}</td>
                          <td><span class='badge {$class}'>{$statusTxt}</span></td>
                          <td style='text-align: center;'>";
                  
                  if ($row['varian_status'] == 'aktif') {
                    echo "<a href='stok_barang.php?action=status&id={$row['id_varian']}&s=non-aktif' 
                             class='btn-action disable' 
                             onclick=\"return confirm('Arsipkan barang ini?')\"
                             title='Arsipkan'>📦</a>";
                  } else {
                    echo "<a href='stok_barang.php?action=status&id={$row['id_varian']}&s=aktif' 
                             class='btn-action enable' 
                             onclick=\"return confirm('Aktifkan kembali?')\"
                             title='Aktifkan Kembali'>🔄</a>";
                  }
                  
                  echo "</td>
                        </tr>";
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
                 Modal: <strong>Rp <?= number_format($total_modal,0,',','.') ?></strong></p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    function instantSearch() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('stokTable');
      const rows = table.getElementsByClassName('data-row');
      const noResults = document.getElementById('noResults');
      const totalVisibleSpan = document.getElementById('totalVisible');
      
      let visibleCount = 0;
      let matchFound = false;

      for (let i = 0; i < rows.length; i++) {
        const searchData = rows[i].getAttribute('data-search');
        if (searchData.includes(filter)) {
          rows[i].style.display = "";
          visibleCount++;
          matchFound = true;
        } else {
          rows[i].style.display = "none";
        }
      }

      // Tampilkan pesan "Tidak ditemukan" jika pencarian gagal
      if (filter !== "" && !matchFound) {
        noResults.style.display = "";
      } else {
        noResults.style.display = "none";
      }

      // Update jumlah total yang terlihat
      totalVisibleSpan.innerText = visibleCount;
    }
  </script>
</body>
</html>
