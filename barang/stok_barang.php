<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'owner1';
    $_SESSION['user_role'] = 'owner';
}

// Gabungkan barang dan varian
$query = "
  SELECT b.nama_barang, v.id_varian, v.warna, v.ukuran, 
         v.harga_beli, v.harga_jual, v.stok,
         b.stok_min, b.stok_max
  FROM barang b
  JOIN barang_varian v ON b.id_barang = v.id_barang
  ORDER BY b.nama_barang ASC
";

$result = $conn->query($query);
if ($result === false) die('Query error: ' . $conn->error);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Stok Barang - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/stok_barang.css">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <div class="page-content">
        <section class="table-section">
          <div class="page-header">
            <h1>Manajemen Stok Barang</h1>
            <div class="actions">
              <button class="btn-primary" onclick="window.location.href='tambah_barang.php'">+ Tambah Barang Baru</button>
              <button class="btn-outline">🔁 Sinkronisasi Stok</button>
            </div>
          </div>

          <div class="table-wrapper">
            <table>
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
                </tr>
              </thead>
              <tbody>
              <?php
              $no = 1;
              $barang_rendah = 0;
              $total_barang = 0;
              $total_modal = 0;

              if ($result->num_rows === 0) {
                echo '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7280;">Belum ada data barang.</td></tr>';
              } else {
                while ($row = $result->fetch_assoc()) {
                  if ($row['stok'] <= $row['stok_min']) {
                    $status = "Stok Rendah"; $class="status-low"; $barang_rendah++;
                  } elseif ($row['stok'] >= $row['stok_max']) {
                    $status = "Berlebih"; $class="status-high";
                  } else {
                    $status = "Normal"; $class="status-normal";
                  }
                  $total_barang++;
                  $total_modal += ($row['harga_beli'] * $row['stok']);

                  echo "<tr>
                          <td>{$no}</td>
                          <td>" . htmlspecialchars($row['nama_barang']) . "</td>
                          <td>" . htmlspecialchars($row['warna']) . "</td>
                          <td>" . htmlspecialchars($row['ukuran']) . "</td>
                          <td>Rp " . number_format($row['harga_beli'],0,',','.') . "</td>
                          <td>Rp " . number_format($row['harga_jual'],0,',','.') . "</td>
                          <td>{$row['stok']}</td>
                          <td class='{$class}'>{$status}</td>
                        </tr>";
                  $no++;
                }
              }
              ?>
              </tbody>
            </table>

            <div class="info-bar">
              <p>⚠️ <?= $barang_rendah ?> barang di bawah stok minimum</p>
              <p>Jumlah Varian: <?= $total_barang ?> |
                 Total Modal: Rp <?= number_format($total_modal,0,',','.') ?></p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</body>
</html>
