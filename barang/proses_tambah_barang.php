<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_barang = trim($_POST['nama_barang']);
  $warna       = trim($_POST['warna']);
  $ukuran      = trim($_POST['ukuran']);
  $harga_beli  = $_POST['harga_beli'] !== '' ? floatval($_POST['harga_beli']) : 0;
  $harga_jual  = $_POST['harga_jual'] !== '' ? floatval($_POST['harga_jual']) : 0;
  $stok        = intval($_POST['stok']);
  $stok_min    = $_POST['stok_min'] !== '' ? intval($_POST['stok_min']) : 10;
  $stok_max    = $_POST['stok_max'] !== '' ? intval($_POST['stok_max']) : 9999;

  if ($nama_barang === '' || $stok <= 0) {
    echo "<script>alert('Nama barang dan stok wajib diisi!'); window.history.back();</script>";
    exit;
  }

  $conn->begin_transaction();
  try {
    // 🔍 1. Dapatkan atau Buat Barang Utama
    $cekBarang = $conn->prepare("SELECT id_barang FROM barang WHERE nama_barang = ?");
    $cekBarang->bind_param("s", $nama_barang);
    $cekBarang->execute();
    $resBarang = $cekBarang->get_result();

    if ($row = $resBarang->fetch_assoc()) {
      $id_barang = $row['id_barang'];
    } else {
      $stmt = $conn->prepare("INSERT INTO barang (nama_barang) VALUES (?)");
      $stmt->bind_param("s", $nama_barang);
      $stmt->execute();
      $id_barang = $conn->insert_id;
    }

    // 🔍 2. Dapatkan atau Buat Varian
    $cekVar = $conn->prepare("SELECT id_varian, stok FROM barang_varian WHERE id_barang=? AND warna=? AND ukuran=?");
    $cekVar->bind_param("iss", $id_barang, $warna, $ukuran);
    $cekVar->execute();
    $resVar = $cekVar->get_result();

    if ($v = $resVar->fetch_assoc()) {
      $id_varian = $v['id_varian'];
      $stok_baru = $v['stok'] + $stok;
      $update = $conn->prepare("
        UPDATE barang_varian SET 
          stok = ?, 
          harga_beli = CASE WHEN ? > 0 THEN ? ELSE harga_beli END,
          harga_jual = CASE WHEN ? > 0 THEN ? ELSE harga_jual END,
          stok_min = ?, stok_max = ?
        WHERE id_varian = ?
      ");
      $update->bind_param("ididiiii", $stok_baru, $harga_beli, $harga_beli, $harga_jual, $harga_jual, $stok_min, $stok_max, $id_varian);
      $update->execute();
      $ket = "Stok varian ditambah";
    } else {
      $stmtVar = $conn->prepare("INSERT INTO barang_varian (id_barang, warna, ukuran, harga_beli, harga_jual, stok, stok_min, stok_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmtVar->bind_param("issddiii", $id_barang, $warna, $ukuran, $harga_beli, $harga_jual, $stok, $stok_min, $stok_max);
      $stmtVar->execute();
      $id_varian = $conn->insert_id;
      $ket = "Varian baru ditambahkan";
    }

    // 📜 3. Catat Riwayat
    catat_riwayat_stok($conn, $id_barang, $id_varian, $stok, 'penambahan', $ket);

    $conn->commit();
    echo "<script>alert('Berhasil disimpan!'); window.location='stok_barang.php';</script>";
  } catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Gagal: " . $e->getMessage() . "'); window.history.back();</script>";
  }
}
?>
