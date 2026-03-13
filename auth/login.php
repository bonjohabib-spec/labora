<?php
session_start();
include __DIR__ . '/../includes/koneksi.php';


if (isset($_SESSION['username'])) {
    if ($_SESSION['user_role'] == 'kasir') {
        header("Location: ../penjualan/penjualan.php");
    } else {
        header("Location: ../dashboard/dashboard.php");
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data && password_verify($password, $data['password'])) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['user_role'] = $data['role'];
        
        if ($_SESSION['user_role'] == 'kasir') {
            header("Location: ../penjualan/penjualan.php");
        } else {
            header("Location: ../dashboard/dashboard.php");
        }
        exit;
    } else {
        $error = "❌ Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LABORA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <!-- LEFT SECTION: FORM -->
        <div class="form-section">
            <div class="login-header">
                <img src="../assets/img/logo-labora.png" alt="LABORA Logo" class="brand-logo">
            </div>
            <div class="form-wrapper">
                <h2>Log In to Your Shift</h2>
                <p class="subtitle">Welcome back! Please enter your details to continue.</p>

                <?php if($error): ?>
                    <div class="error-box"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-field">
                        <label>Username</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-regular fa-user"></i>
                            <input type="text" name="username" placeholder="Enter your username" required autofocus>
                        </div>
                    </div>

                    <div class="input-field">
                        <div class="label-row">
                            <label>Password</label>
                            <a href="#" class="forgot-link">Forgot Password?</a>
                        </div>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember Me</label>
                    </div>

                    <button type="submit" name="login" class="btn-signin">Sign In</button>
                </form>
            </div>
        </div>

        <!-- RIGHT SECTION: DECORATIVE -->
        <div class="side-panel">
            <div class="side-content">
                <div class="box-icon-wrapper">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h1>Hello, Friend!</h1>
                <h3>Start your shift with LABORA</h3>
                <p>Simplify operations, manage inventory, and deliver exceptional service.</p>
            </div>
        </div>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html>
