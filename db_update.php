<?php
include __DIR__ . '/includes/koneksi.php';

echo "<h2>🔧 Database Update - LABORA</h2>";
echo "<p>Sedang memperbarui struktur database...</p>";

// 1. Tambah kolom ke tabel penjualan
$queries = [
    "ALTER TABLE penjualan ADD COLUMN metode_pembayaran ENUM('tunai', 'piutang') DEFAULT 'tunai'",
    "ALTER TABLE penjualan ADD COLUMN jumlah_bayar DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE penjualan ADD COLUMN sisa_piutang DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE penjualan ADD COLUMN jatuh_tempo DATE NULL",
    "ALTER TABLE penjualan ADD COLUMN id_shift INT NULL",
    
    // 2. Buat tabel kas_shift
    "CREATE TABLE IF NOT EXISTS kas_shift (
        id_shift INT AUTO_INCREMENT PRIMARY KEY,
        kasir VARCHAR(50) NOT NULL,
        waktu_buka DATETIME NOT NULL,
        waktu_tutup DATETIME NULL,
        saldo_awal DECIMAL(15,2) DEFAULT 0,
        saldo_akhir_sistem DECIMAL(15,2) DEFAULT 0,
        saldo_akhir_fisik DECIMAL(15,2) DEFAULT 0,
        selisih DECIMAL(15,2) DEFAULT 0,
        status ENUM('open', 'closed') DEFAULT 'open'
    )",

    // 3. Buat tabel pembayaran_piutang
    "CREATE TABLE IF NOT EXISTS pembayaran_piutang (
        id_bayar INT AUTO_INCREMENT PRIMARY KEY,
        id_penjualan INT NOT NULL,
        tanggal DATETIME NOT NULL,
        nominal DECIMAL(15,2) NOT NULL,
        id_shift INT NULL
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "<div style='color:green;'>✅ Berhasil: " . substr($sql, 0, 50) . "...</div>";
    } else {
        echo "<div style='color:orange;'>ℹ️ Lewati/Sudah ada: " . $conn->error . "</div>";
    }
}

echo "<br><p><strong>Update selesai!</strong> Silakan hapus file ini dan kembali ke <a href='dashboard/dashboard.php'>Dashboard</a>.</p>";
?>
