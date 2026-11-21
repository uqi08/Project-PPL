<?php
// owner/profil.php
session_start();
require '../config/database.php';

// Proteksi: Hanya Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan = '';
$pesan_tipe = '';

// --- LOGIKA UPDATE PROFIL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profil'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password     = $_POST['password'];

    if (!empty($password)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', no_telp = '$no_telp', password = '$password_hashed' WHERE id = $user_id";
    } else {
        $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', no_telp = '$no_telp' WHERE id = $user_id";
    }

    if (mysqli_query($conn, $query)) {
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $pesan = "Profil Anda berhasil diperbarui!";
        $pesan_tipe = "success";
    } else {
        $pesan = "Gagal memperbarui profil. Terjadi kesalahan pada sistem.";
        $pesan_tipe = "danger";
    }
}

// Ambil data terbaru pengguna
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($q_user);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Fino Motor</title>
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

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand text-center position-relative">
            <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo">
            <button class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-3 d-md-none" onclick="toggleSidebar()"></button>
        </div>
        <ul class="sidebar-menu mt-3">
            <li><a href="dashboard.php"><i class="fa fa-chart-pie text-white"></i> Dashboard Utama</a></li>
            <li><a href="kasir.php"><i class="fa fa-cash-register text-white"></i> Mesin Kasir</a></li>
            <li><a href="master_barang.php"><i class="fa fa-boxes text-white"></i> Master Gudang</a></li>
            <li><a href="uang_masuk.php"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_admin.php"><i class="fa fa-user-shield text-white"></i> Manajemen Staf</a></li>
            <li><a href="profil.php" class="active"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>
            
            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="d-flex align-items-center mb-4">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <h2 class="m-0" style="font-family:'Playfair Display',serif; font-weight:900; color:#121F45;">
                <i class="fa fa-user-circle text-primary me-2 d-none d-sm-inline"></i> Pengaturan Profil Akun
            </h2>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $pesan_tipe ?> py-3 rounded-4 shadow-sm fw-bold mb-4">
                <i class="fa <?= $pesan_tipe == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $pesan; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 70px; height: 70px; font-size: 30px;">
                            <i class="fa fa-user"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0"><?= htmlspecialchars($user_data['nama_lengkap']); ?></h5>
                            <span class="badge bg-danger rounded-pill mt-1 px-3">OWNER</span>
                        </div>
                    </div>
                    
                    <hr class="opacity-25 mb-4">
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Username (Tidak bisa diubah)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user_data['username']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user_data['nama_lengkap']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nomor WhatsApp / Telepon</label>
                            <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user_data['no_telp']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Ubah Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                            <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> Biarkan kosong jika password masih sama.</small>
                        </div>

                        <button type="submit" name="update_profil" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm">
                            <i class="fa fa-save me-2"></i> Perbarui Data Profil
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white bg-opacity-75 h-100 border-start border-4 border-warning">
                    <h5 class="fw-bold mb-3"><i class="fa fa-shield-alt text-warning me-2"></i> Keamanan Akun</h5>
                    <p class="text-muted">Sebagai Owner, akun Anda memiliki akses tertinggi di dalam sistem. Pastikan untuk menjaga keamanan data dengan langkah berikut:</p>
                    <ul class="text-muted small">
                        <li class="mb-2">Gunakan kombinasi password yang kuat (angka, huruf, simbol).</li>
                        <li class="mb-2">Jangan membagikan akun Owner Anda kepada staf Karyawan.</li>
                        <li class="mb-2">Perbarui password secara berkala untuk menghindari akses tidak sah.</li>
                    </ul>
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