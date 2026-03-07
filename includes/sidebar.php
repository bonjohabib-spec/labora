<?php
include __DIR__ . '/config.php'; // ✅ tambahin ini di paling atas
$currentPage = basename($_SERVER['PHP_SELF']);
?><!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">
      <i class="fas fa-box-open logo-icon"></i>
      <span class="logo-text">LABORA</span>
    </div>
    <button id="toggle-sidebar" class="toggle-btn">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>

  <ul class="nav-menu">
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
    <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/dashboard/dashboard.php">
        <i class="fas fa-th-large"></i>
        <span class="link-text">Beranda</span>
      </a>
    </li>
    
    <li class="<?= $currentPage == 'stok_barang.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/barang/stok_barang.php">
        <i class="fas fa-boxes-stacked"></i>
        <span class="link-text">Stok Barang</span>
      </a>
    </li>
    <?php endif; ?>

    <li class="<?= $currentPage == 'penjualan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/penjualan/penjualan.php">
        <i class="fas fa-shopping-cart"></i>
        <span class="link-text">Penjualan</span>
      </a>
    </li>

    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
    <li class="<?= $currentPage == 'pengeluaran.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/pengeluaran/pengeluaran.php">
        <i class="fas fa-wallet"></i>
        <span class="link-text">Pengeluaran</span>
      </a>
    </li>
    <li class="<?= $currentPage == 'laporan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/laporan/laporan.php">
        <i class="fas fa-chart-line"></i>
        <span class="link-text">Laporan</span>
      </a>
    </li>
    <li class="<?= $currentPage == 'pengaturan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/pengaturan/pengaturan.php">
        <i class="fas fa-cog"></i>
        <span class="link-text">Pengaturan</span>
      </a>
    </li>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= $base_url ?>/auth/logout.php" title="Logout">
      <i class="fas fa-sign-out-alt"></i>
      <span class="link-text">Logout</span>
    </a>
  </div>
</div>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggle-sidebar');
  const mainContent = document.querySelector('.main-content');

  // Load state from localStorage
  if (localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    if(mainContent) mainContent.classList.add('sidebar-collapsed');
  }

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebar-collapsed', isCollapsed);
    
    if(mainContent) {
      mainContent.classList.toggle('sidebar-collapsed', isCollapsed);
    }
  });
</script>
