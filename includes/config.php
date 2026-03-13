<?php
// Gunakan URL absolut biar aman di semua halaman
// Deteksi URL secara dinamis agar bisa diakses dari localhost maupun Ngrok (HP)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Jika di localhost, tambahkan subfolder /labora. Jika di hosting, biasanya langsung di root domain.
if ($host == 'localhost' || $host == '127.0.0.1') {
    $base_url = $protocol . $host . '/labora';
} else {
    $base_url = $protocol . $host;
}

// Path fisik di komputer
$base_path = __DIR__ . '/..';
