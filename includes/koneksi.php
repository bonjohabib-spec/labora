<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // kosong kalau XAMPP default
$db   = 'labo_ra';


$conn = mysqli_connect($host, $user, $pass, $db, );

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
