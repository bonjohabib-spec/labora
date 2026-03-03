<?php
include __DIR__ . '/../includes/koneksi.php';

$id_barang = intval($_GET['id_barang'] ?? 0);

if ($id_barang <= 0) {
    echo "<option value=''>Pilih Varian</option>";
    exit;
}

// Ambil varian yang aktif saja
$q = $conn->query("SELECT id_varian, warna, ukuran, harga_jual, stok 
                   FROM barang_varian 
                   WHERE id_barang=$id_barang AND status='aktif' 
                   ORDER BY warna, ukuran ASC");

$options = "<option value=''>Pilih Varian</option>";
while ($v = $q->fetch_assoc()) {
    $warna = htmlspecialchars($v['warna']);
    $ukuran = htmlspecialchars($v['ukuran']);
    $harga = $v['harga_jual'];
    $stok = $v['stok'];
    
    // Taruh harga dan stok di atribut data agar bisa diambil via JS
    $options .= "<option value='{$v['id_varian']}' data-harga='{$harga}' data-stok='{$stok}'>
                    {$warna} - {$ukuran} (Stok: {$stok})
                 </option>";
}

echo $options;
