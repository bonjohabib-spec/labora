<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['simpan'])) {
    $tanggal   = $_POST['tanggal'];
    $kategori  = $_POST['kategori'];
    $nominal   = floatval($_POST['nominal']);
    $deskripsi = $_POST['deskripsi'];
    $kasir     = $_SESSION['username'] ?? 'admin';

    $stmt = $conn->prepare("INSERT INTO pengeluaran (tanggal, kategori, deskripsi, nominal, dibuat_oleh) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $tanggal, $kategori, $deskripsi, $nominal, $kasir);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Pengeluaran berhasil disimpan!'); window.location='pengeluaran.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal simpan: " . $conn->error . "'); window.history.back();</script>";
    }
} else {
    header("Location: pengeluaran.php");
}
?>
