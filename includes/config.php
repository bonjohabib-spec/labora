<?php
// Gunakan URL absolut biar aman di semua halaman
// Deteksi URL secara dinamis agar bisa diakses dari localhost maupun Ngrok (HP)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/labora';

// Path fisik di komputer
$base_path = __DIR__ . '/..';
