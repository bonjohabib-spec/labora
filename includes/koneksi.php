<?php
// Deteksi apakah sedang di localhost atau di hosting
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // KONFIGURASI LOKAL (LARAGON)
    $host = 'localhost';
    $user = 'root';
    $pass = ''; 
    $db   = 'labo_ra';
} else {
    // KONFIGURASI HOSTING (INFINITYFREE)
    $host = "sql106.infinityfree.com";
    $user = "if0_41266995";
    $pass = "uRnO1iAeRn"; 
    $db   = "if0_41266995_labora";
}

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi database gagal!");
}
