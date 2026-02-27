<?php
include 'koneksi.php';
$id = intval($_GET['id'] ?? 0);
$q = mysqli_query($conn, "SELECT * FROM penjualan WHERE id_penjualan=$id");
$s = mysqli_fetch_assoc($q);
$items = mysqli_query($conn, "SELECT * FROM detail_penjualan WHERE id_penjualan=$id");
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Struk</title><style>body{font-family:monospace;width:240px;margin:0 auto;font-size:12px}.center{text-align:center}.line{border-top:1px dashed #000;margin:6px 0}</style></head>
<body onload="window.print()">
<div class="center"><img src="assets/img/logo-labora.png" width="50"><div>LABORA - POS</div><div>Tanggal: <?= $s['tanggal'] ?></div></div><div class="line"></div>
<table width="100%"><?php while($r=mysqli_fetch_assoc($items)){ echo '<tr><td colspan="2">'.$r['nama_barang'].'</td></tr><tr><td>'.$r['qty'].' x '.number_format($r['harga'],0,',','.') .'</td><td align="right">'.number_format($r['subtotal'],0,',','.').'</td></tr>'; } ?></table><div class="line"></div><table width="100%"><tr><td>Total</td><td align="right"><?= number_format($s['total'],0,',','.') ?></td></tr></table><div class="line"></div><div class="center">Terima Kasih</div></body></html>