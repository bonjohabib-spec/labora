<?php
include __DIR__ . '/config.php';
$currentPage = basename($_SERVER['PHP_SELF']);

// ✅ Ambil status sidebar dari Cookie agar instan (Anti-Flicker)
$is_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
?>
<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="sidebar <?= $is_collapsed ? 'collapsed' : '' ?>" id="sidebar">
  <div class="sidebar-header">
    <div class="logo-brand">
      <div class="logo-circle">
        <i class="fas fa-star"></i>
      </div>
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
        <span class="link-text">Dashboard</span>
      </a>
    </li>
    
    <li class="<?= $currentPage == 'stok_barang.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/barang/stok_barang.php">
        <i class="fas fa-boxes-stacked"></i>
        <span class="link-text">Produk</span>
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
    <li class="<?= $currentPage == 'laporan.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/laporan/laporan.php">
        <i class="fas fa-chart-line"></i>
        <span class="link-text">Laporan</span>
      </a>
    </li>
    <?php endif; ?>

    <li class="<?= $currentPage == 'tutup_kasir.php' ? 'active' : '' ?>">
      <a href="<?= $base_url ?>/dashboard/tutup_kasir.php">
        <i class="fas fa-flag-checkered"></i>
        <span class="link-text">Tutup Kasir</span>
      </a>
    </li>

    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
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
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggle-sidebar');
  const mainContent = document.querySelector('.main-content');

  // Logic to set Cookie
  const setSidebarCookie = (val) => {
    const d = new Date();
    d.setTime(d.getTime() + (30*24*60*60*1000)); // 30 hari
    document.cookie = "sidebar_collapsed=" + val + ";expires=" + d.toUTCString() + ";path=/";
  };

  const applySidebarState = (isCollapsed) => {
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('sidebar-collapsed');
    } else {
      sidebar.classList.remove('collapsed');
      if (mainContent) mainContent.classList.remove('sidebar-collapsed');
    }
  };

  // ✅ Sinkronisasi awal main-content via JS (PHP sudah handle sidebar element)
  const isCollapsedInitial = sidebar.classList.contains('collapsed');
  if (isCollapsedInitial && mainContent) {
    mainContent.classList.add('sidebar-collapsed');
  }

  toggleBtn.addEventListener('click', () => {
    const isNowCollapsed = !sidebar.classList.contains('collapsed');
    applySidebarState(isNowCollapsed);
    setSidebarCookie(isNowCollapsed);
  });
});
</script>
