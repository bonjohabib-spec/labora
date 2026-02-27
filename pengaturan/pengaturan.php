<?php
session_start();
include 'koneksi.php';
if(!isset($_SESSION['user_role'])){ header('Location:index.php'); exit(); }
if($_SESSION['user_role']!=='owner'){ echo 'Akses terbatas'; exit(); }
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pengaturan - LABORA</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'dashboard.php'; exit(); ?>
</body></html>