<?php
// owner/tambah_admin.php
session_start();
require '../config/database.php';

// Proteksi: Hanya Owner yang boleh mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$pesan_tipe = '';

// --- LOGIKA HAPUS STAF ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    if ($id_hapus === $_SESSION['user_id']) {
        $pesan = "Akses ditolak! Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.";
        $pesan_tipe = "danger";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id_hapus");
        header("Location: tambah_admin.php?msg=hapus_sukses");
        exit;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'hapus_sukses') {
    $pesan = "Data pengguna berhasil dihapus dari sistem.";
    $pesan_tipe = "success";
}

// --- LOGIKA PROSES PENDAFTARAN STAF ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar_staf'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password     = $_POST['password'];
    $role         = $_POST['role'];

    $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $pesan = "Username sudah terdaftar! Silakan gunakan username lain.";
        $pesan_tipe = "danger";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (nama_lengkap, username, no_telp, password, role) 
                  VALUES ('$nama_lengkap', '$username', '$no_telp', '$password_hashed', '$role')";
        
        if (mysqli_query($conn, $query)) {
            $pesan = "Akun Staf baru ber-role " . strtoupper($role) . " berhasil didaftarkan!";
            $pesan_tipe = "success";
        } else {
            $pesan = "Gagal mendaftarkan akun staf baru.";
            $pesan_tipe = "danger";
        }
    }
}

// --- LOGIKA FITUR PENCARIAN ---
$search = $_GET['search'] ?? '';
$where_clause = "role IN ('owner', 'karyawan')"; 

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where_clause .= " AND (nama_lengkap LIKE '%$safe_search%' OR username LIKE '%$safe_search%' OR no_telp LIKE '%$safe_search%')";
}

$query_staf = mysqli_query($conn, "SELECT * FROM users WHERE $where_clause ORDER BY role ASC, nama_lengkap ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Staf - Fino Motor</title>
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
            .header-title-container { flex-direction: column; align-items: flex-start !important; gap: 15px; }
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
            <li><a href="tambah_admin.php" class="active"><i class="fa fa-user-shield text-white"></i> Manajemen Staf</a></li>
            <li><a href="profil.php"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>
            
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
                <i class="fa fa-user-shield text-primary me-2 d-none d-sm-inline"></i> Otoritas & Manajemen Akses
            </h2>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $pesan_tipe ?> py-3 rounded-4 shadow-sm fw-bold mb-4">
                <i class="fa <?= $pesan_tipe == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $pesan; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa fa-user-plus text-muted me-2"></i>Daftarkan Staf Baru</h5>
                    <hr class="opacity-25 mb-4">
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap staf" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Username (Untuk Login)</label>
                            <input type="text" name="username" class="form-control" placeholder="Buat username login" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nomor WhatsApp / HP</label>
                            <input type="text" name="no_telp" class="form-control" placeholder="Contoh: 08123xxxx" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Hak Akses / Tingkat Otoritas (Role)</label>
                            <select name="role" class="form-select fw-bold text-dark" required>
                                <option value="karyawan">Karyawan (Akses Mesin Kasir POS)</option>
                                <option value="owner">Owner (Akses Penuh Finansial Bengkel)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Password Default</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password awal" required>
                        </div>

                        <button type="submit" name="daftar_staf" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm" style="background-color: var(--rb-red); border:none;">
                            <i class="fa fa-save me-2"></i> Daftarkan Akun Staf
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fa fa-users text-muted me-2"></i>Daftar Pengguna</h5>
                        
                        <form method="GET" action="" class="d-flex w-100" style="max-width: 300px;">
                            <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border w-100">
                                <input type="text" name="search" class="form-control border-0 px-3" placeholder="Cari nama, user, telp..." value="<?= htmlspecialchars($search); ?>">
                                <button class="btn btn-warning fw-bold text-dark border-0 px-3" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>

                    <hr class="opacity-25 mb-3 mt-0">
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="white-space: nowrap;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Nama Pengguna</th>
                                    <th>Username</th>
                                    <th>Kontak</th>
                                    <th class="text-center">Hak Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($query_staf) > 0): ?>
                                    <?php while ($staf = mysqli_fetch_assoc($query_staf)): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($staf['nama_lengkap']); ?></td>
                                            <td><code><?= htmlspecialchars($staf['username']); ?></code></td>
                                            <td><?= htmlspecialchars($staf['no_telp'] ?: '-'); ?></td>
                                            <td class="text-center">
                                                <?php if ($staf['role'] === 'owner'): ?>
                                                    <span class="badge bg-danger px-3 py-1 rounded-pill shadow-sm"><i class="fa fa-user-shield me-1"></i> OWNER</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark px-3 py-1 rounded-pill shadow-sm"><i class="fa fa-user me-1"></i> KARYAWAN</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="tambah_admin.php?hapus=<?= $staf['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Apakah Anda yakin ingin menghapus staf ini dari sistem?');">
                                                    <i class="fa fa-trash-alt"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa fa-search-minus fa-3x opacity-25 mb-3"></i><br>
                                            Tidak ada data pengguna yang cocok dengan pencarian Anda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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