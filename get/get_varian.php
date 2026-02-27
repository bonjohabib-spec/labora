<?php
include __DIR__ . '/includes/koneksi.php';

$id_barang = intval($_GET['id_barang'] ?? 0);

if ($id_barang <= 0) {
    echo "<option value=''>Pilih Varian</option>";
    exit;
}

$q = $conn->query("SELECT id_varian, warna, ukuran FROM barang_varian WHERE id_barang=$id_barang ORDER BY warna, ukuran ASC");

$options = "<option value=''>Pilih Varian</option>";
while ($v = $q->fetch_assoc()) {
    $warna = htmlspecialchars($v['warna']);
    $ukuran = htmlspecialchars($v['ukuran']);
    $options .= "<option value='{$v['id_varian']}'>{$warna} - {$ukuran}</option>";
}

echo $options;
