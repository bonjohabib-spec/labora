<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php'; 
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = 'owner1';
  $_SESSION['user_role'] = 'owner';
}

$kasir = $_SESSION['username'];

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
if (isset($_POST['buat_transaksi'])) {
  $pelanggan = trim($_POST['pelanggan']);
  $stmt = $conn->prepare("INSERT INTO penjualan (tanggal, kasir, pelanggan, status, total, keuntungan)
                          VALUES (NOW(), ?, ?, 'aktif', 0, 0)");
  $stmt->bind_param("ss", $kasir, $pelanggan);
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

// Paginasi
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$q_total = $conn->query("SELECT COUNT(*) AS total FROM penjualan WHERE status IN ('selesai', 'batal')");
$total_rows = $q_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$q_riwayat = $conn->query("SELECT * FROM penjualan WHERE status IN ('selesai', 'batal') ORDER BY tanggal DESC LIMIT $per_page OFFSET $offset");
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
            <h2>Manajemen Penjualan</h2>
            <form method="POST" class="header-form-flex">
              <button type="submit" name="buat_transaksi" class="btn-primary">+ Buat Transaksi Baru</button>
            </form>
          </div>

          <!-- 📜 SECTION: RIWAYAT TRANSAKSI SELESAI -->
          <div class="sub-header-flex">
            <h3>📜 Riwayat Penjualan (Selesai/Batal)</h3>
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
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($q_riwayat->num_rows == 0): ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">Belum ada riwayat transaksi.</td></tr>
              <?php else: ?>
                <?php while($p = $q_riwayat->fetch_assoc()): ?>
                <tr class="history-row">
                  <td><a href="penjualan_detail.php?id=<?= $p['id_penjualan'] ?>" style="color:#3b82f6; font-weight:600;">#INV-<?= $p['id_penjualan'] ?></a></td>
                  <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                  <td class="customer-col"><?= htmlspecialchars($p['pelanggan'] ?: '-') ?></td>
                  <td><strong>Rp <?= number_format($p['total'], 0, ',', '.') ?></strong></td>
                  <td>
                    <span class="<?= $p['status'] == 'selesai' ? 'badge-selesai' : 'badge-batal' ?>">
                      <?= ucfirst($p['status']) ?>
                    </span>
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
