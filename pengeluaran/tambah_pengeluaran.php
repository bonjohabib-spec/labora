<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Pengeluaran - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/pengeluaran.css?v=<?= time() ?>">
</head>
<body>

<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div style="margin-bottom: 20px;">
      <a href="pengeluaran.php" style="text-decoration: none; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">← Kembali ke Daftar</a>
      <div class="welcome-header" style="margin-top: 10px;">
        <h1>➕ Tambah Pengeluaran</h1>
        <p>Gunakan formulir ini untuk mencatat biaya baru.</p>
      </div>
    </div>

    <div class="form-container">
      <form action="pengeluaran_aksi.php" method="POST">
        <div class="form-group">
          <label>Tanggal Pengeluaran</label>
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
          <label>Kategori</label>
          <select name="kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <option value="Operasional Toko">Operasional Toko (Listrik, Wifi, Sewa, dll)</option>
            <option value="Gaji Karyawan">Gaji Karyawan (Gaji, Makan, Bonus)</option>
            <option value="Stok & Inventaris">Stok & Inventaris (Kulakan, ATK, Packing)</option>
            <option value="Pemasaran & Promo">Pemasaran & Promo (Iklan, Spanduk)</option>
            <option value="Perawatan">Perawatan (Servis AC, Renovasi Kecil)</option>
            <option value="Lain-lain">Lain-lain</option>
          </select>
        </div>

        <div class="form-group">
          <label>Nominal (Rp)</label>
          <input type="number" name="nominal" id="nominalInput" placeholder="Contoh: 50000" required>
          <span class="rupiah-preview" id="rupiahPreview">Rp 0</span>
        </div>

        <div class="form-group">
          <label>Keterangan / Deskripsi</label>
          <textarea name="deskripsi" placeholder="Tuliskan detail pengeluaran..." rows="3" required></textarea>
        </div>

        <button type="submit" name="simpan" class="btn-save">Simpan Pengeluaran</button>
      </form>
    </div>

  </div>
</div>

<script>
  const nominalInput = document.getElementById('nominalInput');
  const rupiahPreview = document.getElementById('rupiahPreview');

  nominalInput.addEventListener('input', function() {
    const val = this.value;
    if (val) {
      rupiahPreview.textContent = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(val);
    } else {
      rupiahPreview.textContent = 'Rp 0';
    }
  });
</script>

</body>
</html>
