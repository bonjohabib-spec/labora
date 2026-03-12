<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Proteksi: Hanya Owner yang bisa audit
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';
$filter_kasir = isset($_GET['kasir']) ? $_GET['kasir'] : '';

// Paginasi
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$where = "DATE(waktu_buka) BETWEEN ? AND ?";
if ($filter_kasir !== '') {
    $where .= " AND kasir = '" . $conn->real_escape_string($filter_kasir) . "'";
}

// Count total
$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM kas_shift WHERE $where");
$stmtCount->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtCount->execute();
$total_data = $stmtCount->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Fetch data
$stmt = $conn->prepare("SELECT * FROM kas_shift WHERE $where ORDER BY waktu_buka DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ssii", $tanggal_awal, $tanggal_akhir, $per_page, $offset);
$stmt->execute();
$res = $stmt->get_result();

// Ambil list kasir dari tabel users agar hanya akun yang aktif yang muncul
$qUsers = $conn->query("SELECT username as kasir FROM users ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Audit Riwayat Shift - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/laporan_penjualan.css?v=<?= time() ?>"> <!-- Re-use styling -->
  <style>
    .status-badge.closed { background: #f1f5f9; color: #64748b; }
    .status-badge.open { background: #f0fdf4; color: #16a34a; }
    .diff-neg { color: #ef4444; font-weight: 700; }
    .diff-pos { color: #10b981; font-weight: 700; }
    .diff-zero { color: #94a3b8; }
  </style>
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-content">
      <div style="margin-bottom: 20px;">
        <a href="laporan.php" style="text-decoration: none; color: #64748b; font-size: 14px;">← Kembali ke Ringkasan</a>
        <h2 style="margin-top: 10px;">📋 Laporan Riwayat Shift (Audit Kasir)</h2>
      </div>

      <form method="GET" class="filter-bar" id="filterForm">
        <div class="filter-group">
          <div class="input-control">
            <label>Periode</label>
            <select name="periode" id="selectPeriode">
              <option value="hari_ini" <?= $periode == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
              <option value="kemarin" <?= $periode == 'kemarin' ? 'selected' : '' ?>>Kemarin</option>
              <option value="pekan_ini" <?= $periode == 'pekan_ini' ? 'selected' : '' ?>>Pekan Ini</option>
              <option value="pekan_lalu" <?= $periode == 'pekan_lalu' ? 'selected' : '' ?>>Pekan Lalu</option>
              <option value="bulan_ini" <?= $periode == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
              <option value="bulan_lalu" <?= $periode == 'bulan_lalu' ? 'selected' : '' ?>>Bulan Lalu</option>
              <option value="tahun_ini" <?= $periode == 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
              <option value="tahun_lalu" <?= $periode == 'tahun_lalu' ? 'selected' : '' ?>>Tahun Lalu</option>
              <option value="custom" <?= $periode == 'custom' ? 'selected' : '' ?>>Custom Tanggal</option>
            </select>
          </div>
          <div class="input-control">
            <label>Dari Tanggal</label>
            <input type="date" name="tanggal_awal" id="tglAwal" value="<?= $tanggal_awal ?>">
          </div>
          <div class="input-control">
            <label>Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" id="tglAkhir" value="<?= $tanggal_akhir ?>">
          </div>
          <div class="input-control">
            <label>Kasir</label>
            <select name="kasir">
              <option value="">Semua Kasir</option>
              <?php while($u = $qUsers->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($u['kasir']) ?>" <?= $filter_kasir == $u['kasir'] ? 'selected' : '' ?>><?= htmlspecialchars($u['kasir']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <button type="submit" class="btn-filter">Audit</button>
        </div>
      </form>

      <div class="detail-card" style="margin-top: 20px;">
        <div class="table-container">
          <table class="table-mini">
            <thead>
              <tr>
                <th>Kasir</th>
                <th>Waktu Shift</th>
                <th style="text-align: right;">Modal Awal</th>
                <th style="text-align: right;">Sistem (TUNAI)</th>
                <th style="text-align: right;">Sistem (TRANSFER)</th>
                <th style="text-align: right;">Laci Seharusnya</th>
                <th style="text-align: right;">Fisik (Input)</th>
                <th style="text-align: right;">Selisih</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while($s = $res->fetch_assoc()): 
                $selisih = $s['selisih'] ?? 0;
                // Uang tunai sistem = Omset Tunai + Pelunasan Tunai
                $total_tunai_sistem = ($s['omset_tunai'] ?? 0) + ($s['piutang_tunai'] ?? 0);
                // Uang transfer sistem = Omset Transfer + Pelunasan Transfer
                $total_transfer_sistem = ($s['omset_transfer'] ?? 0) + ($s['piutang_transfer'] ?? 0);
                // Ekspektasi Laci = Modal + Total Tunai Sistem
                $ekspektasi_laci = $s['saldo_awal'] + $total_tunai_sistem;
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['kasir']) ?></strong></td>
                <td>
                  <div style="font-size: 11px; color: #64748b;">Mulai: <?= date('d/m/y H:i', strtotime($s['waktu_buka'])) ?></div>
                  <div style="font-size: 11px; color: #64748b;">Tutup: <?= $s['waktu_tutup'] ? date('d/m/y H:i', strtotime($s['waktu_tutup'])) : '-' ?></div>
                </td>
                <td style="text-align: right;">Rp <?= number_format($s['saldo_awal'], 0, ',', '.') ?></td>
                <td style="text-align: right; color: #475569;">Rp <?= number_format($total_tunai_sistem, 0, ',', '.') ?></td>
                <td style="text-align: right; color: #0891b2;">Rp <?= number_format($total_transfer_sistem, 0, ',', '.') ?></td>
                <td style="text-align: right; font-weight: 700; background: #fdfcf4;">Rp <?= number_format($ekspektasi_laci, 0, ',', '.') ?></td>
                <td style="text-align: right; font-weight: 500;"><?= $s['waktu_tutup'] ? 'Rp '.number_format($s['saldo_akhir_fisik'], 0, ',', '.') : '<span style="color:#94a3b8 italic">masih buka</span>' ?></td>
                <td style="text-align: right;">
                    <?php if (!$s['waktu_tutup']): ?>
                        -
                    <?php elseif ($selisih < 0): ?>
                        <span class="diff-neg">Rp <?= number_format($selisih, 0, ',', '.') ?></span>
                    <?php elseif ($selisih > 0): ?>
                        <span class="diff-pos">+Rp <?= number_format($selisih, 0, ',', '.') ?></span>
                    <?php else: ?>
                        <span class="diff-zero">Pas (Rp 0)</span>
                    <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge <?= $s['status'] ?>">
                    <?= $s['status'] == 'open' ? '🟢 Aktif' : '⚪ Tutup' ?>
                  </span>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($res->num_rows == 0): ?>
              <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px;">Belum ada riwayat shift untuk periode ini.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <div class="page-nav">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
              <a href="?periode=<?= $periode ?>&tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>&kasir=<?= $filter_kasir ?>&page=<?= $i ?>" class="page-btn <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
const selectPeriode = document.getElementById('selectPeriode');
const tglAwal = document.getElementById('tglAwal');
const tglAkhir = document.getElementById('tglAkhir');
const filterForm = document.getElementById('filterForm');

selectPeriode.addEventListener('change', function() {
    const now = new Date();
    let start, end;

    switch (this.value) {
        case 'hari_ini':
            start = end = formatDate(now);
            break;
        case 'kemarin':
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            start = end = formatDate(yesterday);
            break;
        case 'pekan_ini':
            const firstDayOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
            const lastDayOfWeek = new Date(now.setDate(now.getDate() - now.getDay() + 6));
            start = formatDate(firstDayOfWeek);
            end = formatDate(lastDayOfWeek);
            break;
        case 'pekan_lalu':
            const firstDayLastWeek = new Date(now.setDate(now.getDate() - now.getDay() - 7));
            const lastDayLastWeek = new Date(now.setDate(now.getDate() - now.getDay() + 6));
            start = formatDate(firstDayLastWeek);
            end = formatDate(lastDayLastWeek);
            break;
        case 'bulan_ini':
            start = formatDate(new Date(now.getFullYear(), now.getMonth(), 1));
            end = formatDate(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            break;
        case 'bulan_lalu':
            start = formatDate(new Date(now.getFullYear(), now.getMonth() - 1, 1));
            end = formatDate(new Date(now.getFullYear(), now.getMonth(), 0));
            break;
        case 'tahun_ini':
            start = formatDate(new Date(now.getFullYear(), 0, 1));
            end = formatDate(new Date(now.getFullYear(), 11, 31));
            break;
        case 'tahun_lalu':
            start = formatDate(new Date(now.getFullYear() - 1, 0, 1));
            end = formatDate(new Date(now.getFullYear() - 1, 11, 31));
            break;
        case 'custom':
            return;
    }

    if (start && end) {
        tglAwal.value = start;
        tglAkhir.value = end;
        filterForm.submit();
    }
});

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}
</script>
</body>
</html>
