<?php
include 'c:/laragon/www/labora/includes/koneksi.php';

echo "--- STRUCTURE PENJUALAN ---\n";
$res = $conn->query("DESCRIBE penjualan");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- ADDING NEW COLUMNS ---\n";
$conn->query("ALTER TABLE penjualan ADD COLUMN metode_pembayaran ENUM('tunai', 'piutang') DEFAULT 'tunai'");
$conn->query("ALTER TABLE penjualan ADD COLUMN jumlah_bayar DECIMAL(15,2) DEFAULT 0");
$conn->query("ALTER TABLE penjualan ADD COLUMN sisa_piutang DECIMAL(15,2) DEFAULT 0");
$conn->query("ALTER TABLE penjualan ADD COLUMN jatuh_tempo DATE NULL");
$conn->query("ALTER TABLE penjualan ADD COLUMN id_shift INT NULL");

echo "Checking if success...\n";
$res = $conn->query("DESCRIBE penjualan");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- CREATING TABLES ---\n";
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
if($conn->query($sqlShift)) echo "Table kas_shift OK\n"; else echo "Error kas_shift: " . $conn->error . "\n";

$sqlCicilan = "CREATE TABLE IF NOT EXISTS pembayaran_piutang (
    id_bayar INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    tanggal DATETIME NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    id_shift INT NULL
)";
if($conn->query($sqlCicilan)) echo "Table pembayaran_piutang OK\n"; else echo "Error cicilan: " . $conn->error . "\n";
?>
