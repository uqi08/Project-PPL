<?php
// karyawan/profil.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password_baru = trim($_POST['password_baru']);
    
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $user_id");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "<div class='alert alert-danger'>Username sudah digunakan orang lain.</div>";
    } else {
        if (!empty($password_baru)) {
            $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', username = '$username', email = '$email', no_telp = '$no_telp', password = '$password_hashed' WHERE id = $user_id";
        } else {
            $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', username = '$username', email = '$email', no_telp = '$no_telp' WHERE id = $user_id";
        }
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $pesan = "<div class='alert alert-success'><i class='fa fa-check-circle me-2'></i> Profil berhasil diperbarui!</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Terjadi kesalahan sistem.</div>";
        }
    }
}

$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query_user);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; margin: 0; overflow-x: hidden; }
        
        /* SIDEBAR DEFAULT DESKTOP */
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background-color: #121F45; color: white; padding-top: 20px; border-right: 4px solid #FFC906; z-index: 1050; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 10px 0; margin-bottom: 5px !important; }
        .sidebar-brand img { max-width: 85%; height: auto; }
        .sidebar-menu { list-style: none; padding: 0; margin-top: 0px; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { color: white; background-color: rgba(255,255,255,0.08); border-left: 5px solid #CC1E4A; }
        .sidebar-menu li a i { width: 30px; }
        
        /* MAIN CONTENT */
        .main-content { margin-left: 260px; padding: 40px; min-height: 100vh; transition: margin-left 0.3s ease; }

        /* MOBILE RESPONSIVE QUERIES */
        .mobile-toggle { display: none; background: none; border: none; font-size: 24px; color: #121F45; cursor: pointer; padding: 0; margin-right: 15px; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; opacity: 0; transition: opacity 0.3s ease; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); } 
            .main-content { margin-left: 0 !important; padding: 20px !important; } 
            .mobile-toggle { display: block; } 
            .sidebar-overlay.active { display: block; opacity: 1; }
        }
    </style>
</head>
<body>
    
    <!-- OVERLAY GELAP UNTUK MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand text-center position-relative">
            <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo">
            <button class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-3 d-md-none" onclick="toggleSidebar()"></button>
        </div>
        <ul class="sidebar-menu mt-3">
            <li><a href="kasir.php"><i class="fa fa-cash-register text-white"></i> Mesin Kasir</a></li>
            <li><a href="uang_masuk.php"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_karyawan.php"><i class="fa fa-user-plus text-white"></i> Tambah Karyawan</a></li>
            <li><a href="profil.php" class="active"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>

            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="d-flex align-items-center mb-4">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <h2 class="m-0 text-dark" style="font-family:'Playfair Display',serif; font-weight:900;">
                <i class="fa fa-user-circle text-primary me-2 d-none d-sm-inline"></i>Pengaturan Profil Akun
            </h2>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 p-4 p-md-5 rounded-4 bg-white">
                    <div class="text-center mb-4">
                        <i class="fa fa-user-tie fa-4x text-muted mb-2"></i>
                        <h4 class="fw-bold text-dark">Data Diri Karyawan</h4>
                        <span class="badge bg-info text-dark px-3 py-1">KARYAWAN</span>
                    </div>

                    <?= $pesan; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username Login</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" placeholder="contoh@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No. Telepon</label>
                                <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? ''); ?>" placeholder="0812xxxxxx">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Ganti Password (Opsional)</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak ganti">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                            <i class="fa fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
    </script>
</body>
</html>