<?php
include 'includes/koneksi.php';
$res = $conn->query("DESC penjualan");
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
