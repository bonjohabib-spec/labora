<?php
include __DIR__ . '/includes/koneksi.php';

echo "<h2>🔧 Database Update - LABORA</h2>";
echo "<p>Sedang memperbarui struktur database...</p>";

// Fungsi pembantu untuk cek kolom
function addColumn($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            echo "<div style='color:green;'>✅ Berhasil tambah kolom: $column</div>";
        } else {
            echo "<div style='color:red;'>❌ Gagal tambah kolom $column: " . $conn->error . "</div>";
        }
    } else {
        echo "<div style='color:blue;'>ℹ️ Kolom `$column` sudah ada, dilewati.</div>";
    }
}

// 1. Update Tabel Penjualan
addColumn($conn, 'penjualan', 'metode_pembayaran', "ENUM('tunai', 'piutang') DEFAULT 'tunai'");
addColumn($conn, 'penjualan', 'jumlah_bayar', "DECIMAL(15,2) DEFAULT 0");
addColumn($conn, 'penjualan', 'sisa_piutang', "DECIMAL(15,2) DEFAULT 0");
addColumn($conn, 'penjualan', 'jatuh_tempo', "DATE NULL");
addColumn($conn, 'penjualan', 'id_shift', "INT NULL");

// 1b. Update Tabel Detail Penjualan (PENTING untuk Profit)
addColumn($conn, 'detail_penjualan', 'harga_beli', "DECIMAL(15,2) DEFAULT 0");

// Isi data harga_beli yang kosong dari barang_varian (untuk transaksi lama)
$conn->query("UPDATE detail_penjualan d JOIN barang_varian v ON d.id_varian = v.id_varian SET d.harga_beli = v.harga_beli WHERE d.harga_beli = 0");

// 2. Buat tabel kas_shift
$sqlShift = "CREATE TABLE IF NOT EXISTS kas_shift (
    id_shift INT AUTO_INCREMENT PRIMARY KEY,
    kasir VARCHAR(50) NOT NULL,
    waktu_buka DATETIME NOT NULL,
    waktu_tutup DATETIME NULL,
    saldo_awal DECIMAL(15,2) DEFAULT 0,
    saldo_akhir_sistem DECIMAL(15,2) DEFAULT 0,
    saldo_akhir_fisik DECIMAL(15,2) DEFAULT 0,
    selisih DECIMAL(15,2) DEFAULT 0,
    status ENUM('open', 'closed') DEFAULT 'open'
)";
if ($conn->query($sqlShift)) echo "<div style='color:green;'>✅ Tabel kas_shift siap.</div>";

// 3. Buat tabel pembayaran_piutang
$sqlCicilan = "CREATE TABLE IF NOT EXISTS pembayaran_piutang (
    id_bayar INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    tanggal DATETIME NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    id_shift INT NULL
)";
if ($conn->query($sqlCicilan)) echo "<div style='color:green;'>✅ Tabel pembayaran_piutang siap.</div>";

echo "<br><p><strong>Update selesai!</strong> Silakan hapus file ini dan kembali ke <a href='dashboard/dashboard.php'>Dashboard</a>.</p>";
?>
