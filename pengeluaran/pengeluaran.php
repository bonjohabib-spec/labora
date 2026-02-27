<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Cek login dan role
if (!isset($_SESSION['user_role'])) {
    header("Location: auth/login.php");
    exit();
}

// Ambil filter tanggal (default hari ini)
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil data pengeluaran dari database
$query = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE DATE(tanggal) = '$tanggal' ORDER BY tanggal DESC");

// Total pengeluaran
$totalQuery = mysqli_query($conn, "SELECT SUM(nominal) AS total FROM pengeluaran WHERE DATE(tanggal) = '$tanggal'");
$total = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengeluaran Harian</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/pengeluaran.css">
</head>
<body>
   <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <div class="page-content">
        <section class="table-section">
          <div class="page-header">
                <h1>📊 Pengeluaran Harian</h1>

                <form method="GET" class="filter-form">
                    <label for="tanggal">Pilih tanggal:</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
                    <button type="submit">Tampilkan</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td><?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data">Tidak ada data pengeluaran untuk tanggal ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="total">
                    Total Pengeluaran: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong>
                </div>

                <div class="opsi-tampilan">
                    <a href="pengeluaran_bulanan.php">📅 Lihat Bulanan</a>
                    <a href="pengeluaran_tahunan.php">📆 Lihat Tahunan</a>
                </div>
            </div>
        </section>
      </div>
    </div>
   </div>
</body>
</html>
