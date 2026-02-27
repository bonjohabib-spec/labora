<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id_penjualan']) || empty($_POST['pelanggan'])) {
        die("Data transaksi tidak lengkap!");
    }

    $id_penjualan = intval($_POST['id_penjualan']);
    $pelanggan    = trim($_POST['pelanggan']);

    // 🔒 Gunakan prepared statement biar aman dari SQL injection
    $update = $conn->prepare("
        UPDATE penjualan 
        SET status = 'selesai', pelanggan = ? 
        WHERE id_penjualan = ?
    ");
    $update->bind_param("si", $pelanggan, $id_penjualan);

    if ($update->execute()) {
        // ✅ Bisa tambahkan log stok/aktivitas jika diperlukan
        // contoh: catat ke riwayatstok kalau kamu ingin
        // recordEvent('penjualan', "Transaksi #$id_penjualan diselesaikan untuk $pelanggan");

        header("Location: penjualan.php");
        exit;
    } else {
        die("Gagal menyelesaikan transaksi: " . $conn->error);
    }
} else {
    die("Akses tidak sah!");
}
?>
