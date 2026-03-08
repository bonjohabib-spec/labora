<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php'; 
include __DIR__ . '/../includes/auth_shift.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = 'owner1';
  $_SESSION['user_role'] = 'owner';
}

$kasir = $_SESSION['username'];
$active_shift = checkShift($conn, $kasir); // Wajib buka kasir dulu
$id_shift = $active_shift['id_shift'];

// ==========================
// 🗑️ HAPUS TRANSAKSI 
// ==========================
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    
    // 🔍 Cek status transaksi dulu sebelum hapus
    $qStatus = $conn->prepare("SELECT status FROM penjualan WHERE id_penjualan = ?");
    $qStatus->bind_param("i", $id_hapus);
    $qStatus->execute();
    $transaksi = $qStatus->get_result()->fetch_assoc();
    
    $conn->begin_transaction();

    try {
        // 🔹 HANYA KEMBALIKAN STOK JIKA STATUSNYA 'selesai'
        // Karena status 'aktif' (draft) sekarang belum mengurangi stok fisik.
        if ($transaksi && $transaksi['status'] === 'selesai') {
            $det = $conn->prepare("SELECT id_varian, qty FROM detail_penjualan WHERE id_penjualan = ?");
            $det->bind_param("i", $id_hapus);
            $det->execute();
            $res = $det->get_result();

            while ($row = $res->fetch_assoc()) {
                $id_varian = $row['id_varian'];
                $qty = $row['qty'];
                
                $getBarang = $conn->prepare("SELECT id_barang FROM barang_varian WHERE id_varian = ?");
                $getBarang->bind_param("i", $id_varian);
                $getBarang->execute();
                $id_barang = $getBarang->get_result()->fetch_assoc()['id_barang'] ?? null;

                if ($id_varian) {
                    $stmt = $conn->prepare("UPDATE barang_varian SET stok = stok + ? WHERE id_varian = ?");
                    $stmt->bind_param("ii", $qty, $id_varian);
                    $stmt->execute();
                }

                if ($id_barang) {
                    $keterangan = "Pembatalan/Hapus Transaksi Selesai #$id_hapus, stok dikembalikan";
                    catat_riwayat_stok($conn, $id_barang, $id_varian, $qty, 'penambahan', $keterangan);
                }
            }
        }

        // 🔹 Hapus data (baik draft maupun selesai)
        $conn->query("DELETE FROM detail_penjualan WHERE id_penjualan = $id_hapus");
        $conn->query("DELETE FROM penjualan WHERE id_penjualan = $id_hapus");
        $conn->commit();

        echo "<script>alert('✅ Transaksi berhasil dihapus.'); window.location='penjualan.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Gagal hapus: " . $e->getMessage());
    }
}

// ==========================
// ➕ BUAT TRANSAKSI BARU
// ==========================
if (isset($_POST['buat_transaksi']) || (isset($_GET['action']) && $_GET['action'] == 'baru')) {
  $pelanggan = isset($_POST['pelanggan']) ? trim($_POST['pelanggan']) : '';
  $id_shift = $active_shift['id_shift'];
  $stmt = $conn->prepare("INSERT INTO penjualan (tanggal, kasir, pelanggan, status, total, keuntungan, id_shift)
                          VALUES (NOW(), ?, ?, 'aktif', 0, 0, ?)");
  $stmt->bind_param("ssi", $kasir, $pelanggan, $id_shift);
  $stmt->execute();
  $id_baru = $conn->insert_id;
  header("Location: penjualan_tambah.php?id=$id_baru");
  exit;
}

// ==========================
// 📜 AMBIL DATA & BERSIHKAN DRAFT
// ==========================
// Hapus transaksi yang tidak diselesaikan oleh kasir saat ini (kalau belum selesai dianggap tidak ada)
$conn->query("DELETE FROM detail_penjualan WHERE id_penjualan IN (SELECT id_penjualan FROM penjualan WHERE status = 'aktif' AND kasir = '$kasir')");
$conn->query("DELETE FROM penjualan WHERE status = 'aktif' AND kasir = '$kasir'");

// Paginasi & Filter
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$filter = $_GET['filter'] ?? 'all';
$todayStr = date('Y-m-d');
$where_clause = "status IN ('selesai', 'batal')";

if ($filter == 'unpaid') {
    $where_clause .= " AND sisa_piutang > 0";
} elseif ($filter == 'overdue') {
    $where_clause .= " AND sisa_piutang > 0 AND jatuh_tempo < '$todayStr'";
}

$q_total = $conn->query("SELECT COUNT(*) AS total FROM penjualan WHERE $where_clause");
$total_rows = $q_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$q_riwayat = $conn->query("SELECT p.*, 
    (SELECT GROUP_CONCAT(DISTINCT metode) 
     FROM (
        SELECT id_penjualan, metode_pembayaran as metode FROM penjualan
        UNION
        SELECT id_penjualan, metode_pembayaran as metode FROM pembayaran_piutang
     ) AS all_methods 
     WHERE all_methods.id_penjualan = p.id_penjualan
    ) as daftar_metode
    FROM penjualan p 
    WHERE $where_clause 
    ORDER BY p.tanggal DESC 
    LIMIT $per_page OFFSET $offset");

// === 📊 QUERY SUMMARY DASHBOARD (ALA MEKARI) ===
// 1. Penagihan Belum Dibayar (Kuning)
$qUnpaid = $conn->query("SELECT SUM(sisa_piutang) AS total, COUNT(*) AS jml FROM penjualan WHERE sisa_piutang > 0 AND status = 'selesai'");
$dataUnpaid = $qUnpaid->fetch_assoc();

// 2. Penagihan Telat Dibayar (Merah)
$todayStr = date('Y-m-d');
$qOverdue = $conn->query("SELECT SUM(sisa_piutang) AS total, COUNT(*) AS jml FROM penjualan WHERE sisa_piutang > 0 AND status = 'selesai' AND jatuh_tempo IS NOT NULL AND jatuh_tempo < '$todayStr'");
$dataOverdue = $qOverdue->fetch_assoc();

// 3. Pelunasan Diterima 30 Hari Terakhir (Hijau)
$last30Days = date('Y-m-d H:i:s', strtotime('-30 days'));
$qPayRec = $conn->query("SELECT SUM(nominal) AS total FROM pembayaran_piutang WHERE tanggal >= '$last30Days'");
$dataPayRec = $qPayRec->fetch_assoc();
// Tambah Tunai/DP dari penjualan langsung 30 hari terakhir
$qSalesCash = $conn->query("SELECT SUM(total - sisa_piutang) AS total FROM penjualan WHERE status = 'selesai' AND tanggal >= '$last30Days'");
$dataSalesCash = $qSalesCash->fetch_assoc();
$totalReceived30 = ($dataPayRec['total'] ?? 0) + ($dataSalesCash['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Penjualan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/penjualan_list.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
      <?php include __DIR__ . '/../includes/sidebar.php'; ?>
      <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="page-content">

          <div class="page-header-flex">
            <h2>🛒 Manajemen Penjualan</h2>
          </div>

          <!-- 📊 DASHBOARD SUMMARY (ALA MEKARI) -->
          <div class="summary-container">
            <!-- ACTION CARD: BUAT TRANSAKSI BARU -->
            <form method="POST" class="action-card">
              <button type="submit" name="buat_transaksi">
                <div class="btn-icon">🛒</div>
                <div class="btn-text">
                  <strong>Buat Transaksi</strong>
                  <small>Kasir Kece Labora</small>
                </div>
              </button>
            </form>

            <!-- Card 1: Kuning (Belum Lunas) -->
            <a href="?filter=unpaid" class="summary-card card-unpaid <?= $filter == 'unpaid' ? 'active-filter' : '' ?>" style="text-decoration: none;">
              <div class="summary-header">
                <span class="summary-title">Belum Lunas</span>
                <span class="summary-badge"><?= $dataUnpaid['jml'] ?></span>
              </div>
              <div class="summary-value">Rp <?= number_format($dataUnpaid['total'] ?? 0, 0, ',', '.') ?></div>
              <span class="summary-label">Piutang Pelanggan</span>
            </a>

            <!-- Card 2: Merah (Jatuh Tempo) -->
            <a href="?filter=overdue" class="summary-card card-overdue <?= $filter == 'overdue' ? 'active-filter' : '' ?>" style="text-decoration: none;">
              <div class="summary-header">
                <span class="summary-title">Jatuh Tempo</span>
                <span class="summary-badge"><?= $dataOverdue['jml'] ?></span>
              </div>
              <div class="summary-value">Rp <?= number_format($dataOverdue['total'] ?? 0, 0, ',', '.') ?></div>
              <span class="summary-label">Tunggakan Telat</span>
            </a>

            <!-- Card 3: Hijau (Pelunasan 30 Hari) -->
            <div class="summary-card card-received">
              <div class="summary-header">
                <span class="summary-title">Kas Masuk</span>
              </div>
              <div class="summary-value">Rp <?= number_format($totalReceived30, 0, ',', '.') ?></div>
              <span class="summary-label">Diterima (30 Hari)</span>
            </div>
          </div>

          <!-- 📜 SECTION: RIWAYAT TRANSAKSI SELESAI -->
          <div class="sub-header-flex">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h3> Riwayat Penjualan (Selesai/Batal)</h3>
                <?php if ($filter != 'all'): ?>
                    <a href="penjualan.php" class="btn-outline" style="border-color: #3b82f6; color: #3b82f6; background: #eff6ff; padding: 4px 10px;">✕ Hapus Filter</a>
                <?php endif; ?>
            </div>
            <div class="search-wrapper">
              <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="pelangganSearch" placeholder="Cari nama pelanggan...">
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table id="historyTable">
            <thead>
              <tr>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Metode</th>
                <th>Sisa Tagihan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($q_riwayat->num_rows == 0): ?>
                <tr><td colspan="7" style="text-align:center; padding:30px; color:#9ca3af;">Belum ada riwayat transaksi.</td></tr>
              <?php else: ?>
                <?php while($p = $q_riwayat->fetch_assoc()): ?>
                <tr class="history-row">
                  <td><a href="penjualan_detail.php?id=<?= $p['id_penjualan'] ?>" style="color:#3b82f6; font-weight:600;">#INV-<?= $p['id_penjualan'] ?></a></td>
                  <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                  <td class="customer-col"><?= htmlspecialchars($p['pelanggan'] ?: '-') ?></td>
                  <td>
                    <?php 
                    $metodes = explode(',', $p['daftar_metode'] ?? 'tunai');
                    foreach($metodes as $m): 
                        if ($m == 'piutang' && $p['sisa_piutang'] == 0) continue;
                        
                        if ($m == 'transfer') {
                            $color = '#10b981'; // Hijau
                        } elseif ($m == 'piutang') {
                            $color = '#ef4444'; // Merah
                        } else {
                            $color = '#3b82f6'; // Biru (Tunai)
                        }
                    ?>
                        <span style="font-size: 10px; font-weight: 800; color: white; background: <?= $color ?>; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; margin-right: 2px;"><?= $m ?></span>
                    <?php endforeach; ?>
                  </td>
                  <td>
                    <?php if ($p['sisa_piutang'] > 0): ?>
                      <span class="sisa-tagihan-col">Rp <?= number_format($p['sisa_piutang'], 0, ',', '.') ?></span>
                    <?php else: ?>
                      <span class="sisa-tagihan-nol">Rp 0</span>
                    <?php endif; ?>
                  </td>
                  <td><strong>Rp <?= number_format($p['total'], 0, ',', '.') ?></strong></td>
                  <td>
                    <?php if ($p['status'] == 'selesai'): ?>
                        <?php if ($p['sisa_piutang'] > 0): ?>
                            <span class="status-badge status-piutang">Cicil</span>
                        <?php else: ?>
                            <span class="status-badge status-lunas">Selesai</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-batal">Batal</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="penjualan.php?hapus=<?= $p['id_penjualan'] ?>" class="btn-outline" onclick="return confirm('Hapus riwayat ini?')">🗑️ Hapus</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
            </table>
          </div>
          <div id="noMatch" class="no-result-msg">Data transaksi tidak ditemukan.</div>

          <?php if ($total_pages > 1): ?>
          <div class="pagination">
            <span class="page-info"><?= $page ?> / <?= $total_pages ?></span>
            <div class="page-nav">
              <?php if ($page > 1): ?>
                <a href="?page=1" class="page-btn" title="Awal">«</a>
                <a href="?page=<?= $page - 1 ?>" class="page-btn" title="Sebelumnya">‹</a>
              <?php else: ?>
                <span class="page-btn disabled">«</span>
                <span class="page-btn disabled">‹</span>
              <?php endif; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>" class="page-btn" title="Selanjutnya">›</a>
                <a href="?page=<?= $total_pages ?>" class="page-btn" title="Akhir">»</a>
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

    <script>
      // 🕵️ REAL-TIME SEARCH SCRIPT
      document.getElementById('pelangganSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.history-row');
        let found = false;

        rows.forEach(row => {
          const customerName = row.querySelector('.customer-col').innerText.toLowerCase();
          if (customerName.includes(query)) {
            row.style.display = '';
            found = true;
          } else {
            row.style.display = 'none';
          }
        });

        // Tampilkan pesan jika tidak ada yang cocok
        const noMatch = document.getElementById('noMatch');
        if (!found && query !== '') {
          noMatch.style.display = 'block';
        } else {
          noMatch.style.display = 'none';
        }
      });
    </script>
    </body>
    </html>
