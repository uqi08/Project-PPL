<?php
// katalog.php (WBS-2.3.1 Halaman Katalog & WBS-2.3.3 Pencarian Produk)
session_start();
require 'config/database.php';
require 'includes/cart_helper.php';

$flash_cart = $_SESSION['flash_cart'] ?? null;
unset($_SESSION['flash_cart']);

// WBS-2.3.3: Filter Kategori & Pencarian Kata Kunci (mendukung parameter ?q= dari index.php maupun ?search=)
$kategori_aktif = trim($_GET['kategori'] ?? 'Semua');
$search         = trim($_GET['q'] ?? $_GET['search'] ?? '');

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

if ($search !== '') {
    $conditions[] = "(nama_item LIKE ? OR kategori LIKE ? OR deskripsi LIKE ?)";
    $like         = '%' . $search . '%';
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
$query_items = mysqli_stmt_get_result($stmt);

// Memanggil header/navbar rapi dari Project-PPL
include 'includes/header.php';
?>

<div class="py-5 text-white text-center position-relative mb-4" style="background: linear-gradient(135deg, #121F45 0%, #223971 100%); border-bottom: 4px solid #FFC906; margin-top: -20px;">
    <div class="container py-4">
        <h1 class="display-4 fw-style font-serif text-uppercase mb-2" style="font-weight: 900; letter-spacing: 2px;">Katalog Produk & Layanan</h1>
        <p class="lead opacity-75">Temukan suku cadang berkualitas, oli original, hingga layanan cuci motor terbaik dari Fino Motor.</p>
        
        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <form method="GET" action="katalog.php" class="input-group shadow-sm">
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_aktif); ?>">
                    <input type="text" name="q" class="form-control form-control-lg rounded-start-pill border-0 ps-4 fs-6" placeholder="Cari oli, ban, atau nama jasa..." value="<?= htmlspecialchars($search); ?>">
                    <?php if ($search !== ''): ?>
                        <a href="katalog.php?kategori=<?= urlencode($kategori_aktif); ?>" class="btn btn-light text-muted d-flex align-items-center" title="Reset Pencarian">
                            <i class="fa fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-warning rounded-end-pill px-4 fw-bold text-dark"><i class="fa fa-search me-1"></i> Cari</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?php if ($flash_cart): ?>
        <div class="alert alert-<?= htmlspecialchars($flash_cart['tipe']); ?> rounded-4 shadow-sm fw-bold d-flex justify-content-between align-items-center mb-4">
            <span><i class="fa fa-info-circle me-2"></i><?= htmlspecialchars($flash_cart['pesan']); ?></span>
            <a href="pelanggan/keranjang_belanja.php" class="btn btn-sm btn-dark rounded-pill px-3">Lihat Keranjang</a>
        </div>
    <?php endif; ?>

    <!-- Filter Kategori (WBS-2.3.3) -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <?php
        $daftar_kategori = ['Semua', 'Oli', 'Ban', 'Sparepart', 'Perawatan Motor', 'Jasa'];
        foreach ($daftar_kategori as $kat):
            $is_active = ($kategori_aktif === $kat);
            $url_kat   = 'katalog.php?kategori=' . urlencode($kat) . ($search !== '' ? '&q=' . urlencode($search) : '');
        ?>
            <a href="<?= $url_kat; ?>" class="btn rounded-pill px-3 fw-semibold <?= $is_active ? 'btn-dark shadow-sm' : 'btn-white bg-white border text-secondary'; ?>">
                <?= htmlspecialchars($kat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($search !== ''): ?>
        <p class="text-center text-muted mb-4">
            Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($search); ?>"</strong>
            (<?= mysqli_num_rows($query_items); ?> item ditemukan)
        </p>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (mysqli_num_rows($query_items) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_items)): 
                // Deteksi Jalur Gambar (Path relatif Project-PPL vs URL Internet)
                $img_src = '';
                if (!empty($row['gambar'])) {
                    if (filter_var($row['gambar'], FILTER_VALIDATE_URL)) {
                        $img_src = $row['gambar'];
                    } else {
                        $img_src = "assets/uploads/" . $row['gambar'];
                    }
                } else {
                    $img_src = "https://via.placeholder.com/400x300?text=Fino+Motor";
                }

                $is_barang = ($row['tipe'] === 'barang');
                $stok      = (int)$row['stok'];
                $habis     = ($is_barang && $stok <= 0);
            ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative bg-white transition-card" style="transition: transform 0.3s ease;">
                    
                    <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 text-uppercase shadow-sm z-3 <?= $is_barang ? 'bg-primary' : 'bg-success' ?>">
                        <?= htmlspecialchars($row['tipe']); ?>
                    </span>

                    <!-- Badge Ketersediaan Stok (WBS-2.3.1) -->
                    <?php if ($is_barang): ?>
                        <span class="badge position-absolute top-0 end-0 m-3 px-2 py-1 shadow-sm z-3 <?= $habis ? 'bg-danger' : 'bg-dark bg-opacity-75'; ?>">
                            <?= $habis ? 'Stok Habis' : 'Stok: ' . $stok; ?>
                        </span>
                    <?php endif; ?>

                    <!-- Klik gambar menuju Halaman Detail Produk (WBS-2.3.2) -->
                    <a href="detail_produk.php?id=<?= $row['id']; ?>" class="text-decoration-none">
                        <div style="height: 200px; width: 100%; overflow: hidden; background-color: #eee;">
                            <img src="<?= htmlspecialchars($img_src); ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($row['nama_item']); ?>">
                        </div>
                    </a>

                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <a href="detail_produk.php?id=<?= $row['id']; ?>" class="text-decoration-none">
                                <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_item']); ?></h5>
                            </a>
                            
                            <?php if ((int)$row['is_oli'] === 1): ?>
                                <div class="mt-2">
                                    <span class="text-muted d-block small">Oli Saja: <b class="text-success">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></b></span>
                                    <span class="text-muted d-block small">Plus Pasang: <b class="text-danger">Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></b></span>
                                </div>
                            <?php else: ?>
                                <h4 class="text-success fw-bold mt-2">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h4>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 d-flex flex-column gap-2">
                            <!-- Tombol Halaman Detail Produk (WBS-2.3.2) -->
                            <a href="detail_produk.php?id=<?= $row['id']; ?>" class="btn btn-outline-secondary btn-sm w-100 fw-bold rounded-3">
                                <i class="fa fa-eye me-1"></i> Lihat Detail
                            </a>

                            <?php if ($habis): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold py-2 rounded-3" disabled>
                                    Stok Habis
                                </button>
                            <?php elseif ((int)$row['is_oli'] === 1): ?>
                                <button type="button" class="btn btn-outline-dark w-100 fw-bold py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#modalOli<?= $row['id']; ?>">
                                    <i class="fa fa-sliders me-1"></i> Pilih Opsi Oli
                                </button>
                            <?php else: ?>
                                <!-- Tersambung ke WBS-2.4 (pelanggan/proses_keranjang.php) -->
                                <form method="POST" action="pelanggan/proses_keranjang.php">
                                    <input type="hidden" name="aksi" value="tambah">
                                    <input type="hidden" name="item_id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="opsi" value="normal">
                                    <input type="hidden" name="redirect" value="../katalog.php?kategori=<?= urlencode($kategori_aktif); ?>&q=<?= urlencode($search); ?>">
                                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2 rounded-3">
                                        <i class="fa fa-shopping-basket me-1"></i> Pesan Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ((int)$row['is_oli'] === 1): ?>
            <div class="modal fade" id="modalOli<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: #121F45;">
                            <h5 class="modal-title font-serif fw-bold"><i class="fa fa-oil-can text-warning me-2"></i> Opsi Pemasangan Oli</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="pelanggan/proses_keranjang.php">
                            <input type="hidden" name="aksi" value="tambah">
                            <input type="hidden" name="item_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="redirect" value="../katalog.php?kategori=<?= urlencode($kategori_aktif); ?>&q=<?= urlencode($search); ?>">
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="<?= htmlspecialchars($img_src); ?>" class="rounded-3 shadow-sm mb-2" style="width: 120px; height: 120px; object-fit: cover;">
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
                                    <input type="number" name="qty" class="form-control text-center fw-bold fs-5" value="1" min="1" max="<?= max(1, $stok); ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger fw-bold px-4"><i class="fa fa-cart-plus me-1"></i> Masukkan Keranjang</button>
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
                <a href="katalog.php" class="btn btn-dark rounded-pill px-4 mt-2 fw-bold">Reset Katalog</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .transition-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
</style>

<?php 
include 'includes/footer.php'; 
?>