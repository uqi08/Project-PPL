<?php
// owner/proses_edit.php
session_start();
require '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id        = (int)$_POST['id']; // ID item yang dikirim dari form edit (biasanya hidden input)
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $tipe      = $_POST['tipe'];
    $harga     = (int)$_POST['harga'];

    if ($tipe === 'jasa') {
        $stok_query = "stok = NULL";
    } else {
        $stok = (int)$_POST['stok'];
        $stok_query = "stok = $stok";
    }

    $query = "UPDATE items SET 
                nama_item = '$nama_item', 
                tipe = '$tipe', 
                harga = $harga, 
                $stok_query
              WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil diperbarui!'); window.location.href='master_barang.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>