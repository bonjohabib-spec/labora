<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$kasir = $_SESSION['username'] ?? 'owner';
$active_shift = getActiveShift($conn, $kasir);

if (!$active_shift) {
    header("Location: dashboard.php");
    exit();
}

$id_s = $active_shift['id_shift'];
$waktu_buka = $active_shift['waktu_buka'];

// 1. Hitung Penjualan Tunai di shift ini (Bagian DP / Lunas Awal)
$qTunai = $conn->query("SELECT SUM(bayar - kembali) as total FROM penjualan WHERE id_shift = $id_s AND metode_pembayaran = 'tunai' AND status = 'selesai'");
$jual_tunai = $qTunai->fetch_assoc()['total'] ?? 0;

// 2. Hitung Penjualan Transfer di shift ini (Bagian DP / Lunas Awal)
$qTransfer = $conn->query("SELECT SUM(bayar - kembali) as total FROM penjualan WHERE id_shift = $id_s AND metode_pembayaran = 'transfer' AND status = 'selesai'");
$jual_transfer = $qTransfer->fetch_assoc()['total'] ?? 0;

// 3. Hitung Pelunasan Cicilan Piutang di shift ini
$qPiutangTunai = $conn->query("SELECT SUM(nominal) as total FROM pembayaran_piutang WHERE id_shift = $id_s AND metode_pembayaran = 'tunai'");
$piutang_tunai = $qPiutangTunai->fetch_assoc()['total'] ?? 0;

$qPiutangTransfer = $conn->query("SELECT SUM(nominal) as total FROM pembayaran_piutang WHERE id_shift = $id_s AND metode_pembayaran = 'transfer'");
$piutang_transfer = $qPiutangTransfer->fetch_assoc()['total'] ?? 0;

// 4. Perhitungan Total Uang Fisik yang harus ada (Modal Receh + Tunai + Pelunasan Tunai)
$total_seharusnya = $active_shift['saldo_awal'] + $jual_tunai + $piutang_tunai;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tutup_shift'])) {
    $saldo_fisik = floatval($_POST['saldo_fisik']);
    $saldo_sistem = $total_seharusnya;
    $selisih = $saldo_fisik - $saldo_sistem;
    
    // Update Shift degan rincian lengkap
    $stmt = $conn->prepare("UPDATE kas_shift SET 
        waktu_tutup = NOW(), 
        omset_tunai = ?, 
        omset_transfer = ?, 
        piutang_tunai = ?, 
        piutang_transfer = ?, 
        saldo_akhir_sistem = ?, 
        saldo_akhir_fisik = ?, 
        selisih = ?, 
        status = 'closed' 
        WHERE id_shift = ?");
    
    $total_uang_masuk_laci = $jual_tunai + $piutang_tunai; // Hanya uang fisik baru (tanpa saldo awal)
    $stmt->bind_param("dddddddi", 
        $jual_tunai, 
        $jual_transfer, 
        $piutang_tunai, 
        $piutang_transfer, 
        $total_uang_masuk_laci, 
        $saldo_fisik, 
        $selisih, 
        $active_shift['id_shift']
    );
    
    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutup Kasir - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <style>
    .tutup-box {
      max-width: 500px;
      margin: 60px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 25px rgba(0,0,0,0.1);
    }
    @media (max-width: 768px) {
        .tutup-box {
            width: 98%;
            margin: 10px auto;
            padding: 15px;
            border-radius: 0;
            box-shadow: none;
        }
    }
    .tutup-box h1 { font-size: 22px; color: #1e293b; text-align: center; margin-bottom: 25px; }
    .summary-card {
        background: #f8fafc;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
    }
    .summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; border-bottom: 1px dashed #e2e8f0; }
    .summary-row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; margin-top: 5px; color: #1e293b; }
    
    /* Kalkulator Denominasi */
    .denom-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 25px;
        background: #f1f5f9;
        padding: 15px;
        border-radius: 12px;
    }
    .denom-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .denom-item label { font-size: 11px; font-weight: 700; color: #64748b; }
    .denom-item input { 
        padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-weight: 700; text-align: center;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-group input { width: 100%; padding: 14px; border: 1px solid #3b82f6; border-radius: 8px; font-size: 20px; font-weight: 800; text-align: center; color: #1e293b; background: #fff; }
    .btn-close { width: 100%; padding: 15px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 16px; margin-top: 10px; }
    .btn-close:hover { background: #dc2626; }
    .info-msg { padding: 12px; background: #fffbeb; color: #b45309; border-radius: 8px; font-size: 12px; margin-bottom: 20px; text-align: center; border: 1px solid #fde68a; }
  </style>
</head>
<body style="background: #f1f5f9;">
  <div class="tutup-box">
    <h1>🏁 Tutup Kasir / Akhiri Shift</h1>
    
    <div class="info-msg">
        <strong>PENTING:</strong> Harap hitung manual seluruh uang fisik di laci saat ini. Masukkan jumlah lembar pada kalkulator denominasi untuk mempermudah.
    </div>

    <?php if ($_SESSION['user_role'] === 'owner'): ?>
    <div class="summary-card">
        <div style="font-size: 12px; font-weight: 700; color: #3b82f6; margin-bottom: 10px; text-transform: uppercase;">📊 Rincian Sistem (Owner Only)</div>
        <div class="summary-row">
            <span>Modal Awal (Receh)</span>
            <span>Rp <?= number_format($active_shift['saldo_awal'], 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span style="color: #64748b; font-size: 13px;">↳ Penjualan Tunai</span>
            <span style="color: #64748b;">Rp <?= number_format($jual_tunai, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span style="color: #64748b; font-size: 13px;">↳ Penjualan Transfer</span>
            <span style="color: #64748b;">Rp <?= number_format($jual_transfer, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span style="color: #64748b; font-size: 13px;">↳ Pelunasan Cicilan (Tunai)</span>
            <span style="color: #64748b;">Rp <?= number_format($piutang_tunai, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row" style="border-top: 1px solid #cbd5e1; padding-top: 12px;">
            <span>TOTAL SEHARUSNYA (FISIK)</span>
            <div style="text-align: right;">
                <div style="font-size: 16px; color: #1e293b;">Rp <?= number_format($total_seharusnya, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="denom-container">
        <div style="grid-column: span 2; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase;">🧮 Kalkulator Lembaran / Koin</div>
        <?php 
        $denoms = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100];
        foreach ($denoms as $d): ?>
            <div class="denom-item">
                <label><?= number_format($d, 0, ',', '.') ?></label>
                <input type="number" class="input-denom" data-val="<?= $d ?>" placeholder="0" min="0" onfocus="this.select()" inputmode="numeric">
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST">
      <div class="form-group">
        <label>💵 TOTAL UANG FISIK DI LACI</label>
        <input type="text" id="saldo_fisik_display" placeholder="Rp 0" required onfocus="this.select()" inputmode="decimal">
        <input type="hidden" name="saldo_fisik" id="saldo_fisik_real">
      </div>
      <button type="submit" name="tutup_shift" class="btn-close" onclick="return confirm('Apakah Anda yakin ingin mengakhiri shift ini? Selisih uang akan tercatat secara otomatis.')">🛑 TUTUP KASIR & KELUAR</button>
      <a href="dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: #64748b; font-size: 13px; text-decoration: none;">Batal, Kembali ke Dashboard</a>
    </form>
  </div>

  <script>
    const displayInput = document.getElementById('saldo_fisik_display');
    const realInput = document.getElementById('saldo_fisik_real');
    const denomInputs = document.querySelectorAll('.input-denom');

    function updateFromDenom() {
        let total = 0;
        denomInputs.forEach(input => {
            const val = parseInt(input.dataset.val);
            const qty = parseInt(input.value) || 0;
            total += (val * qty);
        });
        
        realInput.value = total;
        displayInput.value = "Rp " + new Intl.NumberFormat('id-ID').format(total);
    }

    denomInputs.forEach(input => {
        input.addEventListener('input', updateFromDenom);
    });

    displayInput.addEventListener('input', function(e) {
        let value = this.value.replace(/[^\d]/g, "");
        if (value === "") {
            this.value = "";
            realInput.value = "";
            return;
        }
        
        realInput.value = value;
        this.value = "Rp " + new Intl.NumberFormat('id-ID').format(value);
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        if (!realInput.value || realInput.value === "0") {
            realInput.value = "0";
        }
    });
  </script>
</body>
</html>
