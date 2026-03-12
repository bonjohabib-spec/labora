<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$id_penjualan = intval($_GET['id'] ?? 0);
if (!$id_penjualan) {
    header("Location: penjualan.php");
    exit;
}

// Ambil data penjualan dengan prepared statement
$stmt = $conn->prepare("SELECT * FROM penjualan WHERE id_penjualan = ?");
$stmt->bind_param("i", $id_penjualan);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    header("Location: penjualan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil - LABORA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/penjualan_finish.css?v=<?= time() ?>">
</head>
<body>
    <div class="finish-container">
        <div class="success-card">
            <div class="icon-check">✓</div>
            <h2 style="margin-bottom: 5px;">Transaksi Berhasil!</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Nomor Invoice: <strong>#INV-<?= $id_penjualan ?></strong></p>
            
            <div class="info-row">
                <span>Metode Pembayaran</span>
                <span class="badge" style="background: <?= $p['metode_pembayaran'] == 'tunai' ? '#ecfdf5' : '#fffbeb' ?>; color: <?= $p['metode_pembayaran'] == 'tunai' ? '#059669' : '#92400e' ?>; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase;">
                    <?= $p['metode_pembayaran'] ?>
                </span>
            </div>
            <div class="info-row">
                <span>Total Belanja</span>
                <strong>Rp <?= number_format($p['total'], 0, ',', '.') ?></strong>
            </div>
            <div class="info-row">
                <span><?= $p['metode_pembayaran'] == 'tunai' ? 'Tunai (Bayar)' : 'Dibayar (DP)' ?></span>
                <strong>Rp <?= number_format($p['bayar'], 0, ',', '.') ?></strong>
            </div>
            
            <?php if ($p['metode_pembayaran'] == 'tunai'): ?>
                <div class="info-row" style="color: #10b981; border-bottom: none;">
                    <span>Kembalian</span>
                    <strong style="font-size: 18px;">Rp <?= number_format($p['kembali'], 0, ',', '.') ?></strong>
                </div>
            <?php else: ?>
                <div class="info-row" style="color: #ef4444; border-bottom: none;">
                    <span>Sisa Hutang</span>
                    <strong style="font-size: 18px;">Rp <?= number_format($p['sisa_piutang'], 0, ',', '.') ?></strong>
                </div>
            <?php endif; ?>

            <div class="btn-group">
                <button onclick="printNota()" class="btn btn-print">🖨️ CETAK NOTA (ENTER)</button>
                <form action="penjualan.php" method="POST" id="formNew">
                    <input type="hidden" name="buat_transaksi" value="1">
                    <input type="hidden" name="pelanggan" value="-">
                    <button type="submit" class="btn btn-new" style="width: 100%;">🔄 TRANSAKSI BARU (ESC)</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function printNota() {
            const width = 400;
            const height = 600;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;
            window.open('penjualan_nota.php?id=<?= $id_penjualan ?>', 'PrintNota', `width=${width},height=${height},left=${left},top=${top}`);
        }

        // Shortcut
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                printNota();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                document.getElementById('formNew').submit();
            }
        });
    </script>
</body>
</html>
