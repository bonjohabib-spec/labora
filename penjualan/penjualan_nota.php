<?php
include __DIR__ . '/../includes/koneksi.php';
$id_penjualan = intval($_GET['id'] ?? 0);
if (!$id_penjualan) die("ID tidak valid");

$p = $conn->query("SELECT * FROM penjualan WHERE id_penjualan=$id_penjualan")->fetch_assoc();
$items = $conn->query("
    SELECT d.*, b.nama_barang, v.warna, v.ukuran 
    FROM detail_penjualan d
    JOIN barang_varian v ON d.id_varian = v.id_varian
    JOIN barang b ON v.id_barang = b.id_barang
    WHERE d.id_penjualan = $id_penjualan
");

// Ambil info toko dari database pengaturan
$qStore = $conn->query("SELECT * FROM pengaturan WHERE id = 1");
$store = $qStore->fetch_assoc();

$store_name = $store['nama_toko'] ?? "LABORA POS";
$store_addr = $store['alamat'] ?? "Alamat Belum Diatur";
$store_phone = $store['telepon'] ?? "-";
$footer_nota = $store['footer_nota'] ?? "Terima Kasih";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #INV-<?= $id_penjualan ?></title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 70mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .meta { font-size: 11px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 2px 0; vertical-align: top; }
        .total-row td { font-weight: bold; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="header text-center">
        <h2><?= $store_name ?></h2>
        <p><?= $store_addr ?></p>
        <p>Telp: <?= $store_phone ?></p>
    </div>
    
    <div class="divider"></div>
    
    <div class="meta">
        <div>Inv: #INV-<?= $id_penjualan ?></div>
        <div>Tgl: <?= date('d/m/Y H:i', strtotime($p['tanggal'])) ?></div>
        <div>Ksr: <?= htmlspecialchars($p['kasir']) ?></div>
        <div>Plg: <?= htmlspecialchars($p['pelanggan']) ?></div>
    </div>
    
    <div class="divider"></div>
    
    <table>
        <?php while($item = $items->fetch_assoc()): ?>
        <tr>
            <td colspan="2"><?= htmlspecialchars($item['nama_barang']) ?> (<?= $item['warna'] ?>/<?= $item['ukuran'] ?>)</td>
        </tr>
        <tr>
            <td><?= $item['qty'] ?> x <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
            <td class="text-right"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <div class="divider"></div>
    
    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-right">Rp <?= number_format($p['total'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>BAYAR</td>
            <td class="text-right">Rp <?= number_format($p['bayar'], 0, ',', '.') ?></td>
        </tr>
        <tr class="total-row">
            <td>KEMBALI</td>
            <td class="text-right">Rp <?= number_format($p['kembali'], 0, ',', '.') ?></td>
        </tr>
    </table>
    
    <div class="divider"></div>
    <div class="text-center" style="margin-top: 10px;">
        *** TERIMA KASIH ***<br>
        <?= nl2br(htmlspecialchars($footer_nota)) ?>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Cetak Ulang</button>
        <button onclick="window.close()">Tutup</button>
    </div>
</body>
</html>
