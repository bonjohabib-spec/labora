<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login dan role - disamakan dengan dashboard
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Query data pengeluaran
$query = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE DATE(tanggal) = '$tanggal' ORDER BY tanggal DESC");

$totalQuery = mysqli_query($conn, "SELECT SUM(nominal) AS total FROM pengeluaran WHERE DATE(tanggal) = '$tanggal'");
$total = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengeluaran - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/pengeluaran.css?v=<?= time() ?>">
</head>
<body>

<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <h2>Pengeluaran Harian</h2>
      <a href="tambah_pengeluaran.php" class="btn-add-expense">+ Tambah Pengeluaran</a>
    </div>

    <div class="page-content">
      <div class="pengeluaran-filter">
        <form method="GET" class="pengeluaran-filter-form">
          <label>Pilih Tanggal:</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
          <button type="submit">Filter</button>
        </form>
      </div>

      <div class="pengeluaran-card">
        <table class="pengeluaran-table">
          <thead>
            <tr>
              <th style="width: 50px; text-align: center;">No</th>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Keterangan</th>
              <th style="text-align: right;">Jumlah (Rp)</th>
              <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($query && mysqli_num_rows($query) > 0): ?>
              <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                  <td style="text-align: center;"><?= $no++ ?></td>
                  <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                  <td><span class="badge-kategori"><?= htmlspecialchars($row['kategori'] ?: 'Lain-lain') ?></span></td>
                  <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                  <td style="text-align: right; font-weight: 600;">
                    Rp <?= number_format($row['nominal'], 0, ',', '.') ?>
                  </td>
                  <td style="text-align: center;">
                    <a href="hapus_pengeluaran.php?id=<?= $row['id_pengeluaran'] ?>" class="btn-delete" onclick="return confirm('Hapus pengeluaran ini?')">🗑️</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="pengeluaran-no-data">Tidak ada pengeluaran pada tanggal ini.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pengeluaran-summary">
          Total Pengeluaran: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
