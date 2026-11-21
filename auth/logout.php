<?php
// auth/logout.php
require_once __DIR__ . '/../config/app.php';

session_start();

// Menghapus semua variabel session
session_unset();

// Menghancurkan session sepenuhnya
session_destroy();

// Arahkan kembali ke halaman utama (index.php) setelah berhasil logout
header("Location: " . BASE_URL . "/index.php");
exit;
?>