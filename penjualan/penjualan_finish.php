<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$id_penjualan = intval($_GET['id'] ?? 0);
if (!$id_penjualan) {
    header("Location: penjualan.php");
    exit;
}

$q = $conn->query("SELECT * FROM penjualan WHERE id_penjualan=$id_penjualan");
$p = $q->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Berhasil - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/penjualan_finish.css?v=<?= time() ?>">
</head>
</head>
<body>
    <div class="finish-container">
        <div class="success-card">
            <div class="icon-check">✓</div>
            <h2 style="margin-bottom: 5px;">Transaksi Berhasil!</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Nomor Invoice: <strong>#INV-<?= $id_penjualan ?></strong></p>
            
            <div class="info-row">
                <span>Total Belanja</span>
                <strong>Rp <?= number_format($p['total'], 0, ',', '.') ?></strong>
            </div>
            <div class="info-row">
                <span>Tunai (Bayar)</span>
                <strong>Rp <?= number_format($p['bayar'], 0, ',', '.') ?></strong>
            </div>
            <div class="info-row" style="color: #10b981; border-bottom: none;">
                <span>Kembalian</span>
                <strong style="font-size: 18px;">Rp <?= number_format($p['kembali'], 0, ',', '.') ?></strong>
            </div>

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
