<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$kasir = $_SESSION['username'] ?? 'owner';

// Jika sudah ada shift terbuka, balik ke dashboard
if (getActiveShift($conn, $kasir)) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buka_shift'])) {
    $saldo_awal = floatval($_POST['saldo_awal']);
    $stmt = $conn->prepare("INSERT INTO kas_shift (kasir, waktu_buka, saldo_awal, status) VALUES (?, NOW(), ?, 'open')");
    $stmt->bind_param("sd", $kasir, $saldo_awal);
    
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
  <title>Buka Kasir - LABORA</title>
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
  <style>
    .login-box {
      max-width: 400px;
      margin: 100px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      text-align: center;
    }
    .login-box h1 { font-size: 22px; margin-bottom: 10px; color: #1e293b; }
    .login-box p { color: #64748b; font-size: 14px; margin-bottom: 30px; }
    .form-group { text-align: left; margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
    .form-group input { 
        width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 700;
        transition: border-color 0.2s;
    }
    .form-group input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .btn-block { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 15px; }
    .btn-block:hover { background: #2563eb; }
  </style>
</head>
<body style="background: #f8fafc;">
  <div class="login-box">
    <div style="font-size: 40px; margin-bottom: 15px;">🏪</div>
    <h1>Siap Berjualan hari ini?</h1>
    <p>Silakan masukkan saldo awal di laci kasir (Modal Receh) untuk memulai shift Bapak.</p>
    
    <form method="POST">
      <div class="form-group">
        <label>Saldo Awal (Modal Tunai)</label>
        <input type="text" id="saldo_awal_display" placeholder="Contoh: 50.000" required autofocus>
        <input type="hidden" name="saldo_awal" id="saldo_awal_real">
      </div>
      <button type="submit" name="buka_shift" class="btn-block">🚀 Mulai Shift Sekarang</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="../auth/logout.php" style="color: #ef4444; font-size: 13px; text-decoration: none; font-weight: 600;">Keluar / Ganti Akun</a>
    </div>
  </div>

  <script>
    const displayInput = document.getElementById('saldo_awal_display');
    const realInput = document.getElementById('saldo_awal_real');

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
            // Jika kosong, pastikan terisi 0
            realInput.value = "0";
        }
    });
  </script>
</body>
</html>
