<?php
include __DIR__ . '/config.php'; // ✅ tambahin ini di paling atas
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
  <div class="logo">
    <h2>LABORA</h2>
    <p>Smart POS & Stock Manager</p>
  </div>
  <ul>
    <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/dashboard/dashboard.php">Dashboard</a>
    </li>
    <li class="<?= $currentPage == 'stok_barang.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/barang/stok_barang.php">Stok Barang</a>
    </li>
    <li class="<?= $currentPage == 'penjualan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/penjualan/penjualan.php">Penjualan</a>
    </li>
    <li class="<?= $currentPage == 'pengeluaran.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/pengeluaran/pengeluaran.php">Pengeluaran</a>
    </li>
     <li class="<?= $currentPage == 'laporan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/laporan/laporan.php">Laporan</a>
    </li>
    <li class="<?= $currentPage == 'pengaturan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/pengaturan/pengaturan.php">Pengaturan</a>
    </li>
    <li><a href="<?= $base_url ?>/auth/logout.php">Logout</a></li>
  </ul>
</div>
