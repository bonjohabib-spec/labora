<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php'; // ✅ biar bisa catat riwayat stok
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = 'owner1';
  $_SESSION['user_role'] = 'owner';
}

$kasir = $_SESSION['username'];

// ==========================
// 🗑️ HAPUS TRANSAKSI (fix + varian + riwayat stok)
// ==========================
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    $conn->begin_transaction();

    try {
        // 🔹 Ambil semua item dari detail_penjualan
        $det = $conn->prepare("SELECT id_varian, qty FROM detail_penjualan WHERE id_penjualan = ?");
        $det->bind_param("i", $id_hapus);
        $det->execute();
        $res = $det->get_result();

        while ($row = $res->fetch_assoc()) {
            $id_varian = $row['id_varian'];
            $qty = $row['qty'];

            // 🔹 Ambil id_barang dari tabel varian
            $getBarang = $conn->prepare("SELECT id_barang FROM barang_varian WHERE id_varian = ?");
            $getBarang->bind_param("i", $id_varian);
            $getBarang->execute();
            $id_barang = $getBarang->get_result()->fetch_assoc()['id_barang'] ?? null;

            // 🔹 Kembalikan stok ke varian
            if ($id_varian) {
                $stmt = $conn->prepare("UPDATE barang_varian SET stok = stok + ? WHERE id_varian = ?");
                $stmt->bind_param("ii", $qty, $id_varian);
                $stmt->execute();
            }

            // 🔹 Catat ke riwayat stok
            if ($id_barang) {
                $keterangan = "Transaksi #$id_hapus dihapus, stok varian dikembalikan";
                catat_riwayat_stok(
                    $conn,
                    $id_barang,
                    $id_varian,
                    $qty,
                    'penambahan',
                    $keterangan
                );
            }
        }

        // 🔹 Hapus detail_penjualan dulu
        $hapusDetail = $conn->prepare("DELETE FROM detail_penjualan WHERE id_penjualan = ?");
        $hapusDetail->bind_param("i", $id_hapus);
        $hapusDetail->execute();

        // 🔹 Baru hapus penjualan utama
        $hapusPenjualan = $conn->prepare("DELETE FROM penjualan WHERE id_penjualan = ?");
        $hapusPenjualan->bind_param("i", $id_hapus);
        $hapusPenjualan->execute();

        $conn->commit();

        echo "<script>
                alert('✅ Transaksi berhasil dihapus.');
                window.location='penjualan.php';
              </script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('Gagal menghapus transaksi: " . addslashes($e->getMessage()) . "');
                window.location='penjualan.php';
              </script>";
        exit;
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
  echo "<script>alert('🧾 Transaksi baru dimulai.'); window.location='penjualan_tambah.php?id=$id_baru';</script>";
  exit;
}

// ==========================
// 📜 RIWAYAT PENJUALAN
// ==========================
$riwayat = $conn->query("
  SELECT id_penjualan, tanggal, pelanggan, kasir, total, status 
  FROM penjualan 
  WHERE status IN ('selesai', 'batal', 'aktif')
  ORDER BY tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Penjualan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <style>
    .btn-success {background:#16a34a;color:white;border:none;padding:8px 15px;border-radius:6px;}
    .btn-primary {background:#2563eb;color:white;border:none;padding:8px 15px;border-radius:6px;}
    .btn-hapus {background:red;color:white;padding:5px 10px;border:none;border-radius:5px;text-decoration:none;}
    .link-transaksi {color:#2563eb;text-decoration:underline;cursor:pointer;}
    table {width:100%;border-collapse:collapse;margin-top:10px;}
    th,td {border:1px solid #ddd;padding:8px;text-align:left;}
    th {background:#f3f4f6;}
  </style>
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">

      <h2>Daftar Penjualan</h2>

      <form method="POST" style="margin-bottom:15px;">
        <input type="text" name="pelanggan" placeholder="Nama Pelanggan (opsional)" style="padding:6px;width:250px;">
        <button type="submit" name="buat_transaksi" class="btn-primary">+ Buat Transaksi Baru</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>No. Transaksi</th>
            <th>Pelanggan</th>
            <th>Kasir</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($p = $riwayat->fetch_assoc()): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
            <td>
              <a href="penjualan_detail.php?id=<?= $p['id_penjualan'] ?>" class="link-transaksi">
                Sales Invoice #<?= $p['id_penjualan'] ?>
              </a>
            </td>
            <td><?= htmlspecialchars($p['pelanggan'] ?: '-') ?></td>
            <td><?= htmlspecialchars($p['kasir']) ?></td>
            <td>Rp <?= number_format($p['total'], 0, ',', '.') ?></td>
            <td style="color:<?= $p['status']=='batal'?'red':($p['status']=='aktif'?'#d97706':'green') ?>;">
              <?= ucfirst($p['status']) ?>
            </td>
            <td>
              <a href="penjualan.php?hapus=<?= $p['id_penjualan'] ?>" class="btn-hapus"
                 onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>
</body>
</html>
