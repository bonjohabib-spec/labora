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
$filter_kasir = isset($_GET['kasir']) ? $_GET['kasir'] : '';

// Ambil daftar kair untuk dropdown
$qUsers = $conn->query("SELECT username FROM users ORDER BY username ASC");
$users = [];
while($u = $qUsers->fetch_assoc()) $users[] = $u['username'];

// Paginasi
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$where_kasir = "";
if ($filter_kasir !== "") {
    $where_kasir = " AND kasir = '" . $conn->real_escape_string($filter_kasir) . "'";
}

$query_count = "SELECT COUNT(*) as total FROM penjualan WHERE status='selesai' AND DATE(tanggal) BETWEEN ? AND ?" . $where_kasir;
$params = [$tanggal_awal, $tanggal_akhir];
$types = "ss";

$stmtCount = $conn->prepare($query_count);
$stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total_data = $stmtCount->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// 1. Ringkasan Laporan (Kartu Utama) - Konsolidasi
$stmtSum = $conn->prepare("
    SELECT SUM(total) as total_omset, SUM(sisa_piutang) as total_piutang
    FROM penjualan 
    WHERE status='selesai' AND DATE(tanggal) BETWEEN ? AND ?" . $where_kasir);
$stmtSum->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtSum->execute();
$summ = $stmtSum->get_result()->fetch_assoc();
$total_omset = $summ['total_omset'] ?? 0;
$total_piutang = $summ['sisa_piutang'] ?? 0; // Corrected from $summ['total_piutang']

// Kas Masuk = (Total Omset - Sisa Piutang) - (Cicilan untuk nota periode ini) + (Semua Cicilan periode ini)
// Untuk Cicilan Piutang, kita perlu memfilter berdasarkan kasir yang menerima uang (id_shift -> kasir)
$where_kasir_piutang = "";
if ($filter_kasir !== "") {
    $where_kasir_piutang = " AND id_shift IN (SELECT id_shift FROM kas_shift WHERE kasir = '" . $conn->real_escape_string($filter_kasir) . "')";
}

$stmtPay = $conn->prepare("SELECT SUM(nominal) as total FROM pembayaran_piutang WHERE DATE(tanggal) BETWEEN ? AND ?" . $where_kasir_piutang);
$stmtPay->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtPay->execute();
$kas_cicilan = $stmtPay->get_result()->fetch_assoc()['total'] ?? 0;

$stmtNotaBaru = $conn->prepare("
    SELECT SUM(nominal) FROM pembayaran_piutang 
    WHERE DATE(tanggal) BETWEEN ? AND ? 
    AND id_penjualan IN (SELECT id_penjualan FROM penjualan WHERE DATE(tanggal) BETWEEN ? AND ?" . $where_kasir . ")
    " . $where_kasir_piutang);
$stmtNotaBaru->bind_param("ssss", $tanggal_awal, $tanggal_akhir, $tanggal_awal, $tanggal_akhir);
$stmtNotaBaru->execute();
$cicilan_nota_baru = $stmtNotaBaru->get_result()->fetch_row()[0] ?? 0;

$total_kas_masuk = ($total_omset - $total_piutang) - $cicilan_nota_baru + $kas_cicilan;

// 2. Data Penjualan Terperinci
$query_detail = "SELECT * FROM penjualan 
                 WHERE status='selesai' 
                 AND DATE(tanggal) BETWEEN ? AND ?" . $where_kasir . "
                 ORDER BY tanggal DESC LIMIT ? OFFSET ?";
$stmtDetail = $conn->prepare($query_detail);
$stmtDetail->bind_param("ssii", $tanggal_awal, $tanggal_akhir, $per_page, $offset);
$stmtDetail->execute();
$res_detail = $stmtDetail->get_result();

// 2. Riset: Pelanggan Terbanyak (berdasarkan total belanja)
$query_top_customers = "SELECT pelanggan, COUNT(*) as transaksi, SUM(total) as total_belanja 
                        FROM penjualan 
                        WHERE status='selesai' 
                        AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'" . $where_kasir . "
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
                     AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'" . ($filter_kasir !== "" ? " AND p.kasir = '" . $conn->real_escape_string($filter_kasir) . "'" : "");
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
                    AND DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'" . ($filter_kasir !== "" ? " AND p.kasir = '" . $conn->real_escape_string($filter_kasir) . "'" : "") . "
                    GROUP BY dp.id_varian
                    ORDER BY total_qty DESC LIMIT $item_per_page OFFSET $item_offset";
$res_items = mysqli_query($conn, $query_top_items);

// 4. Perincian Kas Masuk (Untuk Modal)
// A. Kas dari Penjualan Baru (Cash/DP)
$stmtKasPenjualan = $conn->prepare("
    SELECT id_penjualan, tanggal, pelanggan, (total - sisa_piutang) as cash_dp 
    FROM penjualan 
    WHERE status='selesai' 
    AND DATE(tanggal) BETWEEN ? AND ? 
    AND (total - sisa_piutang) > 0" . $where_kasir);
$stmtKasPenjualan->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtKasPenjualan->execute();
$res_kas_penjualan = $stmtKasPenjualan->get_result();

// B. Kas dari Pembayaran Piutang (Cicilan)
$stmtKasCicilan = $conn->prepare("
    SELECT pp.id_penjualan, pp.tanggal, pp.nominal, p.pelanggan 
    FROM pembayaran_piutang pp
    JOIN penjualan p ON pp.id_penjualan = p.id_penjualan
    WHERE DATE(pp.tanggal) BETWEEN ? AND ?" . str_replace(" id_shift", " pp.id_shift", $where_kasir_piutang));
$stmtKasCicilan->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmtKasCicilan->execute();
$res_kas_cicilan = $stmtKasCicilan->get_result();
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
          <div class="input-control">
            <label>Kasir</label>
            <select name="kasir">
              <option value="">Semua Kasir</option>
              <?php foreach($users as $u): ?>
                <option value="<?= htmlspecialchars($u) ?>" <?= $filter_kasir == $u ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
              <?php endforeach; ?>
            </select>
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
        <div class="stat-card" onclick="openModalKas()" style="cursor: pointer;" title="Klik untuk rincian">
          <div class="stat-icon" style="background: #f0fdf4; color: #22c55e;">💵</div>
          <div class="stat-info">
            <span class="stat-label">Total Kas Masuk</span>
            <span class="stat-value">Rp <?= number_format($total_kas_masuk, 0, ',', '.') ?></span>
            <span class="stat-desc">Tunai + DP + Cicilan ⓘ</span>
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
              $item_base_url = "?tanggal_awal=$tanggal_awal&tanggal_akhir=$tanggal_akhir&periode=$periode&kasir=$filter_kasir&page=$page&item_page=";
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
            $base_url = "?tanggal_awal=$tanggal_awal&tanggal_akhir=$tanggal_akhir&periode=$periode&kasir=$filter_kasir&item_page=$item_page&page=";
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
<!-- MODAL PERINCIAN KAS MASUK -->
<div id="modalKas" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <h3>🔍 Perincian Kas Masuk</h3>
      <button class="btn-close" onclick="closeModalKas()">×</button>
    </div>
    <div class="modal-body">
      <div class="perincian-section">
        <label>📦 Dari Penjualan Baru (Cash/DP)</label>
        <table class="table-mini">
          <thead>
            <tr>
              <th>Nota</th>
              <th>Pelanggan</th>
              <th style="text-align: right;">Nominal</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $sub_penjualan = 0;
            while($kp = $res_kas_penjualan->fetch_assoc()): 
              $sub_penjualan += $kp['cash_dp'];
            ?>
            <tr>
              <td>#INV-<?= $kp['id_penjualan'] ?></td>
              <td><?= htmlspecialchars($kp['pelanggan'] ?: '-') ?></td>
              <td style="text-align: right;">Rp <?= number_format($kp['cash_dp'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background: #f8fafc; font-weight: bold;">
              <td colspan="2">Subtotal Penjualan Baru</td>
              <td style="text-align: right;">Rp <?= number_format($sub_penjualan, 0, ',', '.') ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="perincian-section" style="margin-top: 20px;">
        <label>💳 Dari Pembayaran Cicilan (Piutang)</label>
        <table class="table-mini">
          <thead>
            <tr>
              <th>Nota</th>
              <th>Pelanggan</th>
              <th style="text-align: right;">Nominal</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $sub_cicilan = 0;
            while($kc = $res_kas_cicilan->fetch_assoc()): 
              $sub_cicilan += $kc['nominal'];
            ?>
            <tr>
              <td>#INV-<?= $kc['id_penjualan'] ?></td>
              <td><?= htmlspecialchars($kc['pelanggan'] ?: '-') ?></td>
              <td style="text-align: right;">Rp <?= number_format($kc['nominal'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background: #f8fafc; font-weight: bold;">
              <td colspan="2">Subtotal Cicilan Masuk</td>
              <td style="text-align: right;">Rp <?= number_format($sub_cicilan, 0, ',', '.') ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="total-summary-modal">
        <span>Grand Total Kas Masuk:</span>
        <strong>Rp <?= number_format($sub_penjualan + $sub_cicilan, 0, ',', '.') ?></strong>
      </div>
    </div>
  </div>
</div>

<script>
function openModalKas() {
  document.getElementById('modalKas').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeModalKas() {
  document.getElementById('modalKas').classList.remove('active');
  document.body.style.overflow = 'auto';
}

// LOGIKA FILTER PERIODE
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

// Close on click outside
window.onclick = function(event) {
  let modal = document.getElementById('modalKas');
  if (event.target == modal) {
    closeModalKas();
  }
}
</script>

</body>
</html>
