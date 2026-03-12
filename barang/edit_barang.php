<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/riwayatstok.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Proteksi role hanya untuk owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: ../index.php");
    exit();
}

$id_varian = intval($_GET['id'] ?? 0);
if ($id_varian == 0) {
    header("Location: stok_barang.php");
    exit();
}

// Ambil data varian + barang + cek transaksi dalam satu query efisien
$sql = "SELECT b.id_barang, b.nama_barang, b.stok_min, b.stok_max, 
               v.warna, v.ukuran, v.harga_beli, v.harga_jual, v.stok,
               (SELECT COUNT(*) FROM detail_penjualan WHERE id_varian = v.id_varian) as total_jual
        FROM barang b 
        JOIN barang_varian v ON b.id_barang = v.id_barang 
        WHERE v.id_varian = ?";
$stmtFetch = $conn->prepare($sql);
$stmtFetch->bind_param("i", $id_varian);
$stmtFetch->execute();
$data = $stmtFetch->get_result()->fetch_assoc();

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='stok_barang.php';</script>";
    exit;
}

$sudahDijual = (int)$data['total_jual'] > 0;

// Proses Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $warna       = trim($_POST['warna']);
    $ukuran      = trim($_POST['ukuran']);
    $harga_beli  = intval($_POST['harga_beli']);
    $harga_jual  = intval($_POST['harga_jual']);
    $stok_baru   = intval($_POST['stok']);
    $stok_min    = intval($_POST['stok_min']);
    $stok_max    = intval($_POST['stok_max']);

    $conn->begin_transaction();
    try {
        // Jika sudah terjual, field inti (nama, warna, ukuran, harga_beli, stok) tidak boleh diubah
        // Tapi kita tetap izinkan ubah harga_jual, stok_min, dan stok_max demi fleksibilitas
        if ($sudahDijual) {
            $stmt = $conn->prepare("UPDATE barang SET stok_min = ?, stok_max = ? WHERE id_barang = ?");
            $stmt->bind_param("iii", $stok_min, $stok_max, $data['id_barang']);
            $stmt->execute();

            $stmt2 = $conn->prepare("UPDATE barang_varian SET harga_jual = ? WHERE id_varian = ?");
            $stmt2->bind_param("ii", $harga_jual, $id_varian);
            $stmt2->execute();
        } else {
            // Jika belum terjual, bebas edit semua
            $stmt = $conn->prepare("UPDATE barang SET nama_barang = ?, stok_min = ?, stok_max = ? WHERE id_barang = ?");
            $stmt->bind_param("siii", $nama_barang, $stok_min, $stok_max, $data['id_barang']);
            $stmt->execute();

            $stmt2 = $conn->prepare("UPDATE barang_varian SET warna = ?, ukuran = ?, harga_beli = ?, harga_jual = ?, stok = ? WHERE id_varian = ?");
            $stmt2->bind_param("ssiiii", $warna, $ukuran, $harga_beli, $harga_jual, $stok_baru, $id_varian);
            $stmt2->execute();

            // Catat jika ada perubahan stok
            if ($stok_baru != $data['stok']) {
                $selisih = $stok_baru - $data['stok'];
                $tipe = ($selisih > 0) ? 'penambahan' : 'pengurangan';
                catat_riwayat_stok($conn, $data['id_barang'], $id_varian, abs($selisih), $tipe, "Penyesuaian stok via Edit Barang");
            }
        }

        $conn->commit();
        echo "<script>alert('Berhasil diperbarui!'); window.location='stok_barang.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Barang - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/tambah_barang.css?v=<?= time() ?>">
</head>
<body>
<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="page-content">
            <div style="margin-bottom: 20px;">
                <a href="stok_barang.php" class="btn-back" style="text-decoration: none; color: #94a3b8; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                    <span>←</span> Kembali ke Daftar Stok
                </a>
            </div>
            
            <section class="form-section">
                <div class="section-header">
                    <h1>Edit Barang</h1>
                    <?php if ($sudahDijual): ?>
                        <span class="badge-locked">🔒 Mode Terbatas (Barang sudah pernah terjual)</span>
                    <?php endif; ?>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" class="form-barang">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Barang <span class="required">*</span></label>
                            <input type="text" name="nama_barang" value="<?= htmlspecialchars($data['nama_barang']) ?>" <?= $sudahDijual ? 'readonly' : 'required' ?>>
                        </div>

                        <div class="form-group">
                            <label>Warna</label>
                            <input type="text" name="warna" value="<?= htmlspecialchars($data['warna']) ?>" <?= $sudahDijual ? 'readonly' : '' ?>>
                        </div>

                        <div class="form-group">
                            <label>Ukuran</label>
                            <input type="text" name="ukuran" value="<?= htmlspecialchars($data['ukuran']) ?>" <?= $sudahDijual ? 'readonly' : '' ?>>
                        </div>

                        <div class="form-group">
                            <label>Harga Beli (Modal)</label>
                            <input type="number" name="harga_beli" value="<?= $data['harga_beli'] ?>" <?= $sudahDijual ? 'readonly' : '' ?> inputmode="decimal">
                        </div>

                        <div class="form-group">
                            <label>Harga Jual</label>
                            <input type="number" name="harga_jual" value="<?= $data['harga_jual'] ?>" required inputmode="decimal">
                        </div>

                        <div class="form-group">
                            <label>Stok Saat Ini</label>
                            <input type="number" name="stok" value="<?= $data['stok'] ?>" <?= $sudahDijual ? 'readonly' : 'required' ?> inputmode="numeric">
                        </div>

                        <div class="form-group">
                            <label>Stok Minimum</label>
                            <input type="number" name="stok_min" value="<?= $data['stok_min'] ?>" inputmode="numeric">
                        </div>

                        <div class="form-group">
                            <label>Stok Maksimum</label>
                            <input type="number" name="stok_max" value="<?= $data['stok_max'] ?>" inputmode="numeric">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">💾 Simpan Perubahan</button>
                        <button type="button" class="btn-outline" onclick="window.location.href='stok_barang.php'">Batal</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
</body>
</html>
