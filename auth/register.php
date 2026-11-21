<?php
// auth/register.php
session_start();
require '../config/database.php';

// Jika sudah login, dilarang mengakses halaman registrasi
if (isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit;
}

$pesan = '';
$pesan_tipe = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password     = $_POST['password'];
    $role         = 'pelanggan'; // Default pendaftaran lewat publik

    // Cek apakah username sudah dipakai
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "Username sudah digunakan, silakan pilih yang lain.";
        $pesan_tipe = "danger";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (nama_lengkap, username, no_telp, password, role) 
                  VALUES ('$nama_lengkap', '$username', '$no_telp', '$password_hashed', '$role')";
        
        if (mysqli_query($conn, $query)) {
            $pesan = "Pendaftaran berhasil! Silakan login untuk melanjutkan.";
            $pesan_tipe = "success";
        } else {
            $pesan = "Gagal membuat akun. Silakan coba lagi.";
            $pesan_tipe = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --rb-dark-navy: #121F45;
            --rb-yellow: #FFC906;
            --rb-red: #CC1E4A;
        }
        body {
            background-color: var(--rb-dark-navy);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 0;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background-color: #ffffff;
            width: 100%;
            max-width: 500px; /* Sedikit lebih lebar dari form login untuk form register */
        }
        .btn-primary-custom {
            background-color: var(--rb-red);
            border: none;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #b0163e;
            transform: translateY(-2px);
        }
        .form-control:focus {
            border-color: var(--rb-dark-navy);
            box-shadow: 0 0 0 0.25rem rgba(18, 31, 69, 0.2);
        }
        .brand-title {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            color: var(--rb-dark-navy);
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center px-3">
    <div class="card login-card p-4 p-sm-5">
        <div class="card-body">
            
            <!-- Bagian Logo (Menggunakan Logo Tanpa Teks) -->
            <div class="text-center mb-4">
                <img src="../assets/logo_icon.png" alt="Logo Fino Motor" style="max-width: 160px; height: auto;" class="mb-3">
                <h4 class="brand-title mt-2 mb-1">Pendaftaran Pelanggan</h4>
                <p class="text-muted small">Buat akun agar bisa memesan layanan & barang</p>
            </div>

            <!-- Tampilan Notifikasi -->
            <?php if (!empty($pesan)): ?>
                <div class="alert alert-<?= $pesan_tipe ?> py-2 small rounded-3" role="alert">
                    <i class="fa <?= $pesan_tipe === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i> <?= $pesan; ?>
                </div>
            <?php endif; ?>

            <!-- Form Inputs -->
            <form method="POST" action="">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-id-badge"></i></span>
                        <input type="text" name="nama_lengkap" class="form-control border-start-0 bg-light" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Username (Untuk Login)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-user"></i></span>
                        <input type="text" name="username" class="form-control border-start-0 bg-light" placeholder="Buat username unik" required autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Nomor WhatsApp / HP</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-phone"></i></span>
                        <input type="text" name="no_telp" class="form-control border-start-0 bg-light" placeholder="Contoh: 08123456789" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Password Akun</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 bg-light" placeholder="Minimal 6 karakter direkomendasikan" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2 rounded-pill fs-5 mb-3 shadow-sm">
                    <i class="fa fa-user-plus me-2"></i> Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">Sudah punya akun? <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--rb-dark-navy);">Masuk Disini</a></p>
                <hr class="my-3 opacity-25">
                <a href="../index.php" class="small text-secondary text-decoration-none fw-semibold"><i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda Utama</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>