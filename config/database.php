<?php
// config/database.php

$host = "localhost";
$port = "3307";
$user = "root";       // Default username XAMPP
$pass = "";           // Default password XAMPP (kosong)
$db   = "fino_motor"; // Nama database yang baru saja dibuat

// Membuat koneksi
$conn = mysqli_connect($host, $user, $pass, $db ,$port);

// Cek koneksi
if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Set zona waktu default ke Waktu Indonesia Barat (WIB)
// Sangat penting untuk fitur auto-cancel 24 jam nantinya
date_default_timezone_set('Asia/Jakarta');
?>