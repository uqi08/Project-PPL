<?php
// owner/uang_masuk.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

$query_masuk = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap,
           (SELECT GROUP_CONCAT(CONCAT('<span class=\"badge bg-light text-dark border shadow-sm me-1 mb-1\">', td.qty, 'x ', i.nama_item, '</span>') SEPARATOR '') 
            FROM transaction_details td 
            JOIN items i ON td.item_id = i.id 
            WHERE td.transaction_id = t.id) as rincian_barang
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'selesai' 
    AND t.tanggal_booking BETWEEN '$tgl_mulai' AND '$tgl_selesai'
    ORDER BY t.tanggal_booking DESC, t.waktu_booking DESC, t.id DESC
");

$q_total = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM transactions WHERE status = 'selesai' AND tanggal_booking BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$total_pemasukan = mysqli_fetch_assoc($q_total)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Uang Masuk - Fino Motor</title>
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
            <li><a href="uang_masuk.php" class="active"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
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
                        <i class="fa fa-arrow-circle-down text-success me-2 d-none d-sm-inline"></i> Laporan Uang Masuk
                    </h2>
                    <p class="text-muted mb-0 d-none d-md-block">Rincian pendapatan kotor dari transaksi pelanggan.</p>
                </div>
            </div>
            
            <form method="GET" class="d-flex gap-2 align-items-center bg-white p-2 rounded-pill shadow-sm border filter-form">
                <input type="date" name="tgl_mulai" class="form-control form-control-sm border-0 bg-transparent fw-bold text-secondary" value="<?= $tgl_mulai ?>">
                <span class="text-muted fw-bold d-none d-md-block">-</span>
                <input type="date" name="tgl_selesai" class="form-control form-control-sm border-0 bg-transparent fw-bold text-secondary" value="<?= $tgl_selesai ?>">
                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold"><i class="fa fa-filter me-1"></i> Filter</button>
            </form>
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white mb-4">
            <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center bg-success bg-opacity-10 rounded-4 border border-success border-opacity-25 gap-2">
                <h5 class="text-success fw-bold m-0">Total Pemasukan di Rentang Tanggal Ini:</h5>
                <h3 class="text-success fw-bold m-0">Rp <?= number_format($total_pemasukan, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Nota</th>
                            <th>Tanggal & Waktu</th>
                            <th>Nama Pelanggan</th>
                            <th style="min-width: 250px; white-space: normal;">Rincian Barang Terjual</th>
                            <th>Total Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_masuk) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($query_masuk)): ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#TRX-<?= $row['id']; ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= date('d M Y', strtotime($row['tanggal_booking'])); ?></span><br>
                                        <small class="text-muted"><?= date('H:i', strtotime($row['waktu_booking'])); ?> WIB</small>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td style="white-space: normal;">
                                        <?= !empty($row['rincian_barang']) ? $row['rincian_barang'] : '<span class="text-muted small">Tidak ada data rincian</span>'; ?>
                                    </td>
                                    <td class="fw-bold text-success">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <a href="../karyawan/cetak_struk.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fa fa-print me-1"></i> Cetak Ulang
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa fa-folder-open fa-3x mb-3 text-light"></i><br>
                                    <span class="fw-bold text-secondary">Tidak ada pemasukan pada rentang tanggal ini.</span>
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