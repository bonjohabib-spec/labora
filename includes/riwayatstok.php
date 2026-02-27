<?php
function catat_riwayat_stok($conn, $id_barang, $id_varian, $jumlah, $tipe, $keterangan, $user = null) {
    // 🔒 Cegah data kosong yang bisa bikin error
    if (empty($id_barang) || !is_numeric($id_barang)) {
        error_log("Lewati pencatatan stok karena id_barang kosong atau invalid (ket: $keterangan)");
        return false;
    }

    if (empty($jumlah) || empty($tipe)) {
        error_log("Lewati pencatatan stok karena jumlah/tipe kosong (ket: $keterangan)");
        return false;
    }

    if ($id_varian === "") $id_varian = null;
    if ($user === "") $user = null;

    if (session_status() == PHP_SESSION_NONE) session_start();
    $pengguna = $user ?? ($_SESSION['username'] ?? 'system');

    $keterangan_lengkap = $keterangan . " (oleh $pengguna)";

    $sql = "INSERT INTO riwayat_stok (id_barang, id_varian, jumlah, tipe, keterangan)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Gagal prepare riwayat stok: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iiiss", $id_barang, $id_varian, $jumlah, $tipe, $keterangan_lengkap);
    $hasil = $stmt->execute();

    if (!$hasil) {
        error_log("Gagal insert riwayat stok: " . $stmt->error);
        return false;
    }

    return true;
}
?>
