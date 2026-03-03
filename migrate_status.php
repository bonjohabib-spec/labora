<?php
include 'includes/koneksi.php';

// Cek apakah kolom status sudah ada
$check = $conn->query("SHOW COLUMNS FROM barang_varian LIKE 'status'");
if ($check->num_rows == 0) {
    $q = "ALTER TABLE barang_varian ADD COLUMN status ENUM('aktif', 'non-aktif') DEFAULT 'aktif' AFTER stok";
    if ($conn->query($q)) {
        echo "✅ Kolom 'status' berhasil ditambahkan ke tabel barang_varian.\n";
    } else {
        echo "❌ Gagal menambahkan kolom: " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Kolom 'status' sudah ada.\n";
}
?>
