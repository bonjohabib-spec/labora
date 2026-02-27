<?php
include __DIR__ . '/koneksi.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id_varian'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter id_varian tidak dikirim',
        'harga_jual' => 0
    ]);
    exit;
}

$id_varian = intval($_GET['id_varian']);

$stmt = $conn->prepare("SELECT harga_jual FROM barang_varian WHERE id_varian = ?");
$stmt->bind_param("i", $id_varian);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $harga_jual = floatval($row['harga_jual']);
    echo json_encode([
        'success' => true,
        'harga_jual' => $harga_jual > 0 ? $harga_jual : 0
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Varian tidak ditemukan',
        'harga_jual' => 0
    ]);
}
?>
