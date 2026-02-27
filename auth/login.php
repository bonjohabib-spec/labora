<?php
session_start();
include __DIR__ . '/../includes/koneksi.php';


if (isset($_SESSION['username'])) {
    header("Location: ../dashboard/dashboard.php");
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

    if ($data && $data['password'] === $password) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['user_role'] = $data['role'];
        header("Location: ../dashboard/dashboard.php");
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
    <title>Login - LABORA</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body>
    <div class="container" id="container">
        <!-- SIGN UP -->
        <div class="form-container sign-up-container">
            <form method="POST">
                <h1>Sign Up</h1>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="register">Sign Up</button>
            </form>
        </div>

        <!-- SIGN IN -->
        <div class="form-container sign-in-container">
            <form method="POST">
                <h1>Sign In</h1>
                <?php if($error): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>

        <!-- Overlay -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>Silakan login dengan info akun Anda</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Masukkan detail Anda untuk memulai</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/login.js"></script>
</body>
</html>
