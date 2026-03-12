<header class="header">
  <div class="header-left">
    <span class="today-date"><?= date('l, d F Y') ?></span>
  </div>
  <div class="user-info">
    <div class="user-details">
      <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
      <span class="user-role"><?= ucfirst($_SESSION['user_role']) ?></span>
    </div>
    <div class="user-avatar">
      <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
    </div>
  </div>
</header>
