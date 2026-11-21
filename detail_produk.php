<?php
// detail_produk.php (WBS-2.3.2 Halaman Detail Produk)
session_start();
require 'config/database.php';
require 'includes/cart_helper.php';

$id_produk = (int)($_GET['id'] ?? 0);
if ($id_produk <= 0) {
    header("Location: katalog.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$produk) {
    header("Location: katalog.php");
    exit;
}

$ringkasan_cart = getRingkasanKeranjang($conn);
$total_item_cart = $ringkasan_cart['total_qty'] ?? 0;

$foto = !empty($produk['gambar'])
    ? (filter_var($produk['gambar'], FILTER_VALIDATE_URL) ? $produk['gambar'] : "assets/uploads/" . $produk['gambar'])
    : "https://via.placeholder.com/600x450?text=Fino+Motor";

$is_barang = ($produk['tipe'] === 'barang');
$stok      = (int)$produk['stok'];
$habis     = ($is_barang && $stok <= 0);
$is_oli    = ((int)$produk['is_oli'] === 1 && (int)$produk['harga_pasang'] > 0);

// Ambil 4 produk terkait pada kategori yang sama
$stmt_rel = mysqli_prepare($conn, "SELECT * FROM items WHERE kategori = ? AND id != ? LIMIT 4");
mysqli_stmt_bind_param($stmt_rel, "si", $produk['kategori'], $id_produk);
mysqli_stmt_execute($stmt_rel);
$res_related = mysqli_stmt_get_result($stmt_rel);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produk['nama_item']); ?> - Detail Produk Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
        .detail-card { border: none; border-radius: 20px; }
        .detail-img-wrap { background-color: #f8f9fa; border-radius: 16px; overflow: hidden; height: 360px; display: flex; align-items: center; justify-content: center; }
        .detail-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa fa-motorcycle text-warning me-2"></i>FINO MOTOR</a>
        <div class="ms-auto d-flex gap-2">
            <a href="katalog.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Katalog</a>
            <a href="pelanggan/keranjang_belanja.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark position-relative">
                <i class="fa fa-shopping-cart me-1"></i> Keranjang
                <?php if ($total_item_cart > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $total_item_cart; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Breadcrumb Navigasi -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="katalog.php" class="text-decoration-none">Katalog</a></li>
            <li class="breadcrumb-item"><a href="katalog.php?kategori=<?= urlencode($produk['kategori']); ?>" class="text-decoration-none"><?= htmlspecialchars($produk['kategori']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($produk['nama_item']); ?></li>
        </ol>
    </nav>

    <div class="card detail-card shadow-sm bg-white p-4 p-md-5 mb-5">
        <div class="row g-4 align-items-center">
            <!-- Foto Produk -->
            <div class="col-lg-5">
                <div class="detail-img-wrap border">
                    <img src="<?= htmlspecialchars($foto); ?>" alt="<?= htmlspecialchars($produk['nama_item']); ?>">
                </div>
            </div>

            <!-- Informasi Rinci Produk (WBS-2.3.2) -->
            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge bg-primary px-3 py-2 rounded-pill"><?= htmlspecialchars($produk['kategori']); ?></span>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill text-uppercase"><?= htmlspecialchars($produk['tipe']); ?></span>
                    <?php if ($is_barang): ?>
                        <span class="badge <?= $habis ? 'bg-danger' : 'bg-success'; ?> px-3 py-2 rounded-pill">
                            <?= $habis ? 'Stok Habis' : 'Tersedia: ' . $stok . ' unit'; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">Siap Dilayani di Bengkel</span>
                    <?php endif; ?>
                </div>

                <h2 class="fw-bold text-dark mb-3"><?= htmlspecialchars($produk['nama_item']); ?></h2>

                <div class="bg-light p-3 rounded-4 mb-3">
                    <small class="text-muted d-block">Harga Produk</small>
                    <h3 class="fw-bold text-success m-0" id="tampilHarga">
                        Rp <?= number_format($produk['harga'], 0, ',', '.'); ?>
                    </h3>
                </div>

                <h6 class="fw-bold text-secondary small text-uppercase">Deskripsi & Spesifikasi</h6>
                <p class="text-muted mb-3">
                    <?= nl2br(htmlspecialchars($produk['deskripsi'] ?: 'Produk original dan bergaransi kualitas dari Fino Motor Bangkalan.')); ?>
                </p>

                <?php if ($is_barang): ?>
                    <p class="small text-secondary mb-4">
                        <i class="fa fa-weight-hanging me-1"></i> Estimasi Berat Pengiriman: <strong><?= number_format($produk['berat'] ?? 800, 0, ',', '.'); ?> gram</strong>
                    </p>
                <?php endif; ?>

                <!-- Form Tambah ke Keranjang -->
                <?php if ($habis): ?>
                    <div class="alert alert-danger fw-bold rounded-3">
                        <i class="fa fa-times-circle me-2"></i> Maaf, stok produk ini sedang kosong.
                    </div>
                <?php else: ?>
                    <form method="POST" action="pelanggan/proses_keranjang.php">
                        <input type="hidden" name="aksi" value="tambah">
                        <input type="hidden" name="item_id" value="<?= $produk['id']; ?>">
                        <input type="hidden" name="redirect" value="keranjang_belanja.php">

                        <?php if ($is_oli): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Pilih Paket Pembelian Oli:</label>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <div class="form-check border rounded-3 p-3">
                                            <input class="form-check-input" type="radio" name="opsi" id="opsiSaja" value="saja" checked onchange="ubahHarga(<?= (int)$produk['harga']; ?>)">
                                            <label class="form-check-label fw-bold d-block" for="opsiSaja">
                                                Bawa Pulang / Kirim Kurir
                                                <small class="d-block text-success">Rp <?= number_format($produk['harga'], 0, ',', '.'); ?></small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-check border rounded-3 p-3">
                                            <input class="form-check-input" type="radio" name="opsi" id="opsiPasang" value="pasang" onchange="ubahHarga(<?= (int)$produk['harga_pasang']; ?>)">
                                            <label class="form-check-label fw-bold d-block" for="opsiPasang">
                                                Oli + Jasa Pasang di Bengkel
                                                <small class="d-block text-danger">Rp <?= number_format($produk['harga_pasang'], 0, ',', '.'); ?></small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="opsi" value="normal">
                        <?php endif; ?>

                        <div class="row g-3 align-items-end">
                            <div class="col-4 col-sm-3">
                                <label class="form-label small fw-bold">Jumlah</label>
                                <input type="number" name="qty" class="form-control form-control-lg text-center fw-bold" value="1" min="1" max="<?= $is_barang ? $stok : 10; ?>" required>
                            </div>
                            <div class="col-8 col-sm-9 d-flex gap-2">
                                <button type="submit" class="btn btn-warning btn-lg flex-fill fw-bold text-dark rounded-3 shadow-sm">
                                    <i class="fa fa-cart-plus me-2"></i> Masukkan ke Keranjang
                                </button>
                                <a href="katalog.php" class="btn btn-outline-secondary btn-lg rounded-3">Kembali</a>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Produk Terkait -->
    <?php if (mysqli_num_rows($res_related) > 0): ?>
        <h5 class="fw-bold mb-3" style="color: #121F45;">Produk Serupa di Kategori <?= htmlspecialchars($produk['kategori']); ?></h5>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
            <?php while ($rel = mysqli_fetch_assoc($res_related)):
                $rel_foto = !empty($rel['gambar'])
                    ? (filter_var($rel['gambar'], FILTER_VALIDATE_URL) ? $rel['gambar'] : "assets/uploads/" . $rel['gambar'])
                    : "https://via.placeholder.com/400x300?text=Fino+Motor";
            ?>
            <div class="col">
                <a href="detail_produk.php?id=<?= $rel['id']; ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                        <img src="<?= htmlspecialchars($rel_foto); ?>" style="height: 140px; object-fit: cover;" alt="<?= htmlspecialchars($rel['nama_item']); ?>">
                        <div class="card-body p-3">
                            <h6 class="fw-bold small mb-1 text-truncate"><?= htmlspecialchars($rel['nama_item']); ?></h6>
                            <span class="fw-bold text-success small">Rp <?= number_format($rel['harga'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function ubahHarga(nominal) {
    document.getElementById('tampilHarga').innerText = 'Rp ' + nominal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>
</body>
</html>