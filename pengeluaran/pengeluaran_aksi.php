<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}

if (isset($_POST['simpan'])) {
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $kategori  = mysqli_real_escape_string($conn, $_POST['kategori']);
    $nominal   = mysqli_real_escape_string($conn, $_POST['nominal']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kasir     = $_SESSION['username'] ?? 'admin';

    $query = "INSERT INTO pengeluaran (tanggal, kategori, deskripsi, nominal, dibuat_oleh) 
              VALUES ('$tanggal', '$kategori', '$deskripsi', '$nominal', '$kasir')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Pengeluaran berhasil disimpan!'); window.location='pengeluaran.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal simpan: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    header("Location: pengeluaran.php");
}
?>
