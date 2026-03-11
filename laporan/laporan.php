<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil filter tanggal jika ada
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';

// 1. Total Omzet (total penjualan)
$stmtOmzet = $conn->prepare("
    SELECT SUM(total) AS total_omzet 
    FROM penjualan 
    WHERE DATE(tanggal) BETWEEN ? AND ?
      AND status = 'selesai'
");
$stmtOmzet->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtOmzet->execute();
$total_omzet = $stmtOmzet->get_result()->fetch_assoc()['total_omzet'] ?? 0;

// 2. Total Pengeluaran
$stmtExp = $conn->prepare("
    SELECT SUM(nominal) AS total_pengeluaran 
    FROM pengeluaran 
    WHERE DATE(tanggal) BETWEEN ? AND ?
");
$stmtExp->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtExp->execute();
$total_pengeluaran = $stmtExp->get_result()->fetch_assoc()['total_pengeluaran'] ?? 0;

// Rincian Penjualan
$query_rincian_penjualan = mysqli_query($conn, "
    SELECT id_penjualan, tanggal, pelanggan, total 
    FROM penjualan 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
      AND status = 'selesai'
    ORDER BY tanggal DESC
");

// Rincian Pengeluaran
$query_rincian_pengeluaran = mysqli_query($conn, "
    SELECT tanggal, kategori, deskripsi, nominal 
    FROM pengeluaran 
    WHERE DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    ORDER BY tanggal DESC
");

// Hitung laba bersih
$laba_bersih = $total_omzet - $total_pengeluaran;
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Keuangan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan.css?v=<?= time() ?>">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>
      
      <div class="page-content">
        <div class="welcome-header">
            <h1>📊 Laporan Keuangan</h1>
            <p>Pantau omzet, pengeluaran, dan laba bersih toko Anda.</p>
        </div>

        <form action="" method="GET" class="filter-form">
          <div class="form-group">
            <label>Mulai Tanggal</label>
            <input type="date" name="tanggal_awal" id="tanggal_awal" value="<?= $tanggal_awal ?>">
          </div>
          <div class="form-group">
            <label>Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= $tanggal_akhir ?>">
          </div>
          <div class="form-group">
            <label>Periode</label>
            <select name="periode" id="periodeSelect">
              <option value="hari_ini" <?= $periode=='hari_ini'?'selected':'' ?>>Hari ini</option>
              <option value="kemarin" <?= $periode=='kemarin'?'selected':'' ?>>Kemarin</option>
              <option value="pekan_ini" <?= $periode=='pekan_ini'?'selected':'' ?>>Pekan ini</option>
              <option value="pekan_lalu" <?= $periode=='pekan_lalu'?'selected':'' ?>>Pekan lalu</option>
              <option value="bulan_ini" <?= $periode=='bulan_ini'?'selected':'' ?>>Bulan ini</option>
              <option value="bulan_lalu" <?= $periode=='bulan_lalu'?'selected':'' ?>>Bulan lalu</option>
              <option value="tahun_ini" <?= $periode=='tahun_ini'?'selected':'' ?>>Tahun ini</option>
              <option value="kustom" <?= $periode=='kustom'?'selected':'' ?>>Kustom</option>
            </select>
          </div>
          <button type="submit">Filter Laporan</button>
        </form>

        <div class="stat-cards">
          <div class="stat-card">
            <div class="stat-icon purple">💰</div>
            <div class="stat-info">
              <label>Total Omzet</label>
              <h3>Rp<?= number_format($total_omzet, 0, ',', '.') ?></h3>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon danger">💸</div>
            <div class="stat-info">
              <label>Total Pengeluaran</label>
              <h3 style="color: #ef4444;">Rp<?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
            </div>
          </div>

          <div class="stat-card laba">
            <div class="stat-icon emerald">📈</div>
            <div class="stat-info">
              <label>Laba Bersih</label>
              <h3 style="color: #059669;">Rp<?= number_format($laba_bersih, 0, ',', '.') ?></h3>
            </div>
          </div>
        </div>

        <div class="report-grid">
          <a href="laporan_penjualan.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="report-card">
            <h3>🛒 Laporan Penjualan Detail</h3>
            <p>Lihat rincian transaksi per invoice, analisis pelanggan terbanyak, dan riset barang terlaris untuk periode ini.</p>
            <div class="btn-go">Buka Rincian →</div>
          </a>

          <a href="laporan_pengeluaran.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="report-card">
            <h3>💸 Laporan Pengeluaran Detail</h3>
            <p>Lihat rincian biaya operasional, gaji, dan stok. Analisis alokasi dana berdasarkan kategori pengeluaran.</p>
            <div class="btn-go">Buka Rincian →</div>
          </a>
        </div>

      </div> <!-- .page-content -->
    </div> <!-- .main-content -->
  </div> <!-- .container -->

<script>
const periodeSelect = document.getElementById('periodeSelect');
const tglAwal = document.getElementById('tanggal_awal');
const tglAkhir = document.getElementById('tanggal_akhir');

periodeSelect.addEventListener('change', function() {
    const period = this.value;
    const now = new Date();
    let start, end;

    switch(period) {
        case 'hari_ini':
            start = end = now;
            break;
        case 'kemarin':
            const kemarian = new Date();
            kemarian.setDate(now.getDate() - 1);
            start = end = kemarian;
            break;
        case 'pekan_ini':
            const firstDay = now.getDate() - now.getDay();
            start = new Date(now.setDate(firstDay));
            end = new Date(now.setDate(firstDay + 6));
            break;
        case 'pekan_lalu':
            const lastWeek = new Date();
            lastWeek.setDate(now.getDate() - 7);
            const firstDayLalu = lastWeek.getDate() - lastWeek.getDay();
            start = new Date(lastWeek.setDate(firstDayLalu));
            end = new Date(lastWeek.setDate(firstDayLalu + 6));
            break;
        case 'bulan_ini':
            start = new Date(now.getFullYear(), now.getMonth(), 1);
            end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            break;
        case 'bulan_lalu':
            start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            end = new Date(now.getFullYear(), now.getMonth(), 0);
            break;
        case 'tahun_ini':
            start = new Date(now.getFullYear(), 0, 1);
            end = new Date(now.getFullYear(), 11, 31);
            break;
        default:
            return;
    }

    tglAwal.value = formatDate(start);
    tglAkhir.value = formatDate(end);
});

function formatDate(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
}

[tglAwal, tglAkhir].forEach(el => {
    el.addEventListener('change', () => {
        periodeSelect.value = 'kustom';
    });
});
</script>
</body>
</html>
