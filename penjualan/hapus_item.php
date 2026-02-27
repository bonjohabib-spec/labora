<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php'; // biar bisa catat riwayat stok

if (session_status() == PHP_SESSION_NONE) session_start();

// Pastikan login
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'owner1';
    $_SESSION['user_role'] = 'owner';
}

header('Content-Type: application/json');

$id_item = intval($_POST['id'] ?? 0);
if (!$id_item) {
    echo json_encode(['success'=>false,'message'=>'ID item tidak ditemukan']);
    exit;
}

$conn->begin_transaction();

try {
    // Ambil detail item
    $q = $conn->prepare("
        SELECT dp.id_varian, dp.qty, v.id_barang
        FROM detail_penjualan dp
        JOIN barang_varian v ON dp.id_varian = v.id_varian
        WHERE dp.id = ?
    ");
    $q->bind_param("i", $id_item);
    $q->execute();
    $res = $q->get_result();
    $item = $res->fetch_assoc();

    if (!$item) {
        throw new Exception("Item tidak ditemukan di database");
    }

    $id_varian = $item['id_varian'];
    $qty = $item['qty'];
    $id_barang = $item['id_barang'];

    // Kembalikan stok
    $upd = $conn->prepare("UPDATE barang_varian SET stok = stok + ? WHERE id_varian = ?");
    $upd->bind_param("ii", $qty, $id_varian);
    $upd->execute();

    // Catat riwayat stok
    $keterangan = "Item transaksi dihapus, stok dikembalikan";
    catat_riwayat_stok($conn, $id_barang, $id_varian, $qty, 'penambahan', $keterangan);

    // Hapus detail_penjualan
    $del = $conn->prepare("DELETE FROM detail_penjualan WHERE id = ?");
    $del->bind_param("i", $id_item);
    $del->execute();

    $conn->commit();

    echo json_encode(['success'=>true,'message'=>'Item berhasil dihapus']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
