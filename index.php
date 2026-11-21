<?php
// index.php
session_start(); // Pastikan session_start berada paling atas jika include belum memuatnya
require 'config/database.php';

// --- FITUR BARU: LOGIKA PROSES TAMBAH KE KERANJANG LANGSUNG DARI BERANDA ---
if (isset($_POST['tambah_keranjang'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
        echo "<script>alert('Silakan login sebagai akun pelanggan untuk melakukan pemesanan.'); window.location.href='/Fino_Motor/auth/login.php';</script>";
        exit;
    }

    $id_item = (int)$_POST['id_item'];
    $qty = (int)$_POST['qty'];
    $opsi = $_POST['opsi'] ?? 'normal'; 

    // Membuat KEY unik untuk keranjang belanja (Oli Saja vs Plus Pasang)
    $cart_key = $id_item;
    if ($opsi === 'saja') {
        $cart_key = $id_item . "_saja";
    } elseif ($opsi === 'pasang') {
        $cart_key = $id_item . "_pasang";
    }

    if (isset($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key] += $qty;
    } else {
        $_SESSION['keranjang'][$cart_key] = $qty;
    }

    echo "<script>alert('Berhasil dimasukkan ke keranjang belanja!'); window.location.href='index.php';</script>";
    exit;
}

include 'includes/header.php';

// Ambil data produk & layanan (Dibatasi 4 untuk di beranda sebagai Produk Unggulan)
$query_items = mysqli_query($conn, "SELECT * FROM items ORDER BY tipe ASC, nama_item ASC LIMIT 4");
?>

<style>
    /* Styling khusus untuk halaman Beranda */
    .hero-section {
        /* Gambar background motor keren (bisa diganti dengan foto garasi aslimu) */
        background: url('https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
        height: 80vh;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(18, 31, 69, 0.6); /* Warna dark navy dengan transparansi */
    }

    .hero-box {
        background: rgba(18, 31, 69, 0.85);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 201, 6, 0.3); /* Border tipis warna kuning */
        padding: 3rem 4rem;
        border-radius: 15px;
        position: relative;
        z-index: 1;
        text-align: center;
        max-width: 800px;
    }

    .section-title {
        color: var(--rb-dark-navy);
        text-align: center;
        margin-bottom: 3rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .feature-card {
        background-color: #f1f3f5;
        border-radius: 15px;
        padding: 2rem 1.5rem;
        text-align: center;
        transition: transform 0.3s ease;
        border: none;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        background-color: white;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .product-card {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .product-img {
        height: 200px;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        overflow: hidden; /* Tambahan pengaman gambar keluar dari card border */
    }

    /* Penyesuaian agar tag img di dalam product-img terpotong simetris */
    .product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cta-banner {
        background-color: var(--rb-dark-navy);
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-top: 4px solid var(--rb-red);
        border-bottom: 4px solid var(--rb-yellow);
    }

    .testimonial-card {
        background-color: var(--rb-navy);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        height: 100%;
    }

    .gallery-img {
        height: 250px;
        object-fit: cover;
        border-radius: 15px;
        width: 100%;
        transition: transform 0.3s ease;
    }

    .gallery-img:hover {
        transform: scale(1.03);
    }
</style>

<div class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-box shadow-lg">
        <h1 class="text-white fw-bold mb-3 font-serif" style="font-size: 2.8rem; letter-spacing: 2px;">Perawatan Motor Premium</h1>
        <p class="text-white-50 fs-5 mb-0 font-serif" style="font-style: italic;">Solusi tepat dari cuci kilat hingga performa mesin maksimal.</p>
    </div>
</div>

<div class="container my-5 py-4">
    <div class="row g-4 justify-content-center">
        <div class="col-md-4">
            <div class="feature-card h-100">
                <i class="fa fa-medal fa-3x mb-3" style="color: var(--rb-dark-navy);"></i>
                <h4 class="font-serif fw-bold" style="color: var(--rb-dark-navy);">Mekanik Handal</h4>
                <p class="text-muted small mb-0">Ditangani langsung oleh mekanik berpengalaman untuk memastikan kendaraan Anda selalu dalam performa terbaik.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <i class="fa fa-tags fa-3x mb-3" style="color: var(--rb-red);"></i>
                <h4 class="font-serif fw-bold" style="color: var(--rb-dark-navy);">Harga Bersahabat</h4>
                <p class="text-muted small mb-0">Kualitas perawatan premium layaknya dealer resmi, namun dengan harga yang ramah di kantong.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card h-100">
                <i class="fa fa-stopwatch fa-3x mb-3" style="color: var(--rb-yellow);"></i>
                <h4 class="font-serif fw-bold" style="color: var(--rb-dark-navy);">Pengerjaan Cepat</h4>
                <p class="text-muted small mb-0">Waktu Anda berharga. Kami menggunakan peralatan modern agar proses service & cuci selesai lebih efisien.</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 py-4" style="background-color: rgba(248, 249, 250, 0.5); border-radius: 20px;">
    <h2 class="section-title">Produk Unggulan Fino Motor</h2>
    <div class="row g-4">
        <?php if (mysqli_num_rows($query_items) > 0): ?>
            <?php while($item = mysqli_fetch_assoc($query_items)): 
                // PARSER GAMBAR: Deteksi URL Internet vs Upload File Lokal
                $img_src = '';
                if (!empty($item['gambar'])) {
                    if (filter_var($item['gambar'], FILTER_VALIDATE_URL)) {
                        $img_src = $item['gambar'];
                    } else {
                        $img_src = "/Fino_Motor/assets/uploads/" . $item['gambar'];
                    }
                }
            ?>
                <div class="col-md-3">
                    <div class="card product-card h-100">
                        <div class="product-img">
                            <?php if (!empty($img_src)): ?>
                                <img src="<?= $img_src; ?>" alt="<?= htmlspecialchars($item['nama_item']); ?>">
                            <?php else: ?>
                                <i class="fa <?= $item['tipe'] == 'jasa' ? 'fa-tools' : 'fa-oil-can' ?> fa-4x opacity-50"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body text-center d-flex flex-column pt-4">
                            <h5 class="card-title font-serif fw-bold text-dark mb-1"><?= $item['nama_item']; ?></h5>
                            
                            <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                                <div class="mt-2 mb-3 text-start bg-light p-2 rounded border" style="font-size: 0.85rem;">
                                    <span class="text-muted d-block">Oli Saja: <b class="text-success">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></b></span>
                                    <span class="text-muted d-block">Plus Pasang: <b class="text-danger">Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?></b></span>
                                </div>
                            <?php else: ?>
                                <h4 class="fw-bold mt-auto mb-3" style="color: var(--rb-red);">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></h4>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                                    <button type="button" class="btn btn-outline-dark w-100 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalOliBeranda<?= $item['id']; ?>">
                                        <i class="fa fa-sliders-h me-1"></i> Pilih Opsi Oli
                                    </button>
                                <?php else: ?>
                                    <?php if ($item['tipe'] == 'barang'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="id_item" value="<?= $item['id']; ?>">
                                            <input type="hidden" name="qty" value="1">
                                            <input type="hidden" name="opsi" value="normal">
                                            <button type="submit" name="tambah_keranjang" class="btn btn-rb-red w-100 rounded-pill">Pesan Sekarang</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Tersedia di Bengkel</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                <div class="modal fade" id="modalOliBeranda<?= $item['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow-lg text-start">
                            <div class="modal-header text-white" style="background-color: #121F45;">
                                <h5 class="modal-title font-serif fw-bold"><i class="fa fa-oil-can text-warning me-2"></i> Opsi Pembelian Oli</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="id_item" value="<?= $item['id']; ?>">
                                <div class="modal-body p-4">
                                    <div class="text-center mb-4">
                                        <?php if (!empty($img_src)): ?>
                                            <img src="<?= $img_src; ?>" class="rounded-3 shadow-sm mb-2" style="width: 110px; height: 120px; object-fit: cover;">
                                        <?php endif; ?>
                                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['nama_item']); ?></h4>
                                        <p class="text-muted small">Pilih jenis layanan penggantian di bawah</p>
                                    </div>

                                    <div class="mb-3">
                                        <div class="p-3 border rounded-3 mb-2 bg-light d-flex align-items-center justify-content-between">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="radio" name="opsi" id="opsiSajaIdx<?= $item['id']; ?>" value="saja" checked>
                                                <label class="form-check-label fw-bold text-dark" for="opsiSajaIdx<?= $item['id']; ?>">Bawa Pulang (Oli Saja)</label>
                                            </div>
                                            <span class="text-success fw-bold">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></span>
                                        </div>

                                        <div class="p-3 border border-danger bg-danger bg-opacity-10 rounded-3 d-flex align-items-center justify-content-between">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="radio" name="opsi" id="opsiPasangIdx<?= $item['id']; ?>" value="pasang">
                                                <label class="form-check-label fw-bold text-dark" for="opsiPasangIdx<?= $item['id']; ?>">Ganti di Bengkel (+ Jasa Pasang)</label>
                                            </div>
                                            <span class="text-danger fw-bold">Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-secondary">Kuantitas (Qty)</label>
                                        <input type="number" name="qty" class="form-control text-center fw-bold" value="1" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="tambah_keranjang" class="btn btn-danger fw-bold px-4"><i class="fa fa-cart-plus me-1"></i> Tambah Keranjang</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">Belum ada produk di database.</div>
        <?php endif; ?>
    </div>
</div>

<div class="cta-banner my-5">
    <div class="container">
        <h2 class="font-serif fw-bold mb-3" style="color: var(--rb-yellow);">JELAJAHI LAYANAN KAMI!</h2>
        <p class="fs-5 mb-4 font-serif" style="font-style: italic;">Ingin melihat berbagai pilihan oli dan daftar harga service lengkap dari Fino Motor?<br>Silakan klik tombol katalog di bawah ini.</p>
        <a href="katalog.php" class="btn btn-light btn-lg fw-bold px-5 rounded-pill" style="color: var(--rb-dark-navy);">Lihat Katalog Penuh</a>
    </div>
</div>

<div class="container my-5 py-4">
    <h2 class="section-title">Galeri Fino Motor</h2>
    <div class="row g-3">
        <div class="col-md-3">
            <img src="https://images.unsplash.com/photo-1625047509168-a7026f36de04?q=80&w=600&auto=format&fit=crop" class="gallery-img shadow-sm" alt="Galeri 1">
        </div>
        <div class="col-md-3">
            <img src="https://images.unsplash.com/photo-1599256872237-5dcc0fbe9668?q=80&w=600&auto=format&fit=crop" class="gallery-img shadow-sm" alt="Galeri 2">
        </div>
        <div class="col-md-3">
            <img src="https://images.unsplash.com/photo-1632249622959-1e359ab8f8ec?q=80&w=600&auto=format&fit=crop" class="gallery-img shadow-sm" alt="Galeri 3">
        </div>
        <div class="col-md-3">
            <img src="https://images.unsplash.com/photo-1508821430932-b258597305aa?q=80&w=600&auto=format&fit=crop" class="gallery-img shadow-sm" alt="Galeri 4">
        </div>
    </div>
</div>

<div class="container-fluid py-5 my-5" style="background-color: #e9ecef;">
    <div class="container">
        <h2 class="section-title mb-5">Apa Kata Pelanggan Kami?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card shadow">
                    <div class="text-center mb-3 text-warning">
                        <i class="fa fa-quote-left fa-2x"></i>
                    </div>
                    <p class="font-serif text-center mb-4" style="font-style: italic; font-size: 1.1rem;">"Oli mesin yang disarankan mekaniknya mantap! Tarikan motor jadi enteng banget buat jalan jauh."</p>
                    <div class="text-center">
                        <h6 class="fw-bold mb-0 text-warning">Budi S.</h6>
                        <small class="text-white-50">Surabaya</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card shadow">
                    <div class="text-center mb-3 text-warning">
                        <i class="fa fa-quote-left fa-2x"></i>
                    </div>
                    <p class="font-serif text-center mb-4" style="font-style: italic; font-size: 1.1rem;">"Cuci saljunya bersih sampai ke sela-sela mesin. Nyaman banget sambil nunggu bisa santai."</p>
                    <div class="text-center">
                        <h6 class="fw-bold mb-0 text-warning">Ahmad Dani</h6>
                        <small class="text-white-50">Bangkalan</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card shadow">
                    <div class="text-center mb-3 text-warning">
                        <i class="fa fa-quote-left fa-2x"></i>
                    </div>
                    <p class="font-serif text-center mb-4" style="font-style: italic; font-size: 1.1rem;">"Booking oli lewat web gampang banget. Datang ke lokasi tinggal pasang tanpa harus antre lama."</p>
                    <div class="text-center">
                        <h6 class="fw-bold mb-0 text-warning">Siti N.</h6>
                        <small class="text-white-50">Arosbaya</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 text-center">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <h4 class="font-serif fw-bold mb-3" style="color: var(--rb-dark-navy);">Dapatkan Info Promo Terbaru</h4>
            <div class="input-group mb-3 shadow-sm rounded-pill overflow-hidden">
                <input type="email" class="form-control border-0 px-4" placeholder="Masukkan email @gmail.com Anda">
                <button class="btn btn-rb-red px-4 fw-bold" type="button">Subscribe</button>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h3 class="font-serif fw-bold mb-3" style="color: var(--rb-dark-navy);">Tentang Fino Motor</h3>
            <p class="text-muted" style="line-height: 1.8;">
                Fino Motor berdiri dengan komitmen memberikan solusi perawatan kendaraan roda dua terbaik di area Bangkalan dan sekitarnya. 
                Kami percaya setiap pengendara berhak mendapatkan suku cadang yang terjamin keasliannya dan pelayanan prima dengan harga yang transparan. 
                Percayakan perawatan motor Anda kepada kami, karena kualitas mesin adalah prioritas utama Fino Motor.
            </p>
        </div>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>