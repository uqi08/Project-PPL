<?php
// auth/login.php
session_start();
require '../config/database.php';

// Jika sudah login, tendang langsung sesuai role-nya masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'karyawan') {
        header("Location: ../karyawan/kasir.php");
    } elseif ($_SESSION['role'] === 'owner') {
        header("Location: ../owner/dashboard.php");
    } else {
        header("Location: ../katalog.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Cari user berdasarkan username
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);

        // Verifikasi password yang di-hash
        if (password_verify($password, $user['password'])) {
            // Set session data user
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];

            // Alur pengalihan halaman berdasarkan hak akses (Role)
            if ($user['role'] === 'karyawan') {
                header("Location: ../karyawan/kasir.php");
            } elseif ($user['role'] === 'owner') {
                header("Location: ../owner/dashboard.php");
            } else {
                // Perbaikan: Diarahkan ke katalog di folder root utama
                header("Location: ../katalog.php");
            }
            exit;
        } else {
            $error = "Password yang Anda masukkan salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fino Motor</title>
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
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
            
            <div class="text-center mb-4">
                <img src="../assets/logo_icon.png" alt="Logo Fino Motor" style="max-width: 160px;" class="mb-2">
                <h4 class="brand-title mt-2 mb-1">Selamat Datang</h4>
                <p class="text-muted small">Silakan masuk untuk melanjutkan akses sistem</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small rounded-3" role="alert">
                    <i class="fa fa-exclamation-circle me-2"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-user"></i></span>
                        <input type="text" name="username" class="form-control border-start-0 bg-light" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 bg-light" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2.5 rounded-pill fs-5 mb-3 shadow-sm">
                    <i class="fa fa-sign-in-alt me-2"></i> Masuk Sekarang
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">Belum punya akun pelanggan? <a href="register.php" class="fw-bold text-decoration-none" style="color: var(--rb-dark-navy);">Daftar Disini</a></p>
                <hr class="my-3 opacity-25">
                <a href="../index.php" class="small text-secondary text-decoration-none fw-semibold"><i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda Utama</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>