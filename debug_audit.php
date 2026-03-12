<?php
include 'includes/koneksi.php';

echo "<h3>Data Kas Shift Terakhir:</h3>";
$resShift = $conn->query("SELECT * FROM kas_shift ORDER BY id_shift DESC LIMIT 1");
echo "<table border='1'><tr>";
while($f = $resShift->fetch_field()) echo "<th>".$f->name."</th>";
echo "</tr>";
$shift = $resShift->fetch_assoc();
echo "<tr><td>".implode("</td><td>", $shift)."</td></tr></table>";

$id_s = $shift['id_shift'];

echo "<h3>Data Penjualan pada Shift #$id_s:</h3>";
$resPenjualan = $conn->query("SELECT id_penjualan, tanggal, metode_pembayaran, total, bayar, kembali, sisa_piutang, id_shift FROM penjualan WHERE id_shift = $id_s");
echo "<table border='1'><tr><th>ID</th><th>Tgl</th><th>Metode</th><th>Total</th><th>Bayar</th><th>Kembali</th><th>Piutang</th><th>Shift</th></tr>";
while($p = $resPenjualan->fetch_assoc()){
    echo "<tr><td>".implode("</td><td>", $p)."</td></tr>";
}
echo "</table>";

echo "<h3>Data Pelunasan Piutang pada Shift #$id_s:</h3>";
$resPiutang = $conn->query("SELECT * FROM pembayaran_piutang WHERE id_shift = $id_s");
echo "<table border='1'><tr>";
if($resPiutang->num_rows > 0){
    while($f = $resPiutang->fetch_field()) echo "<th>".$f->name."</th>";
    echo "</tr>";
    while($piu = $resPiutang->fetch_assoc()){
        echo "<tr><td>".implode("</td><td>", $piu)."</td></tr>";
    }
} else {
    echo "<td>Belum ada pelunasan</td>";
}
echo "</table>";
?>
