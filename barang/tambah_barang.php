<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Proteksi role hanya untuk owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
  header("Location: ../index.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Barang - LABORA</title>
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
          <a href="stok_barang.php" style="text-decoration: none; color: #64748b; font-size: 14px; display: flex; align-items: center; gap: 5px;">
            <span>←</span> Kembali ke Daftar Stok
          </a>
        </div>
        <section class="form-section">
          <h1 style="margin-top: 0;">Tambah Barang Baru</h1>

          <form action="proses_tambah_barang.php" method="POST" class="form-barang">
            <div class="form-group">
              <label>Nama Barang <span style="color:red">*</span></label>
              <input type="text" name="nama_barang" placeholder="Contoh: Baju Panjang" required>
            </div>

            <div class="form-group">
              <label>Warna</label>
              <input type="text" name="warna" placeholder="Contoh: Hitam / Putih">
            </div>

            <div class="form-group">
              <label>Ukuran</label>
              <input type="text" name="ukuran" placeholder="Contoh: L / XL / 250ml">
            </div>

            <div class="form-group">
              <label>Harga Beli</label>
              <input type="number" name="harga_beli" min="0" placeholder="Boleh dikosongkan (default 0)" inputmode="decimal">
            </div>

            <div class="form-group">
              <label>Harga Jual</label>
              <input type="number" name="harga_jual" min="0" placeholder="Boleh dikosongkan (default 0)" inputmode="decimal">
            </div>

            <div class="form-group">
              <label>Stok Awal <span style="color:red">*</span></label>
              <input type="number" name="stok" min="0" placeholder="0" required inputmode="numeric">
            </div>

            <div class="form-group">
              <label>Stok Minimum</label>
              <input type="number" name="stok_min" min="0" value="10" inputmode="numeric">
            </div>

            <div class="form-group">
              <label>Stok Maksimum</label>
              <input type="number" name="stok_max" min="0" value="9999" inputmode="numeric">
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">💾 Simpan Barang</button>
              <button type="button" class="btn-outline" onclick="window.location.href='stok_barang.php'">Batal</button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</body>
</html>
