<?php
// karyawan/uang_keluar.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: ../auth/login.php"); 
    exit;
}

if (isset($_POST['simpan_pengeluaran'])) {
    $tgl = $_POST['tanggal'];
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $nom = (int)$_POST['nominal'];
    $user_id = $_SESSION['user_id']; 
    
    mysqli_query($conn, "INSERT INTO pengeluaran (user_id, tanggal, keterangan, nominal) VALUES ($user_id, '$tgl', '$ket', $nom)");
    echo "<script>alert('Berhasil mencatat pengeluaran!'); window.location.href='uang_keluar.php';</script>"; 
    exit;
}

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

$query_keluar = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai' ORDER BY tanggal DESC, id DESC");
$grand_total = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uang Keluar - Karyawan</title>
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

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand text-center position-relative">
            <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo">
            <button class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-3 d-md-none" onclick="toggleSidebar()"></button>
        </div>
        <ul class="sidebar-menu mt-3">
            <li><a href="kasir.php"><i class="fa fa-cash-register text-white"></i> Mesin Kasir</a></li>
            <li><a href="uang_masuk.php"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php" class="active"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_karyawan.php"><i class="fa fa-user-plus text-white"></i> Tambah Karyawan</a></li>
            <li><a href="profil.php"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>

            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 header-title-container">
            <div class="d-flex align-items-center">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                <h2 class="m-0 text-danger" style="font-family:'Playfair Display',serif; font-weight:900;">
                    <i class="fa fa-arrow-circle-up me-2 d-none d-sm-inline"></i> Catat & Rekap Uang Keluar
                </h2>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 p-4 rounded-4 border-top border-4 border-danger bg-white">
                    <h5 class="fw-bold mb-3"><i class="fa fa-plus-circle me-2"></i>Input Pengeluaran Baru</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tanggal Pembelian</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Keterangan / Keperluan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Cth: Sabun salju, makan siang" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Nominal Harga (Rp)</label>
                            <input type="number" name="nominal" class="form-control" required>
                        </div>
                        <button type="submit" name="simpan_pengeluaran" class="btn btn-danger w-100 fw-bold"><i class="fa fa-save me-2"></i>Simpan Pengeluaran</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 p-4 rounded-4 mb-4 bg-white">
                    <form method="GET" class="row g-2 align-items-end filter-form">
                        <div class="col-md-4"><label class="form-label small fw-bold">Dari Tanggal</label><input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai; ?>"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Sampai Tanggal</label><input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai; ?>"></div>
                        <div class="col-md-4"><button type="submit" class="btn btn-outline-danger w-100 fw-bold"><i class="fa fa-filter me-2"></i>Filter Tanggal</button></div>
                    </form>
                </div>

                <div class="card shadow-sm border-0 p-0 rounded-4 overflow-hidden bg-white">
                    <div class="table-responsive">
                        <table class="table align-middle m-0" style="white-space: nowrap;">
                            <thead class="table-light"><tr><th class="ps-4">Tanggal</th><th style="white-space: normal;">Keterangan Keperluan</th><th>Nominal</th></tr></thead>
                            <tbody>
                                <?php if(mysqli_num_rows($query_keluar) > 0): while($row = mysqli_fetch_assoc($query_keluar)): $grand_total += $row['nominal']; ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                                        <td style="white-space: normal;"><?= htmlspecialchars($row['keterangan']); ?></td>
                                        <td class="text-danger fw-bold">Rp <?= number_format($row['nominal'],0,',','.'); ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada pengeluaran pada rentang tanggal ini.</td></tr>
                                <?php endif; ?>
                                <tr class="table-danger fw-bold">
                                    <td colspan="2" class="text-end">TOTAL PENGELUARAN:</td>
                                    <td class="fs-5 text-danger">Rp <?= number_format($grand_total,0,',','.'); ?></td>
                                </tr>
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