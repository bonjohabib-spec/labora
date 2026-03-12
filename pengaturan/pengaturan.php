<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// Cek login
if (!isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// 1. Ambil data pengaturan toko
$qStore = $conn->query("SELECT * FROM pengaturan WHERE id = 1");
$store = $qStore->fetch_assoc();

// 2. Proses Simpan / Aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Update Profil Toko
    if (isset($_POST['update_store'])) {
        $nama   = $_POST['nama_toko'];
        $alamat = $_POST['alamat'];
        $telp   = $_POST['telepon'];
        $footer = $_POST['footer_nota'];

        $stmt = $conn->prepare("UPDATE pengaturan SET nama_toko=?, alamat=?, telepon=?, footer_nota=? WHERE id=1");
        $stmt->bind_param("ssss", $nama, $alamat, $telp, $footer);
        
        if ($stmt->execute()) {
            $success = "Profil toko berhasil diperbarui!";
            // Refresh data
            $qStore = $conn->query("SELECT * FROM pengaturan WHERE id = 1");
            $store = $qStore->fetch_assoc();
        } else {
            $error = "Gagal memperbarui profil toko.";
        }
    }

    // B. Update Keamanan (Password Sendiri)
    if (isset($_POST['update_password'])) {
        $new_pass = $_POST['new_password'];
        if (!empty($new_pass)) {
            $user = $_SESSION['username'];
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $new_pass, $user);
            if ($stmt->execute()) {
                $success = "Password berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui password.";
            }
        } else {
            $error = "Password baru tidak boleh kosong.";
        }
    }

    // C. Tambah User (Hanya Owner)
    if (isset($_POST['add_user']) && $_SESSION['user_role'] === 'owner') {
        $user_new = $_POST['username_new'];
        $pass_new = $_POST['password_new'];
        $role_new = $_POST['role_new'];
        
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $user_new);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah terdaftar!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $user_new, $pass_new, $role_new);
            if ($stmt->execute()) {
                $success = "User baru berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user.";
            }
        }
    }
}

// D. Hapus User (Hanya Owner)
if (isset($_GET['hapus_user']) && $_SESSION['user_role'] === 'owner') {
    $id_hapus = intval($_GET['hapus_user']);
    $currentUser = $_SESSION['username'];
    
    // Ambil detail user yang akan dihapus
    $stmtUser = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $id_hapus);
    $stmtUser->execute();
    $userToDelete = $stmtUser->get_result()->fetch_assoc();

    if ($userToDelete) {
        if ($userToDelete['username'] === $currentUser) {
            $error = "Anda tidak bisa menghapus akun sendiri!";
        } else {
            $oldUsername = $userToDelete['username'];

            $conn->begin_transaction();
            try {
                // Re-assign semua data terkait ke user yang sedang login
                $upd1 = $conn->prepare("UPDATE pengeluaran SET dibuat_oleh = ? WHERE dibuat_oleh = ?");
                $upd1->bind_param("ss", $currentUser, $oldUsername);
                $upd1->execute();

                $upd2 = $conn->prepare("UPDATE penjualan SET kasir = ? WHERE kasir = ?");
                $upd2->bind_param("ss", $currentUser, $oldUsername);
                $upd2->execute();
                
                // Baru hapus user
                $del = $conn->prepare("DELETE FROM users WHERE id = ?");
                $del->bind_param("i", $id_hapus);
                $del->execute();

                $conn->commit();
                $success = "User '$oldUsername' berhasil dihapus! Data transaksi dialihkan ke akun Anda.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal menghapus user: " . $e->getMessage();
            }
        }
    } else {
        $error = "User tidak ditemukan.";
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan - LABORA</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/pengaturan.css?v=<?= time() ?>">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>
            
            <div class="page-content">
                <div class="welcome-header">
                    <h1>⚙️ Pengaturan Sistem</h1>
                    <p>Kelola identitas toko dan keamanan akun Anda di sini.</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?= $success ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">⚠️ <?= $error ?></div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- KIRI: PROFIL TOKO -->
                    <div class="settings-card">
                        <h3>🏪 Profil Toko (Data Nota)</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Nama Toko</label>
                                <input type="text" name="nama_toko" value="<?= htmlspecialchars($store['nama_toko']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Alamat Toko</label>
                                <textarea name="alamat" rows="3" required><?= htmlspecialchars($store['alamat']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input type="text" name="telepon" value="<?= htmlspecialchars($store['telepon']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Pesan Kaki Nota (Footer)</label>
                                <textarea name="footer_nota" rows="3" required><?= htmlspecialchars($store['footer_nota']) ?></textarea>
                                <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">* Muncul di bagian paling bawah struk belanja.</p>
                            </div>
                            <button type="submit" name="update_store" class="btn-save">Simpan Profil Toko</button>
                        </form>
                    </div>

                    <!-- KANAN: KEAMANAN -->
                    <div class="settings-card">
                        <h3>🔒 Keamanan Akun</h3>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Role / Akses</label>
                                <input type="text" value="<?= strtoupper($_SESSION['user_role']) ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Ganti Password Baru</label>
                                <input type="password" name="new_password" placeholder="Masukkan password baru...">
                            </div>
                            <button type="submit" name="update_password" class="btn-save" style="background: #64748b;">Update Password</button>
                        </form>
                    </div>

                    <!-- FULL WIDTH: CADANGAN DATA (OWNER ONLY) -->
                    <?php if ($_SESSION['user_role'] === 'owner'): ?>
                    <div class="settings-card" style="grid-column: span 2;">
                        <h3>💾 Cadangan Data (Backup)</h3>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">
                            Unduh seluruh data transaksi, stok, dan pengaturan toko Anda untuk disimpan sebagai cadangan pribadi. 
                            Gunakan file ini untuk memulihkan data jika terjadi masalah pada hosting.
                        </p>
                        <a href="backup_db.php" class="btn-save" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #0f172a; text-decoration: none;">
                            📥 Unduh Cadangan Database (.sql)
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- FULL WIDTH: MANAJEMEN PENGGUNA (OWNER ONLY) -->
                <?php if ($_SESSION['user_role'] === 'owner'): ?>
                <div class="user-management-section">
                    <div class="settings-card" style="max-width: 100%;">
                        <h3>👥 Manajemen Pengguna</h3>
                        <div class="user-mgmt-container">
                            <div class="add-user-form">
                                <h4>Tambah Akun Baru</h4>
                                <form action="" method="POST" class="inline-form">
                                    <input type="text" name="username_new" placeholder="Username" required>
                                    <input type="password" name="password_new" placeholder="Password" required>
                                    <select name="role_new" required>
                                        <option value="kasir">Kasir</option>
                                        <option value="owner">Owner</option>
                                    </select>
                                    <button type="submit" name="add_user" class="btn-save" style="width: auto;">+ Tambah User</button>
                                </form>
                            </div>
                            
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $qUsers = $conn->query("SELECT * FROM users ORDER BY role DESC");
                                    while($u = $qUsers->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><span class="badge-role <?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                                        <td>
                                            <?php if($u['username'] !== $_SESSION['username']): ?>
                                                <a href="?hapus_user=<?= $u['id'] ?>" class="btn-delete-user" onclick="return confirm('Hapus user ini?')">Hapus</a>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 11px;">(Sedang Digunakan)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>