<?php
// auth/logout.php
session_start();

// Menghapus semua variabel session
session_unset();

// Menghancurkan session sepenuhnya
session_destroy();

// Arahkan kembali ke halaman utama (index.php) setelah berhasil logout
header("Location: ../index.php");
exit;
?>