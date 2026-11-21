<?php
// owner/kasir.php
session_start();
require '../config/database.php';

// Proteksi akses: Hanya Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}
$id_kasir = $_SESSION['user_id'];

if (!isset($_SESSION['keranjang_kasir'])) { $_SESSION['keranjang_kasir'] = []; }

if (isset($_POST['tambah_manual'])) {
    $item_key = $_POST['item_key'];
    $qty = (int)$_POST['qty'];
    if (isset($_SESSION['keranjang_kasir'][$item_key])) {
        $_SESSION['keranjang_kasir'][$item_key] += $qty;
    } else {
        $_SESSION['keranjang_kasir'][$item_key] = $qty;
    }
    header("Location: kasir.php"); exit;
}

if (isset($_GET['hapus_manual'])) {
    unset($_SESSION['keranjang_kasir'][$_GET['hapus_manual']]);
    header("Location: kasir.php"); exit;
}

if (isset($_POST['selesai_manual']) && !empty($_SESSION['keranjang_kasir'])) {
    $total_belanja = 0; $tgl = date('Y-m-d'); $wkt = date('H:i:s');
    
    foreach ($_SESSION['keranjang_kasir'] as $cart_key => $qty) {
        $id_item = (int)$cart_key; 
        $opsi = 'normal';
        if (strpos($cart_key, '_saja') !== false) $opsi = 'saja';
        elseif (strpos($cart_key, '_pasang') !== false) $opsi = 'pasang';

        $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga, harga_pasang FROM items WHERE id = $id_item"));
        $harga_berlaku = ($opsi === 'pasang') ? $item['harga_pasang'] : $item['harga'];
        $total_belanja += ($harga_berlaku * $qty);
    }
    
    if (mysqli_query($conn, "INSERT INTO transactions (user_id, total_harga, status, tanggal_booking, waktu_booking) VALUES ($id_kasir, $total_belanja, 'selesai', '$tgl', '$wkt')")) {
        $id_trx = mysqli_insert_id($conn);
        
        foreach ($_SESSION['keranjang_kasir'] as $cart_key => $qty) {
            $id_item = (int)$cart_key;
            $opsi = 'normal';
            if (strpos($cart_key, '_saja') !== false) $opsi = 'saja';
            elseif (strpos($cart_key, '_pasang') !== false) $opsi = 'pasang';

            $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga, harga_pasang, tipe, stok FROM items WHERE id = $id_item"));
            $harga_berlaku = ($opsi === 'pasang') ? $item['harga_pasang'] : $item['harga'];
            $subtotal = $harga_berlaku * $qty;

            mysqli_query($conn, "INSERT INTO transaction_details (transaction_id, item_id, qty, harga, subtotal) VALUES ($id_trx, $id_item, $qty, $harga_berlaku, $subtotal)");
            
            if ($item['tipe'] == 'barang') { 
                mysqli_query($conn, "UPDATE items SET stok = stok - $qty WHERE id = $id_item"); 
            }
        }
        unset($_SESSION['keranjang_kasir']);
        header("Location: kasir.php?cetak=" . $id_trx); exit;
    }
}

if (isset($_GET['selesai_online'])) {
    $id_booking = (int)$_GET['selesai_online'];
    mysqli_query($conn, "UPDATE transactions SET status = 'selesai' WHERE id = $id_booking");
    $q_detail = mysqli_query($conn, "SELECT item_id, qty FROM transaction_details WHERE transaction_id = $id_booking");
    while ($row = mysqli_fetch_assoc($q_detail)) {
        $cek_tipe = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tipe FROM items WHERE id = {$row['item_id']}"));
        if ($cek_tipe['tipe'] == 'barang') { mysqli_query($conn, "UPDATE items SET stok = stok - {$row['qty']} WHERE id = {$row['item_id']}"); }
    }
    header("Location: kasir.php?cetak=" . $id_booking); exit;
}

$q_semua_item = mysqli_query($conn, "SELECT * FROM items ORDER BY tipe ASC, nama_item ASC");
$q_booking_pending = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, u.no_telp FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.status = 'pending' ORDER BY t.tanggal_booking ASC, t.waktu_booking ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesin Kasir Owner - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
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
        .select2-container .select2-selection--single { height: 38px !important; display: flex; align-items: center; }

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
            <!-- Tombol Close Sidebar (Hanya tampil di HP) -->
            <button class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-3 d-md-none" onclick="toggleSidebar()"></button>
        </div>
        <ul class="sidebar-menu mt-3">
            <li><a href="dashboard.php"><i class="fa fa-chart-pie text-white"></i> Dashboard Utama</a></li>
            <li><a href="kasir.php" class="active"><i class="fa fa-cash-register text-white"></i> Mesin Kasir</a></li>
            <li><a href="master_barang.php"><i class="fa fa-boxes text-white"></i> Master Gudang</a></li>
            <li><a href="uang_masuk.php"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_admin.php"><i class="fa fa-user-shield text-white"></i> Manajemen Staf</a></li>
            <li><a href="profil.php"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>
            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex align-items-center mb-4">
            <!-- TOMBOL HAMBURGER MOBILE -->
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <h2 class="m-0" style="font-family:'Playfair Display',serif; font-weight:900; color:#121F45;">
                <i class="fa fa-desktop text-warning me-2 d-none d-sm-inline"></i> POS Transaksi Owner
            </h2>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4 p-3 p-md-4 mb-4">
                    <form method="POST" action="kasir.php" class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Pilih Layanan / Barang</label>
                            <select name="item_key" id="dropdownPintar" class="form-select" required>
                                <option value="" disabled selected>-- Ketik / Pilih Item --</option>
                                <?php while ($itm = mysqli_fetch_assoc($q_semua_item)): ?>
                                    <?php if ($itm['is_oli'] == 1): ?>
                                        <option value="<?= $itm['id']; ?>_saja" class="fw-bold">📦 OLI: <?= $itm['nama_item']; ?> (Bawa Pulang) - Rp <?= number_format($itm['harga'], 0, ',', '.'); ?></option>
                                        <option value="<?= $itm['id']; ?>_pasang" class="text-danger fw-bold">🔧 OLI: <?= $itm['nama_item']; ?> (+ Pasang) - Rp <?= number_format($itm['harga_pasang'], 0, ',', '.'); ?></option>
                                    <?php else: ?>
                                        <option value="<?= $itm['id']; ?>"><?= $itm['tipe'] == 'barang' ? '📦' : '🚿'; ?> <?= strtoupper($itm['tipe']); ?> - <?= $itm['nama_item']; ?> (Rp <?= number_format($itm['harga'], 0, ',', '.'); ?>)</option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small fw-bold">Qty</label>
                            <input type="number" name="qty" class="form-control text-center" value="1" min="1" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <button type="submit" name="tambah_manual" class="btn btn-primary w-100 fw-bold h-100 py-2"><i class="fa fa-cart-plus me-1"></i> Tambah</button>
                        </div>
                    </form>

                    <div class="table-responsive mt-4">
                        <table class="table table-bordered align-middle" style="white-space: nowrap;">
                            <thead class="table-light"><tr><th>Item</th><th>Harga Satuan</th><th>Qty</th><th>Subtotal</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php 
                                $grand_total_manual = 0;
                                if (!empty($_SESSION['keranjang_kasir'])):
                                    foreach ($_SESSION['keranjang_kasir'] as $cart_key => $qty):
                                        $id_item = (int)$cart_key;
                                        $opsi = 'normal';
                                        if (strpos($cart_key, '_saja') !== false) $opsi = 'saja';
                                        elseif (strpos($cart_key, '_pasang') !== false) $opsi = 'pasang';

                                        $dtl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_item, harga, harga_pasang FROM items WHERE id = $id_item"));
                                        $harga_final = ($opsi === 'pasang') ? $dtl['harga_pasang'] : $dtl['harga'];
                                        $sub = $harga_final * $qty; 
                                        $grand_total_manual += $sub;
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($dtl['nama_item']); ?><br><?php if($opsi === 'saja') echo "<small class='text-muted'>(Bawa Pulang)</small>"; ?><?php if($opsi === 'pasang') echo "<small class='text-danger'>(Plus Pasang)</small>"; ?></td>
                                    <td>Rp <?= number_format($harga_final, 0, ',', '.'); ?></td>
                                    <td class="text-center"><?= $qty; ?></td>
                                    <td class="fw-bold text-success">Rp <?= number_format($sub, 0, ',', '.'); ?></td>
                                    <td class="text-center"><a href="kasir.php?hapus_manual=<?= $cart_key; ?>" class="btn btn-sm btn-outline-danger border-0"><i class="fa fa-trash"></i></a></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-shopping-basket fs-3 opacity-25 mb-2"></i><br>Belum ada item ditambahkan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($_SESSION['keranjang_kasir'])): ?>
                    <form method="POST" action="kasir.php" class="mt-3">
                        <button type="submit" name="selesai_manual" class="btn btn-success w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm"><i class="fa fa-cash-register me-2"></i> Proses Pembayaran: Rp <?= number_format($grand_total_manual, 0, ',', '.'); ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 rounded-4 p-3 p-md-4 border-top border-4 border-warning bg-white">
                    <h5 class="fw-bold mb-3"><i class="fa fa-bell text-warning me-2"></i> Antrean Booking Online</h5>
                    <ul class="list-group list-group-flush">
                        <?php if (mysqli_num_rows($q_booking_pending) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($q_booking_pending)): ?>
                                <li class="list-group-item p-3 border rounded mb-2 bg-light">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-2 gap-2">
                                        <div><h6 class="fw-bold mb-0"><?= htmlspecialchars($booking['nama_lengkap']); ?></h6><small class="text-muted"><i class="fa fa-phone me-1"></i> <?= $booking['no_telp'] ?: '-'; ?></small></div>
                                        <span class="badge bg-danger rounded-pill px-2 py-1">Rp <?= number_format($booking['total_harga'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="mb-3 small text-primary fw-bold"><i class="fa fa-calendar me-1"></i> <?= date('d M Y', strtotime($booking['tanggal_booking'])); ?> &nbsp; | &nbsp; <i class="fa fa-clock"></i> <?= date('H:i', strtotime($booking['waktu_booking'])); ?></div>
                                    <a href="kasir.php?selesai_online=<?= $booking['id']; ?>" class="btn btn-sm btn-outline-success w-100 fw-bold rounded-pill"><i class="fa fa-check me-1"></i> Tandai Selesai</a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item p-4 text-center text-muted border-0"><i class="fa fa-check-circle fa-2x mb-2 opacity-50"></i><br>Tidak ada antrean online.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT UTAMA -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SCRIPT SIDEBAR & SELECT2 RESPONSIVE -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        $(document).ready(function() {
            $('#dropdownPintar').select2({ theme: 'bootstrap-5', placeholder: '-- Ketik / Pilih Item --', width: '100%' });
        });
    </script>
    
    <?php if (isset($_GET['cetak'])): ?>
    <script>
        setTimeout(function() {
            alert('Transaksi Berhasil Diproses!');
            window.open('../karyawan/cetak_struk.php?id=<?= (int)$_GET['cetak']; ?>', '_blank');
            window.history.replaceState(null, null, window.location.pathname);
        }, 300);
    </script>
    <?php endif; ?>
</body>
</html>