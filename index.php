<?php
// index.php (WBS-2.3 Antarmuka Pembeli / Halaman Utama Pembeli)
session_start();
require 'config/database.php';
require 'includes/cart_helper.php';

// Hitung jumlah item di keranjang untuk badge navbar
$ringkasan_cart  = getRingkasanKeranjang($conn);
$total_item_cart = $ringkasan_cart['total_qty'] ?? 0;

$is_logged_in = isset($_SESSION['user_id']);
$role_user    = $_SESSION['role'] ?? 'pelanggan';
$nama_user    = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pelanggan';

// Ambil 8 produk unggulan (barang fisik yang stoknya tersedia & layanan populer)
$q_unggulan = mysqli_query($conn, "
    SELECT * FROM items 
    WHERE (tipe = 'barang' AND stok > 0) OR tipe = 'jasa'
    ORDER BY tipe ASC, id DESC 
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fino Motor - Toko Suku Cadang, Oli & Layanan Servis Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
        .hero-section {
            background: linear-gradient(135deg, #121F45 0%, #1d326c 65%, #274494 100%);
            color: #ffffff;
            border-bottom-left-radius: 32px;
            border-bottom-right-radius: 32px;
        }
        .cat-card {
            border: none;
            border-radius: 16px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .cat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 22px rgba(18, 31, 69, 0.12);
        }
        .cat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 12px;
        }
        .product-card {
            border: none;
            border-radius: 16px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(18, 31, 69, 0.12);
        }
        .img-box { height: 185px; background-color: #e9ecef; position: relative; overflow: hidden; }
        .img-box img { width: 100%; height: 100%; object-fit: cover; }
        .footer-fino { background-color: #121F45; color: rgba(255,255,255,0.8); border-top: 4px solid #FFC906; }
    </style>
</head>
<body>

<!-- NAVBAR UTAMA PEMBELI -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <img src="assets/logo.png" alt="Fino Motor" style="height: 40px;" class="me-2" onerror="this.style.display='none'">
            <span>FINO MOTOR</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPembeli">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navPembeli">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item"><a class="nav-link active fw-semibold" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="katalog.php">Katalog Produk</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="#kategori">Kategori</a></li>
            </ul>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="pelanggan/keranjang_belanja.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark position-relative">
                    <i class="fa fa-shopping-cart me-1"></i> Keranjang
                    <?php if ($total_item_cart > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $total_item_cart; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <?php if ($role_user === 'owner'): ?>
                        <a href="owner/dashboard.php" class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-dark">Panel Owner</a>
                    <?php elseif ($role_user === 'karyawan'): ?>
                        <a href="karyawan/kasir.php" class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-dark">Panel Kasir</a>
                    <?php else: ?>
                        <a href="pelanggan/pesanan_saya.php" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">
                            <i class="fa fa-receipt me-1"></i> Pesanan Saya
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Login</a>
                    <a href="auth/register.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- HERO SECTION & PENCARIAN CEPAT -->
<section class="hero-section py-5 mb-5 shadow-sm">
    <div class="container py-md-3">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">
                    <i class="fa fa-star me-1"></i> Bengkel & Toko Suku Cadang Terpercaya Bangkalan
                </span>
                <h1 class="display-5 fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
                    Rawat Performa Motormu Bersama Fino Motor
                </h1>
                <p class="lead text-white-50 mb-4">
                    Belanja oli asli, ban tubeless, dan sparepart berkualitas dengan pengiriman cepat atau layanan pasang langsung di bengkel.
                </p>

                <!-- Form Pencarian Langsung ke Katalog -->
                <form method="GET" action="katalog.php" class="mb-4" style="max-width: 540px;">
                    <div class="input-group input-group-lg shadow">
                        <input type="text" name="q" class="form-control border-0" placeholder="Cari oli MPX2, ban IRC, atau cuci motor...">
                        <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
                            <i class="fa fa-search me-1"></i> Cari
                        </button>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2">
                    <a href="katalog.php" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold text-dark shadow-sm">
                        <i class="fa fa-store me-2"></i> Jelajahi Semua Produk
                    </a>
                    <a href="katalog.php?kategori=Jasa" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                        <i class="fa fa-wrench me-2"></i> Layanan Bengkel
                    </a>
                </div>
            </div>

            <div class="col-lg-5 text-center d-none d-lg-block">
                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-25">
                    <img src="assets/logo.png" alt="Fino Motor" class="img-fluid mb-3" style="max-height: 150px;" onerror="this.style.display='none'">
                    <h5 class="fw-bold text-warning mb-1">Order Online Mudah & Transparan</h5>
                    <p class="small text-white-50 mb-0">Cek stok real-time, estimasi ongkos kirim otomatis, dan pembayaran digital aman.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PILIHAN KATEGORI PRODUK -->
<section id="kategori" class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0" style="color: #121F45;">Kategori Pilihan</h3>
        <a href="katalog.php" class="text-decoration-none fw-bold">Lihat Semua <i class="fa fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row row-cols-2 row-cols-md-5 g-3">
        <?php
        $kategori_cards = [
            ['nama' => 'Oli',             'icon' => 'fa-oil-can',       'bg' => 'bg-warning bg-opacity-25 text-warning'],
            ['nama' => 'Ban',             'icon' => 'fa-compact-disc',  'bg' => 'bg-primary bg-opacity-10 text-primary'],
            ['nama' => 'Sparepart',       'icon' => 'fa-cogs',          'bg' => 'bg-danger bg-opacity-10 text-danger'],
            ['nama' => 'Perawatan Motor', 'icon' => 'fa-spray-can',     'bg' => 'bg-info bg-opacity-10 text-info'],
            ['nama' => 'Jasa',            'icon' => 'fa-tools',         'bg' => 'bg-success bg-opacity-10 text-success'],
        ];
        foreach ($kategori_cards as $kc):
        ?>
        <div class="col">
            <a href="katalog.php?kategori=<?= urlencode($kc['nama']); ?>" class="text-decoration-none text-dark">
                <div class="card cat-card bg-white shadow-sm p-4 text-center h-100">
                    <div class="cat-icon <?= $kc['bg']; ?>">
                        <i class="fa <?= $kc['icon']; ?>"></i>
                    </div>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($kc['nama']); ?></h6>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- PRODUK & LAYANAN UNGGULAN (WBS-2.3) -->
<section class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0" style="color: #121F45;">Produk & Layanan Terbaru</h3>
            <small class="text-muted">Pilih produk untuk melihat detail atau langsung masukkan ke keranjang</small>
        </div>
        <a href="katalog.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Katalog Lengkap</a>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if ($q_unggulan && mysqli_num_rows($q_unggulan) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($q_unggulan)):
                $foto = !empty($item['gambar'])
                    ? (filter_var($item['gambar'], FILTER_VALIDATE_URL) ? $item['gambar'] : "assets/uploads/" . $item['gambar'])
                    : "https://via.placeholder.com/400x300?text=Fino+Motor";
                $is_barang = ($item['tipe'] === 'barang');
            ?>
            <div class="col">
                <div class="card product-card h-100 bg-white shadow-sm">
                    <a href="detail_produk.php?id=<?= $item['id']; ?>" class="text-decoration-none">
                        <div class="img-box">
                            <img src="<?= htmlspecialchars($foto); ?>" alt="<?= htmlspecialchars($item['nama_item']); ?>">
                            <span class="position-absolute top-0 start-0 m-2 badge <?= $is_barang ? 'bg-primary' : 'bg-success'; ?>">
                                <?= htmlspecialchars($item['kategori']); ?>
                            </span>
                            <?php if ($is_barang): ?>
                                <span class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75">
                                    Stok: <?= (int)$item['stok']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column p-3">
                        <a href="detail_produk.php?id=<?= $item['id']; ?>" class="text-decoration-none text-dark">
                            <h6 class="fw-bold mb-1 lh-sm"><?= htmlspecialchars($item['nama_item']); ?></h6>
                        </a>
                        <p class="text-muted small mb-2 text-truncate">
                            <?= htmlspecialchars($item['deskripsi'] ?? 'Produk & layanan terbaik Fino Motor.'); ?>
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <div class="fw-bold fs-5 text-success">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                            <?php if ((int)$item['is_oli'] === 1 && (int)$item['harga_pasang'] > 0): ?>
                                <small class="text-danger fw-semibold d-block">+ Pasang: Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex gap-2">
                        <a href="detail_produk.php?id=<?= $item['id']; ?>" class="btn btn-outline-secondary btn-sm w-50 fw-bold rounded-3">
                            Detail
                        </a>
                        <form method="POST" action="pelanggan/proses_keranjang.php" class="w-50">
                            <input type="hidden" name="aksi" value="tambah">
                            <input type="hidden" name="item_id" value="<?= $item['id']; ?>">
                            <input type="hidden" name="qty" value="1">
                            <input type="hidden" name="opsi" value="<?= (int)$item['is_oli'] === 1 ? 'saja' : 'normal'; ?>">
                            <input type="hidden" name="redirect" value="keranjang_belanja.php">
                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark rounded-3">
                                <i class="fa fa-cart-plus me-1"></i> Beli
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-4 text-muted">Belum ada produk tersedia.</div>
        <?php endif; ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer-fino py-4 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <strong class="text-warning">FINO MOTOR</strong> — Bengkel Servis & Suku Cadang Motor, Bangkalan.
        </div>
        <small class="text-white-50">© <?= date('Y'); ?> Fino Motor. All rights reserved.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>