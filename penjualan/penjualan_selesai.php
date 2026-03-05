<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id_penjualan'])) {
        die("Data transaksi tidak lengkap!");
    }

    $id_penjualan = intval($_POST['id_penjualan']);
    $pelanggan    = trim($_POST['pelanggan'] ?: '-');

    $conn->begin_transaction();

    try {
        // 1. Ambil semua item dari detail_penjualan untuk transaksi ini
        $qDetail = $conn->prepare("
            SELECT d.id_varian, d.id_barang, d.qty, v.warna, v.ukuran, b.nama_barang 
            FROM detail_penjualan d
            JOIN barang_varian v ON d.id_varian = v.id_varian
            JOIN barang b ON v.id_barang = b.id_barang
            WHERE d.id_penjualan = ?
        ");
        $qDetail->bind_param("i", $id_penjualan);
        $qDetail->execute();
        $resDetail = $qDetail->get_result();

        // 2. Loop untuk kurangi stok dan catat riwayat
        while ($item = $resDetail->fetch_assoc()) {
            $id_v = $item['id_varian'];
            $id_b = $item['id_barang'];
            $qty  = $item['qty'];
            
            // Kurangi Stok
            $updStok = $conn->prepare("UPDATE barang_varian SET stok = stok - ? WHERE id_varian = ?");
            $updStok->bind_param("ii", $qty, $id_v);
            $updStok->execute();

            // Catat Riwayat
            $ket = "Penjualan #$id_penjualan: {$item['nama_barang']} ({$item['warna']} - {$item['ukuran']})";
            catat_riwayat_stok($conn, $id_b, $id_v, $qty, 'pengurangan', $ket);
        }

        // 3. Update status transaksi jadi selesai dengan data pembayaran
        $bayar   = floatval($_POST['bayar'] ?? 0);
        $kembali = floatval($_POST['kembali'] ?? 0);

        $update = $conn->prepare("
            UPDATE penjualan 
            SET status = 'selesai', pelanggan = ?, bayar = ?, kembali = ? 
            WHERE id_penjualan = ?
        ");
        $update->bind_param("sdii", $pelanggan, $bayar, $kembali, $id_penjualan);
        $update->execute();

        $conn->commit();
        header("Location: penjualan_finish.php?id=$id_penjualan");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Gagal menyelesaikan transaksi: " . $e->getMessage());
    }
} else {
    die("Akses tidak sah!");
}
?>
