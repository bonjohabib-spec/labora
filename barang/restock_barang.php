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

// Ambil data varian + barang
$sql = "SELECT b.id_barang, b.nama_barang, v.warna, v.ukuran, v.harga_beli, v.stok 
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

// Proses Restock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty_tambah = intval($_POST['qty_tambah']);
    $harga_beli_baru = !empty($_POST['harga_beli_baru']) ? intval($_POST['harga_beli_baru']) : $data['harga_beli'];
    $keterangan = trim($_POST['keterangan']) ?: "Restock barang via menu Restock Cepat";

    if ($qty_tambah <= 0) {
        $error = "Jumlah penambahan harus lebih dari 0!";
    } else {
        $conn->begin_transaction();
        try {
            $stok_akhir = $data['stok'] + $qty_tambah;
            
            // Update Stok & Harga Beli Terakhir
            $stmt = $conn->prepare("UPDATE barang_varian SET stok = ?, harga_beli = ? WHERE id_varian = ?");
            $stmt->bind_param("iii", $stok_akhir, $harga_beli_baru, $id_varian);
            $stmt->execute();

            // Catat Riwayat
            catat_riwayat_stok($conn, $data['id_barang'], $id_varian, $qty_tambah, 'penambahan', $keterangan);

            $conn->commit();
            echo "<script>alert('Berhasil menambah stok!'); window.location='stok_barang.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal memperbarui stok: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Restock Barang - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
    <style>
        .restock-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .item-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .item-info h2 { margin: 0 0 10px 0; color: #0f172a; font-size: 1.2rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item { display: flex; flex-direction: column; }
        .info-item label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item span { font-weight: 600; color: #334155; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #334155; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus { outline: none; border-color: #6366f1; ring: 2px solid #e0e7ff; }
        
        .btn-group { display: flex; gap: 10px; margin-top: 30px; }
        .btn-save { 
            flex: 2; border: none; padding: 14px; border-radius: 8px; color: white;
            font-weight: 600; cursor: pointer; background: #6366f1; transition: 0.2s;
        }
        .btn-save:hover { background: #4f46e5; }
        .btn-cancel { 
            flex: 1; border: 1px solid #cbd5e1; padding: 14px; border-radius: 8px;
            color: #64748b; text-align: center; text-decoration: none; font-weight: 500; transition: 0.2s;
        }
        .btn-cancel:hover { background: #f1f5f9; }
    </style>
</head>
<body>
<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="page-content">
            <div class="restock-container">
                <div class="section-header">
                    <h1 style="font-size: 1.5rem; margin-bottom: 20px;">➕ Restock Barang</h1>
                </div>

                <div class="item-info">
                    <h2><?= htmlspecialchars($data['nama_barang']) ?></h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Varian</label>
                            <span><?= htmlspecialchars($data['warna']) ?> / <?= htmlspecialchars($data['ukuran']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Stok Sekarang</label>
                            <span style="color: <?= $data['stok'] <= 0 ? '#dc2626' : '#334155' ?>"><?= $data['stok'] ?> Unit</span>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Jumlah Tambahan (Unit)</label>
                        <input type="number" name="qty_tambah" placeholder="Contoh: 50" required autofocus min="1">
                    </div>

                    <div class="form-group">
                        <label>Harga Beli Per Unit (Opsional)</label>
                        <input type="number" name="harga_beli_baru" value="<?= $data['harga_beli'] ?>" placeholder="Biarkan jika harga sama">
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">* Mengupdate harga modal terakhir untuk barang ini.</p>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" rows="2" placeholder="Contoh: Belanja stok mingguan dari supplier X"></textarea>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn-save">Simpan Stok Baru</button>
                        <a href="stok_barang.php" class="btn-cancel">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
