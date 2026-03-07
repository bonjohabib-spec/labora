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

        // 3. Hitung Total & Keuntungan (Sudah ada di database saat tambah item, tapi kita pastikan lagi)
        $qTotal = $conn->prepare("SELECT SUM(subtotal) as total, SUM(qty * (harga_jual - harga_beli)) as untung FROM detail_penjualan WHERE id_penjualan = ?");
        $qTotal->bind_param("i", $id_penjualan);
        $qTotal->execute();
        $resT = $qTotal->get_result()->fetch_assoc();
        $total_akhir = $resT['total'] ?? 0;
        $untung_akhir = $resT['untung'] ?? 0;

        // 4. Update data pembayaran
        $metode  = $_POST['metode_pembayaran'] ?? 'tunai';
        $bayar   = floatval($_POST['bayar'] ?? 0);
        $kembali = ($metode == 'tunai') ? max(0, $bayar - $total_akhir) : 0;
        $piutang = ($metode == 'piutang') ? max(0, $total_akhir - $bayar) : 0;
        $tgl_jt = ($metode == 'piutang') ? date('Y-m-d', strtotime('+14 days')) : null;

        $update = $conn->prepare("
            UPDATE penjualan 
            SET status = 'selesai', 
                pelanggan = ?, 
                total = ?,
                keuntungan = ?,
                metode_pembayaran = ?, 
                jumlah_bayar = ?, 
                sisa_piutang = ?,
                bayar = ?, 
                kembali = ?,
                jatuh_tempo = ?
            WHERE id_penjualan = ?
        ");
        $update->bind_param("sddsddddsi", $pelanggan, $total_akhir, $untung_akhir, $metode, $bayar, $piutang, $bayar, $kembali, $tgl_jt, $id_penjualan);
        $update->execute();

        // 5. Update Saldo Kas Shift (Jika bayar tunai/DP)
        if ($bayar > 0) {
            $net_cash = $bayar - $kembali;
            $updShift = $conn->prepare("UPDATE kas_shift SET saldo_akhir_sistem = saldo_akhir_sistem + ? WHERE status = 'open' AND kasir = (SELECT kasir FROM penjualan WHERE id_penjualan = ?)");
            $updShift->bind_param("di", $net_cash, $id_penjualan);
            $updShift->execute();
        }

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
