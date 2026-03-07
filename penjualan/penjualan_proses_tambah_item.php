<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_penjualan = intval($_POST['id_penjualan']);
    $id_varian    = intval($_POST['id_varian']);
    $qty          = intval($_POST['qty']);
    $pelanggan    = isset($_POST['pelanggan']) ? trim($_POST['pelanggan']) : null;

    if ($qty <= 0) {
        die("Jumlah tidak valid!");
    }

    // 🔍 Ambil data varian (id_barang, harga_jual, harga_beli, stok, nama_barang, warna, ukuran)
    $qVar = $conn->prepare("
        SELECT v.id_varian, v.id_barang, v.stok, v.harga_jual, v.harga_beli,
               v.warna, v.ukuran, b.nama_barang
        FROM barang_varian v
        JOIN barang b ON v.id_barang = b.id_barang
        WHERE v.id_varian = ? AND v.status = 'aktif'
    ");
    $qVar->bind_param("i", $id_varian);
    $qVar->execute();
    $varian = $qVar->get_result()->fetch_assoc();

    if (!$varian) die("Varian barang tidak ditemukan!");
    if ($varian['stok'] < $qty) die("Stok tidak mencukupi!");

    $id_barang  = $varian['id_barang'];
    $harga_jual = floatval($varian['harga_jual']);
    $harga_beli = floatval($varian['harga_beli']);

    // ✅ Jika harga jual kosong di DB, ambil dari input form
    if ($harga_jual <= 0 && !empty($_POST['harga_jual'])) {
        $harga_jual = floatval($_POST['harga_jual']);
    }

    $warna    = $varian['warna'] ?? '';
    $ukuran   = $varian['ukuran'] ?? '';
    $subtotal = $qty * $harga_jual;

    // 🧩 Mulai transaksi agar data tetap konsisten
    $conn->begin_transaction();

    try {
        // 🔎 Cek apakah item dengan varian sama sudah ada di detail_penjualan (status aktif)
        $cek = $conn->prepare("
            SELECT id, qty, subtotal 
            FROM detail_penjualan 
            WHERE id_penjualan = ? AND id_varian = ? AND status='aktif'
        ");
        $cek->bind_param("ii", $id_penjualan, $id_varian);
        $cek->execute();
        $existing = $cek->get_result()->fetch_assoc();

        if ($existing) {
            // 📈 Update qty dan subtotal jika sudah ada
            $newQty = $existing['qty'] + $qty;
            $newSubtotal = $newQty * $harga_jual;

            $upd = $conn->prepare("
                UPDATE detail_penjualan 
                SET qty = ?, subtotal = ? 
                WHERE id = ?
            ");
            $upd->bind_param("idi", $newQty, $newSubtotal, $existing['id']);
            $upd->execute();
        } else {
            // ➕ Insert baru jika belum ada
            $sql = $conn->prepare("
                INSERT INTO detail_penjualan 
                (id_penjualan, id_barang, id_varian, qty, harga_jual, harga_beli, subtotal, status, warna, ukuran)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, ?)
            ");
            $sql->bind_param("iiiidddss", 
                $id_penjualan, $id_barang, $id_varian, 
                $qty, $harga_jual, $harga_beli, $subtotal, $warna, $ukuran
            );
            $sql->execute();
        }

        // 🔁 Update total penjualan & Pelanggan
        if ($pelanggan !== null) {
            $updPel = $conn->prepare("UPDATE penjualan SET pelanggan = ?, total = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
            $updPel->bind_param("sii", $pelanggan, $id_penjualan, $id_penjualan);
            $updPel->execute();
        } else {
            $updateTotal = $conn->prepare("UPDATE penjualan SET total = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
            $updateTotal->bind_param("ii", $id_penjualan, $id_penjualan);
            $updateTotal->execute();
        }

        $conn->commit();

        // ✅ Redirect kembali ke halaman transaksi
        header("Location: penjualan_tambah.php?id=$id_penjualan");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Gagal menambahkan item: " . $e->getMessage());
    }
}
?>
