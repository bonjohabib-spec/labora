<?php
include 'includes/koneksi.php';
$q = mysqli_query($conn, "SELECT * FROM kas_shift WHERE status = 'open' ORDER BY id_shift DESC LIMIT 1");
$data = mysqli_fetch_assoc($q);
echo "=== DATA SHIFT AKTIF ===\n";
print_r($data);

echo "\n=== DATA PENJUALAN HARI INI ===\n";
$q2 = mysqli_query($conn, "SELECT id_penjualan, total, jumlah_bayar, sisa_piutang FROM penjualan WHERE DATE(tanggal) = CURDATE() AND status = 'selesai'");
while($r = mysqli_fetch_assoc($q2)) {
    echo "<pre>";
    print_r($r);
    echo "</pre>";
}

echo "\n=== DATA PEMBAYARAN PIUTANG HARI INI ===\n";
$q3 = mysqli_query($conn, "SELECT * FROM pembayaran_piutang WHERE DATE(tanggal) = CURDATE()");
while($r = mysqli_fetch_assoc($q3)) {
    print_r($r);
}
?>
