<?php
// owner/uang_keluar.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

// Logika Hapus Data Pengeluaran
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pengeluaran WHERE id = $id_hapus");
    echo "<script>alert('Data pengeluaran berhasil dihapus/dibatalkan.'); window.location.href='uang_keluar.php';</script>";
    exit;
}

// Filter Tanggal (Default: Hari ini)
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Ambil Data Pengeluaran
$query_keluar = mysqli_query($conn, "
    SELECT * FROM pengeluaran 
    WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'
    ORDER BY tanggal DESC, id DESC
");

// Hitung Total Pengeluaran
$q_total = mysqli_query($conn, "
    SELECT SUM(nominal) AS total 
    FROM pengeluaran 
    WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'
");
$total_pengeluaran = mysqli_fetch_assoc($q_total)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Uang Keluar - Fino Motor</title>
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
            .filter-form { flex-direction: column; width: 100%; gap: 10px; align-items: stretch !important; }
            .filter-form > * { width: 100%; margin: 0 !important; }
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
            <li><a href="uang_keluar.php" class="active"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_admin.php"><i class="fa fa-user-shield text-white"></i> Manajemen Staf</a></li>
            <li><a href="profil.php"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>
            
            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4 header-title-container">
            <div class="d-flex align-items-center">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                <div>
                    <h2 class="m-0" style="font-family:'Playfair Display',serif; font-weight:900; color:#121F45;">
                        <i class="fa fa-arrow-circle-up text-danger me-2 d-none d-sm-inline"></i> Laporan Uang Keluar
                    </h2>
                    <p class="text-muted mb-0 d-none d-md-block">Rincian beban biaya dan operasional bengkel.</p>
                </div>
            </div>
            
            <form method="GET" class="d-flex gap-2 align-items-center bg-white p-2 rounded-pill shadow-sm border filter-form">
                <input type="date" name="tgl_mulai" class="form-control form-control-sm border-0 bg-transparent fw-bold text-secondary" value="<?= $tgl_mulai ?>">
                <span class="text-muted fw-bold d-none d-md-block">-</span>
                <input type="date" name="tgl_selesai" class="form-control form-control-sm border-0 bg-transparent fw-bold text-secondary" value="<?= $tgl_selesai ?>">
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold"><i class="fa fa-filter me-1"></i> Filter</button>
            </form>
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white mb-4">
            <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-25 gap-2">
                <h5 class="text-danger fw-bold m-0">Total Pengeluaran di Rentang Tanggal Ini:</h5>
                <h3 class="text-danger fw-bold m-0">Rp <?= number_format($total_pengeluaran, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th>ID Beban</th>
                            <th>Tanggal Tercatat</th>
                            <th>Keterangan / Kebutuhan</th>
                            <th>Nominal Keluar</th>
                            <th class="text-center">Aksi Ralat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_keluar) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($query_keluar)): ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#EXP-<?= $row['id']; ?></td>
                                    <td class="fw-bold text-dark"><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                                    <td style="white-space: normal;"><?= htmlspecialchars($row['keterangan']); ?></td>
                                    <td class="fw-bold text-danger">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <a href="uang_keluar.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Apakah Anda yakin ingin membatalkan/menghapus catatan pengeluaran ini? Laporan keuangan akan berubah mengikuti penghapusan ini.');">
                                            <i class="fa fa-trash-alt"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fa fa-folder-open fa-3x mb-3 text-light"></i><br>
                                    <span class="fw-bold text-secondary">Tidak ada pengeluaran pada tanggal ini. Hebat!</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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