<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$kasir = $_SESSION['username'] ?? 'owner';
$active_shift = getActiveShift($conn, $kasir);

// Ambil daftar piutang (sisa_piutang > 0)
$q = $conn->query("
    SELECT * FROM penjualan 
    WHERE sisa_piutang > 0 
    AND status = 'selesai' 
    ORDER BY tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Piutang - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .piutang-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .debt-table { width: 100%; border-collapse: collapse; }
        .debt-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 13px; }
        .debt-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .badge-debt {
            background: #fff7ed; color: #c2410c; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px;
        }
        .btn-pay {
            background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;
        }
        .btn-pay:hover { background: #2563eb; }
        .empty-debt { text-align: center; padding: 50px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>
            
            <div class="page-content">
                <div class="welcome-header">
                    <h1>💰 Manajemen Piutang</h1>
                    <p>Daftar transaksi pelanggan yang belum lunas atau masih mencicil.</p>
                </div>

                <div class="piutang-card">
                    <table class="debt-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total Belanja</th>
                                <th>Sisa Hutang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q->num_rows == 0): ?>
                                <tr>
                                    <td colspan="6" class="empty-debt">🎉 Alhamdulillah, semua piutang sudah lunas!</td>
                                </tr>
                            <?php else: ?>
                                <?php while($p = $q->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color: #3b82f6;">#INV-<?= $p['id_penjualan'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                                    <td><strong><?= htmlspecialchars($p['pelanggan'] ?: '-') ?></strong></td>
                                    <td>Rp <?= number_format($p['total'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge-debt">Rp <?= number_format($p['sisa_piutang'], 0, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <a href="penjualan_detail.php?id=<?= $p['id_penjualan'] ?>&action=bayar" class="btn-pay">💳 Bayar / Cicil</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
