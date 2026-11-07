<?php
// katalog.php
session_start();
require 'config/database.php';

// --- LOGIKA PROSES TAMBAH KE KERANJANG ---
if (isset($_POST['tambah_keranjang'])) {
    // Pengaman: Harus login sebagai pelanggan terlebih dahulu
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
        echo "<script>alert('Silakan login sebagai akun pelanggan untuk melakukan pemesanan.'); window.location.href='auth/login.php';</script>";
        exit;
    }

    $id_item = (int)$_POST['id_item'];
    $qty = (int)$_POST['qty'];
    $opsi = $_POST['opsi'] ?? 'normal'; // Opsi bisa: 'saja', 'pasang', atau 'normal'

    // Membuat KEY unik untuk keranjang agar tidak bercampur antara oli saja & plus pasang
    $cart_key = $id_item;
    if ($opsi === 'saja') {
        $cart_key = $id_item . "_saja";
    } elseif ($opsi === 'pasang') {
        $cart_key = $id_item . "_pasang";
    }

    // Masukkan ke dalam session keranjang belanja
    if (isset($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key] += $qty;
    } else {
        $_SESSION['keranjang'][$cart_key] = $qty;
    }

    echo "<script>alert('Berhasil dimasukkan ke keranjang belanja!'); window.location.href='katalog.php';</script>";
    exit;
}

// --- LOGIKA FILTER PENCARIAN ---
$search = $_GET['search'] ?? '';
$where_clause = "";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where_clause = "WHERE nama_item LIKE '%$search_safe%'";
}

// Ambil data produk & layanan dari database
$query_items = mysqli_query($conn, "SELECT * FROM items $where_clause ORDER BY tipe ASC, nama_item ASC");

// Memanggil header/navbar rapi milikmu
include 'includes/header.php';
?>

<div class="py-5 text-white text-center position-relative mb-5" style="background: linear-gradient(135deg, #121F45 0%, #223971 100%); border-bottom: 4px solid #FFC906; margin-top: -20px;">
    <div class="container py-4">
        <h1 class="display-4 fw-style font-serif text-uppercase mb-2" style="font-weight: 900; letter-spacing: 2px;">Katalog Produk & Layanan</h1>
        <p class="lead opacity-75">Temukan suku cadang berkualitas, oli original, hingga layanan cuci motor terbaik dari Fino Motor.</p>
        
        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <form method="GET" action="" class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control form-control-lg rounded-start-pill border-0 ps-4 fs-6" placeholder="Cari oli, ban, atau nama jasa..." value="<?= htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-warning rounded-end-pill px-4 fw-bold text-dark"><i class="fa fa-search me-1"></i> Cari</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (mysqli_num_rows($query_items) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_items)): 
                // Deteksi Jalur Gambar (Upload Lokal vs URL Internet)
                $img_src = '';
                if (!empty($row['gambar'])) {
                    if (filter_var($row['gambar'], FILTER_VALIDATE_URL)) {
                        $img_src = $row['gambar'];
                    } else {
                        $img_src = "/Fino_Motor/assets/uploads/" . $row['gambar'];
                    }
                } else {
                    $img_src = "https://via.placeholder.com/400x300?text=Fino+Motor";
                }
            ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-white transition-card" style="transition: transform 0.3s ease;">
                    
                    <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 text-uppercase shadow-sm z-3 <?= $row['tipe'] === 'barang' ? 'bg-primary' : 'bg-success' ?>">
                        <?= $row['tipe']; ?>
                    </span>

                    <div style="height: 200px; width: 100%; overflow: hidden; background-color: #eee;">
                        <img src="<?= $img_src; ?>" class="w-100 h-100" style="object-fit: cover;" alt="Gambar Produk">
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_item']); ?></h5>
                            
                            <?php if ($row['is_oli'] == 1): ?>
                                <div class="mt-2">
                                    <span class="text-muted d-block small">Oli Saja: <b class="text-success">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></b></span>
                                    <span class="text-muted d-block small">Plus Pasang: <b class="text-danger">Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></b></span>
                                </div>
                            <?php else: ?>
                                <h4 class="text-success fw-bold mt-2">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h4>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <?php if ($row['is_oli'] == 1): ?>
                                <button type="button" class="btn btn-outline-dark w-100 fw-bold py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#modalOli<?= $row['id']; ?>">
                                    <i class="fa fa-sliders me-1"></i> Pilih Opsi Oli
                                </button>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="id_item" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="opsi" value="normal">
                                    <button type="submit" name="tambah_keranjang" class="btn btn-dark w-100 fw-bold py-2 rounded-3">
                                        <i class="fa fa-shopping-basket me-1"></i> Pesan Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($row['is_oli'] == 1): ?>
            <div class="modal fade" id="modalOli<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: #121F45;">
                            <h5 class="modal-title font-serif fw-bold"><i class="fa fa-oil-can text-warning me-2"></i> Opsi Pemasangan Oli</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="id_item" value="<?= $row['id']; ?>">
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="<?= $img_src; ?>" class="rounded-3 shadow-sm mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_item']); ?></h4>
                                    <p class="text-muted small">Silakan pilih jenis pembelian di bawah ini</p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Jenis Pembelian</label>
                                    
                                    <div class="p-3 border rounded-3 mb-2 bg-light d-flex align-items-center justify-content-between">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="opsi" id="opsiSaja<?= $row['id']; ?>" value="saja" checked>
                                            <label class="form-check-label fw-bold text-dark" for="opsiSaja<?= $row['id']; ?>">Bawa Pulang (Oli Saja)</label>
                                        </div>
                                        <span class="text-success fw-bold">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></span>
                                    </div>

                                    <div class="p-3 border border-danger bg-danger bg-opacity-10 rounded-3 d-flex align-items-center justify-content-between">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="opsi" id="opsiPasang<?= $row['id']; ?>" value="pasang">
                                            <label class="form-check-label fw-bold text-dark" for="opsiPasang<?= $row['id']; ?>">Ganti di Bengkel (+ Jasa Pasang)</label>
                                        </div>
                                        <span class="text-danger fw-bold">Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-secondary">Jumlah (Qty)</label>
                                    <input type="number" name="qty" class="form-control text-center fw-bold fs-5" value="1" min="1" required>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                                <!-- PERBAIKAN DI BARIS BAWAH INI: Atribut name="tambah_running" dihapus -->
                                <button type="submit" name="tambah_keranjang" class="btn btn-danger fw-bold px-4"><i class="fa fa-cart-plus me-1"></i> Masukkan Keranjang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fa fa-search-minus fa-3x mb-3 opacity-50"></i>
                <h5>Produk atau layanan tidak ditemukan.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Efek transisi hover estetik untuk card katalog */
    .transition-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
</style>

<?php 
// Memanggil footer
include 'includes/footer.php'; 
?>