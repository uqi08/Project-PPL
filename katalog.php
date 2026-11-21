<?php
// katalog.php (WBS-2.3.1 Halaman Katalog & WBS-2.3.3 Pencarian Produk)
session_start();
require 'config/database.php';
require 'includes/cart_helper.php';

$ringkasan_cart = getRingkasanKeranjang($conn);
$total_item_cart = $ringkasan_cart['total_qty'] ?? 0;

$flash_cart = $_SESSION['flash_cart'] ?? null;
unset($_SESSION['flash_cart']);

// WBS-2.3.3: Filter & Pencarian Kata Kunci
$kategori_aktif = trim($_GET['kategori'] ?? 'Semua');
$keyword        = trim($_GET['q'] ?? '');

$conditions = [];
$params     = [];
$types      = '';

if ($kategori_aktif !== '' && $kategori_aktif !== 'Semua') {
    if ($kategori_aktif === 'Jasa') {
        $conditions[] = "tipe = 'jasa'";
    } else {
        $conditions[] = "kategori = ?";
        $params[]     = $kategori_aktif;
        $types       .= 's';
    }
}

if ($keyword !== '') {
    $conditions[] = "(nama_item LIKE ? OR kategori LIKE ? OR deskripsi LIKE ?)";
    $like         = '%' . $keyword . '%';
    $params[]     = $like;
    $params[]     = $like;
    $params[]     = $like;
    $types       .= 'sss';
}

$sql = "SELECT * FROM items";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY tipe ASC, nama_item ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result_items = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk & Layanan - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
        .hero-katalog { background: linear-gradient(135deg, #121F45 0%, #1e3570 100%); color: white; border-radius: 20px; }
        .product-card { border: none; border-radius: 16px; transition: transform 0.25s ease, box-shadow 0.25s ease; overflow: hidden; }
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(18, 31, 69, 0.12); }
        .img-box { height: 190px; background-color: #e9ecef; position: relative; overflow: hidden; }
        .img-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .product-card:hover .img-box img { transform: scale(1.05); }
        .filter-pill { border-radius: 50px; font-weight: 600; padding: 8px 18px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <img src="assets/logo.png" alt="Fino Motor" style="height: 38px;" class="me-2" onerror="this.style.display='none'">
            <span>FINO MOTOR</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Beranda</a>
            <a href="pelanggan/keranjang_belanja.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark position-relative">
                <i class="fa fa-shopping-cart me-1"></i> Keranjang
                <?php if ($total_item_cart > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $total_item_cart; ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pelanggan/pesanan_saya.php" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">Pesanan Saya</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Banner & Form Pencarian Produk (WBS-2.3.3) -->
    <div class="hero-katalog p-4 p-md-5 mb-4 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-2" style="font-family: 'Playfair Display', serif;">Katalog Sparepart, Oli & Jasa Servis</h2>
                <p class="text-white-50 mb-0">Temukan suku cadang asli, oli motor terbaik, dan layanan perawatan Fino Motor.</p>
            </div>
            <div class="col-lg-6">
                <form method="GET" action="katalog.php" class="d-flex gap-2">
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_aktif); ?>">
                    <div class="input-group input-group-lg shadow">
                        <input type="text" name="q" class="form-control border-0" placeholder="Cari oli, ban, busi, atau cuci motor..." value="<?= htmlspecialchars($keyword); ?>">
                        <?php if ($keyword !== ''): ?>
                            <a href="katalog.php?kategori=<?= urlencode($kategori_aktif); ?>" class="btn btn-light text-muted d-flex align-items-center" title="Reset Pencarian">
                                <i class="fa fa-times"></i>
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
                            <i class="fa fa-search me-1"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($flash_cart): ?>
        <div class="alert alert-<?= htmlspecialchars($flash_cart['tipe']); ?> rounded-4 shadow-sm fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fa fa-info-circle me-2"></i><?= htmlspecialchars($flash_cart['pesan']); ?></span>
            <a href="pelanggan/keranjang_belanja.php" class="btn btn-sm btn-dark rounded-pill px-3">Lihat Keranjang</a>
        </div>
    <?php endif; ?>

    <!-- Filter Kategori -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php
        $daftar_kategori = ['Semua', 'Oli', 'Ban', 'Sparepart', 'Perawatan Motor', 'Jasa'];
        foreach ($daftar_kategori as $kat):
            $is_active = ($kategori_aktif === $kat);
            $url_kat = 'katalog.php?kategori=' . urlencode($kat) . ($keyword !== '' ? '&q=' . urlencode($keyword) : '');
        ?>
            <a href="<?= $url_kat; ?>" class="btn filter-pill <?= $is_active ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary'; ?>">
                <?= htmlspecialchars($kat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($keyword !== ''): ?>
        <p class="text-muted mb-3">
            Menampilkan hasil pencarian untuk kata kunci: <strong>"<?= htmlspecialchars($keyword); ?>"</strong>
            (<?= mysqli_num_rows($result_items); ?> item ditemukan)
        </p>
    <?php endif; ?>

    <!-- Grid Daftar Produk (WBS-2.3.1) -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (mysqli_num_rows($result_items) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($result_items)):
                $foto = !empty($item['gambar'])
                    ? (filter_var($item['gambar'], FILTER_VALIDATE_URL) ? $item['gambar'] : "assets/uploads/" . $item['gambar'])
                    : "https://via.placeholder.com/400x300?text=Fino+Motor";

                $is_barang = ($item['tipe'] === 'barang');
                $stok      = (int)$item['stok'];
                $habis     = ($is_barang && $stok <= 0);
            ?>
            <div class="col">
                <div class="card product-card h-100 bg-white shadow-sm">
                    <a href="detail_produk.php?id=<?= $item['id']; ?>" class="text-decoration-none">
                        <div class="img-box">
                            <img src="<?= htmlspecialchars($foto); ?>" alt="<?= htmlspecialchars($item['nama_item']); ?>">
                            <span class="position-absolute top-0 start-0 m-2 badge <?= $is_barang ? 'bg-primary' : 'bg-success'; ?> text-uppercase">
                                <?= htmlspecialchars($item['kategori']); ?>
                            </span>
                            <?php if ($is_barang): ?>
                                <span class="position-absolute bottom-0 end-0 m-2 badge <?= $habis ? 'bg-danger' : 'bg-dark bg-opacity-75'; ?>">
                                    <?= $habis ? 'Stok Habis' : 'Stok: ' . $stok; ?>
                                </span>
                            <?php else: ?>
                                <span class="position-absolute bottom-0 end-0 m-2 badge bg-success">Layanan Tersedia</span>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column p-3">
                        <a href="detail_produk.php?id=<?= $item['id']; ?>" class="text-decoration-none text-dark">
                            <h6 class="fw-bold mb-1 lh-sm"><?= htmlspecialchars($item['nama_item']); ?></h6>
                        </a>
                        <p class="text-muted small mb-2 text-truncate">
                            <?= htmlspecialchars($item['deskripsi'] ?? 'Produk & layanan berkualitas Fino Motor.'); ?>
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
                            <i class="fa fa-eye me-1"></i> Detail
                        </a>
                        <?php if ($habis): ?>
                            <button type="button" class="btn btn-secondary btn-sm w-50 fw-bold rounded-3" disabled>Habis</button>
                        <?php else: ?>
                            <form method="POST" action="pelanggan/proses_keranjang.php" class="w-50">
                                <input type="hidden" name="aksi" value="tambah">
                                <input type="hidden" name="item_id" value="<?= $item['id']; ?>">
                                <input type="hidden" name="qty" value="1">
                                <input type="hidden" name="opsi" value="<?= (int)$item['is_oli'] === 1 ? 'saja' : 'normal'; ?>">
                                <input type="hidden" name="redirect" value="../katalog.php?kategori=<?= urlencode($kategori_aktif); ?>&q=<?= urlencode($keyword); ?>">
                                <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark rounded-3">
                                    <i class="fa fa-cart-plus me-1"></i> + Keranjang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 w-100 text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="fa fa-search fa-3x text-muted opacity-25 mb-3"></i>
                <h5 class="fw-bold">Produk tidak ditemukan</h5>
                <p class="text-muted">Coba gunakan kata kunci lain atau pilih kategori "Semua".</p>
                <a href="katalog.php" class="btn btn-primary rounded-pill px-4 fw-bold">Reset Katalog</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>