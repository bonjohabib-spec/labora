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

// 1. Hitung Penjualan Tunai di shift ini
$qTunai = $conn->query("SELECT SUM(total - sisa_piutang) as total FROM penjualan WHERE id_shift = $id_s AND metode_pembayaran = 'tunai' AND status = 'selesai'");
$jual_tunai = $qTunai->fetch_assoc()['total'] ?? 0;

// 2. Hitung Penjualan Transfer di shift ini
$qTransfer = $conn->query("SELECT SUM(total - sisa_piutang) as total FROM penjualan WHERE id_shift = $id_s AND metode_pembayaran = 'transfer' AND status = 'selesai'");
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
    
    // Update Shift
    $stmt = $conn->prepare("UPDATE kas_shift SET waktu_tutup = NOW(), saldo_akhir_sistem = ?, saldo_akhir_fisik = ?, selisih = ?, status = 'closed' WHERE id_shift = ?");
    $total_uang_masuk_laci = $jual_tunai + $piutang_tunai; // Hanya uang fisik
    $stmt->bind_param("dddi", $total_uang_masuk_laci, $saldo_fisik, $selisih, $active_shift['id_shift']);
    
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
    .tutup-box h1 { font-size: 22px; color: #1e293b; text-align: center; margin-bottom: 25px; }
    .summary-card {
        background: #f8fafc;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    .summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; border-bottom: 1px dashed #e2e8f0; }
    .summary-row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; margin-top: 5px; color: #1e293b; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-group input { width: 100%; padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 18px; font-weight: 800; text-align: center; color: #1e293b; }
    .btn-close { width: 100%; padding: 15px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 16px; margin-top: 10px; }
    .btn-close:hover { background: #dc2626; }
    .info-msg { padding: 12px; background: #eff6ff; color: #3b82f6; border-radius: 8px; font-size: 12px; margin-bottom: 20px; text-align: center; }
  </style>
</head>
<body style="background: #f1f5f9;">
  <div class="tutup-box">
    <h1>🏁 Tutup Kasir / Akhiri Shift</h1>
    
    <div class="info-msg">
        Harap hitung seluruh uang fisik (kertas & koin) yang ada di laci kasir saat ini.
    </div>

    <div class="summary-card">
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
        <div class="summary-row">
            <span style="color: #64748b; font-size: 13px;">↳ Pelunasan Cicilan (Transfer)</span>
            <span style="color: #64748b;">Rp <?= number_format($piutang_transfer, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row" style="border-top: 1px solid #cbd5e1; padding-top: 12px;">
            <span>TOTAL UANG FISIK (DI LACI)</span>
            <div style="text-align: right;">
                <div style="font-size: 16px;">Rp <?= number_format($total_seharusnya, 0, ',', '.') ?></div>
                <small style="font-weight: 400; color: #94a3b8; font-size: 10px;">(Modal + Jual Tunai + Pelunasan Tunai)</small>
            </div>
        </div>
    </div>

    <form method="POST">
      <div class="form-group">
        <label>💵 NILAI UANG FISIK DI LACI (INPUT MANUAL)</label>
        <input type="text" id="saldo_fisik_display" placeholder="Contoh: 50.000" required autofocus>
        <input type="hidden" name="saldo_fisik" id="saldo_fisik_real">
      </div>
      <button type="submit" name="tutup_shift" class="btn-close" onclick="return confirm('Apakah Bapak yakin ingin mengakhiri shift ini?')">🛑 TUTUP KASIR & KELUAR</button>
      <a href="dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: #64748b; font-size: 13px; text-decoration: none;">Batal, Kembali ke Dashboard</a>
    </form>
  </div>

  <script>
    const displayInput = document.getElementById('saldo_fisik_display');
    const realInput = document.getElementById('saldo_fisik_real');

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
