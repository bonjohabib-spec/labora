<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pengaturan - LABORA</title>
<link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
<link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
<link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
<style>
  .settings-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    max-width: 600px;
  }
  .form-group {
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    font-size: 14px;
  }
  .form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    box-sizing: border-box;
  }
  .btn-save {
    background: #3a79ef;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
  }
</style>
</head>
<body><div class="container"><?php include __DIR__ . '/../includes/sidebar.php'; ?><div class="main-content"><?php include __DIR__ . '/../includes/header.php'; ?><h2>Pengaturan Profil</h2><div class="page-content">
<div class="settings-card">
  <form action="" method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
    </div>
    <div class="form-group">
      <label>Role</label>
      <input type="text" value="<?= htmlspecialchars($_SESSION['user_role']) ?>" readonly>
    </div>
    <div class="form-group">
      <label>Password Baru (kosongkan jika tidak ganti)</label>
      <input type="password" name="new_password">
    </div>
    <button type="submit" class="btn-save">Simpan Perubahan</button>
  </form>
</div>
</div></div></div></body></html>