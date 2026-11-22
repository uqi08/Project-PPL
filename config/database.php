
<?php

// Konfigurasi alamat utama aplikasi
define('BASE_URL', '/Project-PPL');

// Konfigurasi database
$host = "default";
$user = "root";
$pass = "";
$db   = "fino_motor";

// Membuat koneksi database
$conn = mysqli_connect($host, $user, $pass, $db);

// Memeriksa koneksi database
if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Mengatur karakter database
mysqli_set_charset($conn, "utf8mb4");

// Mengatur zona waktu WIB
date_default_timezone_set('Asia/Jakarta');

?>
