<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Proteksi role hanya untuk owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
  header("Location: ../index.php");
  exit();
}

$id_varian = intval($_GET['id'] ?? 0);
if ($id_varian == 0) {
  header("Location: stok_barang.php");
  exit();
}

// Cek apakah varian sudah pernah dijual
$cekTransaksi = $conn->query("SELECT COUNT(*) AS total FROM detail_penjualan WHERE id_varian = $id_varian");
$sudahDijual = $cekTransaksi->fetch_assoc()['total'] > 0;

if ($sudahDijual) {
  echo "<script>alert('Barang ini sudah pernah dijual, tidak bisa diedit!'); window.location='stok_barang.php';</script>";
  exit;
}

// Ambil data varian + barang
$sql = "SELECT b.id_barang, b.nama_barang, b.stok_min, b.stok_max, 
               v.id_varian, v.warna, v.ukuran, v.harga_beli, v.harga_jual, v.stok
        FROM barang b 
        JOIN barang_varian v ON b.id_barang = v.id_barang 
        WHERE v.id_varian = $id_varian";
$data = $conn->query($sql)->fetch_assoc();

if (!$data) {
  echo "<script>alert('Data tidak ditemukan!'); window.location='stok_barang.php';</script>";
  exit;
}

// Proses Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_barang = $conn->real_escape_string(trim($_POST['nama_barang']));
  $warna = $conn->real_escape_string(trim($_POST['warna']));
  $ukuran = $conn->real_escape_string(trim($_POST['ukuran']));
  $harga_beli = intval($_POST['harga_beli']);
  $harga_jual = intval($_POST['harga_jual']);
  $stok = intval($_POST['stok']);
  $stok_min = intval($_POST['stok_min']);
  $stok_max = intval($_POST['stok_max']);

  $conn->begin_transaction();
  try {
    // Update barang induk
    $stmt1 = $conn->prepare("UPDATE barang SET nama_barang = ?, stok_min = ?, stok_max = ? WHERE id_barang = ?");
    $stmt1->bind_param("siii", $nama_barang, $stok_min, $stok_max, $data['id_barang']);
    $stmt1->execute();

    // Update varian
    $stmt2 = $conn->prepare("UPDATE barang_varian SET warna = ?, ukuran = ?, harga_beli = ?, harga_jual = ?, stok = ? WHERE id_varian = ?");
    $stmt2->bind_param("ssiiis", $warna, $ukuran, $harga_beli, $harga_jual, $stok, $id_varian);
    $stmt2->execute();

    $conn->commit();
    echo "<script>alert('Barang berhasil diperbarui!'); window.location='stok_barang.php';</script>";
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error = "Gagal mengupdate: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Barang - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/tambah_barang.css?v=<?= time() ?>">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <div class="page-content">
        <div style="margin-bottom: 20px;">
          <a href="stok_barang.php" style="text-decoration: none; color: #94a3b8; font-size: 13px; display: flex; align-items: center; gap: 5px;">
            <span>←</span> Kembali ke Daftar Stok
          </a>
        </div>
        <section class="form-section">
          <h1 style="margin-top: 0; font-size: 18px;">Edit Barang</h1>

          <?php if (isset($error)): ?>
            <div style="background: #fef2f2; color: #dc2626; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 15px;">
              <?= $error ?>
            </div>
          <?php endif; ?>

          <form method="POST" class="form-barang">
            <div class="form-group">
              <label>Nama Barang <span style="color:red">*</span></label>
              <input type="text" name="nama_barang" value="<?= htmlspecialchars($data['nama_barang']) ?>" required>
            </div>

            <div class="form-group">
              <label>Warna</label>
              <input type="text" name="warna" value="<?= htmlspecialchars($data['warna']) ?>">
            </div>

            <div class="form-group">
              <label>Ukuran</label>
              <input type="text" name="ukuran" value="<?= htmlspecialchars($data['ukuran']) ?>">
            </div>

            <div class="form-group">
              <label>Harga Beli</label>
              <input type="number" name="harga_beli" min="0" value="<?= $data['harga_beli'] ?>">
            </div>

            <div class="form-group">
              <label>Harga Jual</label>
              <input type="number" name="harga_jual" min="0" value="<?= $data['harga_jual'] ?>">
            </div>

            <div class="form-group">
              <label>Stok <span style="color:red">*</span></label>
              <input type="number" name="stok" min="0" value="<?= $data['stok'] ?>" required>
            </div>

            <div class="form-group">
              <label>Stok Minimum</label>
              <input type="number" name="stok_min" min="0" value="<?= $data['stok_min'] ?>">
            </div>

            <div class="form-group">
              <label>Stok Maksimum</label>
              <input type="number" name="stok_max" min="0" value="<?= $data['stok_max'] ?>">
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">💾 Simpan Perubahan</button>
              <button type="button" class="btn-outline" onclick="window.location.href='stok_barang.php'">Batal</button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</body>
</html>
