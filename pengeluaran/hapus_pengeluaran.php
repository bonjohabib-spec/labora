<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM pengeluaran WHERE id_pengeluaran = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('✅ Pengeluaran berhasil dihapus.'); window.location='pengeluaran.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal hapus: " . $conn->error . "'); window.location='pengeluaran.php';</script>";
    }
} else {
    header("Location: pengeluaran.php");
}
?>
