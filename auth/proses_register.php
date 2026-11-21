<?php
// auth/proses_register.php
session_start();
require '../config/database.php';

// Pastikan file ini hanya diakses lewat submit form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Menangkap data dari form pendaftaran
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = $_POST['password'];
    $role         = 'pelanggan'; // Pastikan yang daftar dari luar selalu jadi pelanggan

    // Enkripsi password agar aman di database
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah username sudah ada yang pakai
    $cek_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($cek_username) > 0) {
        // Jika username sudah ada
        echo "<script>
                alert('Gagal: Username sudah digunakan! Silakan pilih username lain.'); 
                window.location.href='register.php';
              </script>";
        exit;
    }

    // Jika aman, masukkan data ke database
    $query = "INSERT INTO users (nama_lengkap, username, password, role) 
              VALUES ('$nama_lengkap', '$username', '$password_hashed', '$role')";

    if (mysqli_query($conn, $query)) {
        // Jika berhasil disimpan
        echo "<script>
                alert('Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.'); 
                window.location.href='login.php';
              </script>";
        exit;
    } else {
        // Jika query gagal (misal koneksi terputus)
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Jika seseorang iseng mencoba membuka file ini langsung dari URL
    header("Location: register.php");
    exit;
}
?>