<?php
// owner/proses_tambah.php
session_start();
require '../config/database.php';

// Pastikan hanya owner yang bisa menambah barang
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $tipe      = $_POST['tipe']; // Nilainya dari select box: 'barang' atau 'jasa'
    $harga     = (int)$_POST['harga'];
    
    // Logika handling stok: jika tipe adalah jasa, stok di-set NULL
    if ($tipe === 'jasa') {
        $stok_query = "NULL"; 
    } else {
        $stok = (int)$_POST['stok'];
        $stok_query = $stok;
    }

    $query = "INSERT INTO items (nama_item, tipe, harga, stok) 
              VALUES ('$nama_item', '$tipe', $harga, $stok_query)";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil ditambahkan!'); window.location.href='master_barang.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>