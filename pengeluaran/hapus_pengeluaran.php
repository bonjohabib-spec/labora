<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM pengeluaran WHERE id_pengeluaran = $id";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Pengeluaran berhasil dihapus.'); window.location='pengeluaran.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal hapus: " . mysqli_error($conn) . "'); window.location='pengeluaran.php';</script>";
    }
} else {
    header("Location: pengeluaran.php");
}
?>
