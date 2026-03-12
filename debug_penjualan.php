<?php
include 'includes/koneksi.php';
$res = $conn->query("SELECT id_penjualan, kasir, pelanggan, metode_pembayaran, total, bayar, kembali, sisa_piutang, status, id_shift FROM penjualan ORDER BY id_penjualan DESC LIMIT 5");
echo "<table border='1'><tr><th>ID</th><th>Kasir</th><th>Pelanggan</th><th>Metode</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Piutang</th><th>Status</th><th>Shift</th></tr>";
while($r = $res->fetch_assoc()) {
    echo "<tr><td>".implode("</td><td>", $r)."</td></tr>";
}
echo "</table>";
?>
