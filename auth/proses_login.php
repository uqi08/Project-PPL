<?php
// auth/proses_login.php
session_start();
require '../config/database.php';

// Pastikan request datang dari form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Amankan input dari SQL Injection
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Cari user berdasarkan username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    // Jika username ditemukan
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Verifikasi kecocokan password yang diinput dengan hash di database
        if (password_verify($password, $row['password'])) {
            
            // Set Session Data
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];

            // Redirect berdasarkan role (Isolasi Halaman)
            if ($row['role'] == 'owner') {
                header("Location: ../owner/dashboard.php");
            } elseif ($row['role'] == 'karyawan') {
                header("Location: ../karyawan/kasir.php");
            } else {
                header("Location: ../pelanggan/katalog.php");
            }
            exit;
        } else {
            // Password salah
            echo "<script>alert('Password salah!'); window.location.href='login.php';</script>";
        }
    } else {
        // Username tidak ditemukan
        echo "<script>alert('Username tidak ditemukan!'); window.location.href='login.php';</script>";
    }
} else {
    // Jika diakses langsung dari URL tanpa POST
    header("Location: login.php");
    exit;
}
?>