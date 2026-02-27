<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$id_penjualan = $_GET['id'] ?? null;

if (!$id_penjualan) {
    die("ID transaksi tidak ditemukan.");
}

// 1️⃣ Ambil semua item dalam transaksi
$qDetail = $conn->prepare("
    SELECT dp.id_varian, dp.qty, b.id_barang
    FROM detail_penjualan dp
    JOIN barang_varian v ON dp.id_varian = v.id_varian
    JOIN barang b ON v.id_barang = b.id_barang
    WHERE dp.id_penjualan = ?
");
$qDetail->bind_param("i", $id_penjualan);
$qDetail->execute();
$resDetail = $qDetail->get_result();

if ($resDetail->num_rows === 0) {
    die("Tidak ada item dalam transaksi ini.");
}

// 2️⃣ Mulai transaksi MySQL
$conn->begin_transaction();

try {
    while ($row = $resDetail->fetch_assoc()) {
        $id_varian = $row['id_varian'];
        $id_barang = $row['id_barang'];
        $qty = $row['qty'];

        // ✅ Kembalikan stok ke varian
        $update = $conn->prepare("UPDATE barang_varian SET stok = stok + ? WHERE id_varian = ?");
        $update->bind_param("ii", $qty, $id_varian);
        $update->execute();

        // ✅ Catat ke riwayat stok
        $ket = "Penghapusan transaksi ID #$id_penjualan";
        $tipe = "penambahan";
        $log = $conn->prepare("
            INSERT INTO riwayat_stok (id_barang, jumlah, tipe, keterangan, tanggal)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $log->bind_param("iiss", $id_barang, $qty, $tipe, $ket);
        $log->execute();
    }

    // 3️⃣ Hapus detail_penjualan
    $hapusDetail = $conn->prepare("DELETE FROM detail_penjualan WHERE id_penjualan = ?");
    $hapusDetail->bind_param("i", $id_penjualan);
    $hapusDetail->execute();

    // 4️⃣ Hapus penjualan
    $hapusPenjualan = $conn->prepare("DELETE FROM penjualan WHERE id_penjualan = ?");
    $hapusPenjualan->bind_param("i", $id_penjualan);
    $hapusPenjualan->execute();

    $conn->commit();

    echo "<script>alert('Transaksi berhasil dihapus. Stok barang telah dikembalikan.'); window.location='../penjualan/penjualan.php';</script>";
} catch (Exception $e) {
    $conn->rollback();
    die("Gagal menghapus transaksi: " . $e->getMessage());
}
?>
