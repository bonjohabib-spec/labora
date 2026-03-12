<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';

// 1. Data Pengeluaran Terperinci
$stmtDetail = $conn->prepare("SELECT * FROM pengeluaran WHERE DATE(tanggal) BETWEEN ? AND ? ORDER BY tanggal DESC");
$stmtDetail->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtDetail->execute();
$res_detail = $stmtDetail->get_result();

// 2. Analisis: Pengeluaran per Kategori
$stmtCat = $conn->prepare("
    SELECT kategori, COUNT(*) as jumlah_transaksi, SUM(nominal) as total_nominal 
    FROM pengeluaran 
    WHERE DATE(tanggal) BETWEEN ? AND ?
    GROUP BY kategori 
    ORDER BY total_nominal DESC
");
$stmtCat->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtCat->execute();
$res_category = $stmtCat->get_result();

// 3. Hitung Total untuk Persentase
$stmtTotal = $conn->prepare("SELECT SUM(nominal) as grand_total FROM pengeluaran WHERE DATE(tanggal) BETWEEN ? AND ?");
$stmtTotal->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtTotal->execute();
$grand_total = $stmtTotal->get_result()->fetch_assoc()['grand_total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rincian Pengeluaran - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan_pengeluaran.css?v=<?= time() ?>">
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-content">
      <div style="margin-bottom: 20px;">
        <a href="laporan.php" style="text-decoration: none; color: #64748b; font-size: 14px;">← Kembali ke Ringkasan</a>
        <h2 style="margin-top: 10px;">💸 Rincian Laporan Pengeluaran</h2>
      </div>

      <form method="GET" class="filter-bar">
        <div class="filter-group">
          <div class="input-control">
            <label>Dari Tanggal</label>
            <input type="date" name="tanggal_awal" id="tanggal_awal" value="<?= $tanggal_awal ?>">
          </div>
          <div class="input-control">
            <label>Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= $tanggal_akhir ?>">
          </div>
          <div class="input-control">
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
          <button type="submit" class="btn-filter">Tampilkan</button>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 12px; color: #64748b;">Total Pengeluaran Periode Ini</div>
          <div style="font-size: 24px; font-weight: bold; color: #ef4444;">Rp <?= number_format($grand_total, 0, ',', '.') ?></div>
        </div>
      </form>

      <div class="analytics-grid">
        <div class="analytics-card">
          <h4>📊 Alokasi Dana per Kategori</h4>
          <?php if ($grand_total > 0): ?>
            <?php while($cat = mysqli_fetch_assoc($res_category)): 
              $persen = ($cat['total_nominal'] / $grand_total) * 100;
            ?>
            <div class="category-row">
              <div class="category-info">
                <span class="category-name"><?= htmlspecialchars($cat['kategori'] ?: 'Lain-lain') ?></span>
                <span class="category-val">Rp <?= number_format($cat['total_nominal'], 0, ',', '.') ?> (<?= round($persen, 1) ?>%)</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $persen ?>%;"></div>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada data pengeluaran.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-card">
        <h4>📄 Daftar Rincian Pengeluaran</h4>
        <div class="table-container">
          <table class="table-mini">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th style="text-align: right;">Nominal (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = mysqli_fetch_assoc($res_detail)): ?>
              <tr>
                <td style="color: #64748b;"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><span class="badge-kategori"><?= htmlspecialchars($row['kategori'] ?: 'Lain-lain') ?></span></td>
                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                <td style="text-align: right; font-weight: 700; color: #ef4444;">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if (mysqli_num_rows($res_detail) == 0): ?>
              <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;">Tidak ada data pengeluaran ditemukan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

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
