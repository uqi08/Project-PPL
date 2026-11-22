<?php
// pelanggan/keranjang_belanja.php (WBS-2.4 Halaman Keranjang Belanja)
session_start();
require '../config/database.php';
require '../includes/cart_helper.php';

$ringkasan = getRingkasanKeranjang($conn);
$flash = $_SESSION['flash_cart'] ?? null;
unset($_SESSION['flash_cart']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
        .card-clean { border: none; border-radius: 16px; }
        .thumb-cart { width: 68px; height: 68px; object-fit: cover; border-radius: 12px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fa fa-motorcycle text-warning me-2"></i>FINO MOTOR</a>
        <div class="ms-auto d-flex gap-2">
            <a href="../katalog.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Lanjut Belanja</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pesanan_saya.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark">Pesanan Saya</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <h3 class="fw-bold mb-4" style="color: #121F45;">
        <i class="fa fa-shopping-cart text-warning me-2"></i>Keranjang Belanja Anda
    </h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']); ?> rounded-4 shadow-sm fw-bold">
            <?= htmlspecialchars($flash['pesan']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ringkasan['items'])): ?>
        <div class="card card-clean shadow-sm p-5 text-center bg-white">
            <i class="fa fa-shopping-basket fa-4x text-muted opacity-25 mb-3"></i>
            <h5 class="fw-bold">Keranjang belanja masih kosong</h5>
            <p class="text-muted">Yuk pilih oli, sparepart, atau layanan servis di halaman katalog.</p>
            <div>
                <a href="../katalog.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fa fa-store me-2"></i>Lihat Katalog Produk
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Daftar Item Keranjang -->
            <div class="col-lg-8">
                <div class="card card-clean shadow-sm p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0">Daftar Produk (<?= $ringkasan['total_qty']; ?> Item)</h5>
                        <form method="POST" action="proses_keranjang.php" onsubmit="return confirm('Kosongkan seluruh keranjang?');">
                            <input type="hidden" name="aksi" value="kosongkan">
                            <input type="hidden" name="redirect" value="keranjang_belanja.php">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">
                                <i class="fa fa-trash-alt me-1"></i> Kosongkan
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th style="width: 150px;">Jumlah</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ringkasan['items'] as $itm):
                                    $foto = !empty($itm['gambar'])
                                        ? (filter_var($itm['gambar'], FILTER_VALIDATE_URL) ? $itm['gambar'] : "../assets/uploads/" . $itm['gambar'])
                                        : "https://via.placeholder.com/150?text=Item";
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($foto); ?>" class="thumb-cart border" alt="">
                                            <div>
                                                <a href="../detail_produk.php?id=<?= $itm['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= htmlspecialchars($itm['nama_item']); ?>
                                                </a>
                                                <div>
                                                    <?php if ($itm['opsi_item'] === 'plus_pasang'): ?>
                                                        <span class="badge bg-danger">Oli + Pasang</span>
                                                    <?php elseif ($itm['opsi_item'] === 'bawa_pulang'): ?>
                                                        <span class="badge bg-secondary">Bawa Pulang</span>
                                                    <?php endif; ?>
                                                    <?php if ($itm['tipe'] === 'barang'): ?>
                                                        <small class="text-muted ms-1">Stok: <?= $itm['stok']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">Rp <?= number_format($itm['harga_satuan'], 0, ',', '.'); ?></td>
                                    <td>
                                        <!-- WBS-2.4.2: Ubah Jumlah Produk -->
                                        <form method="POST" action="proses_keranjang.php" class="d-flex gap-1">
                                            <input type="hidden" name="aksi" value="ubah">
                                            <input type="hidden" name="cart_key" value="<?= htmlspecialchars($itm['cart_key']); ?>">
                                            <input type="hidden" name="redirect" value="keranjang_belanja.php">
                                            <input type="number" name="qty" class="form-control form-control-sm text-center fw-bold"
                                                   value="<?= $itm['qty']; ?>" min="1" max="<?= $itm['tipe'] === 'barang' ? $itm['stok'] : 10; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Simpan Jumlah">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="fw-bold text-success">Rp <?= number_format($itm['subtotal'], 0, ',', '.'); ?></td>
                                    <td class="text-end">
                                        <!-- WBS-2.4.2: Hapus Produk -->
                                        <a href="proses_keranjang.php?aksi=hapus&cart_key=<?= urlencode($itm['cart_key']); ?>&redirect=keranjang_belanja.php"
                                           class="btn btn-sm btn-outline-danger rounded-3" title="Hapus Item"
                                           onclick="return confirm('Hapus produk ini dari keranjang?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Subtotal (WBS-2.4.3) -->
            <div class="col-lg-4">
                <div class="card card-clean shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-3">Ringkasan Belanja</h5>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Total Kuantitas</span>
                        <span class="fw-bold text-dark"><?= $ringkasan['total_qty']; ?> item</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span>Estimasi Berat Total</span>
                        <span class="fw-bold text-dark"><?= number_format($ringkasan['total_berat_gram'], 0, ',', '.'); ?> gram</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold">Subtotal Keranjang</span>
                        <span class="fw-bold fs-4 text-success">Rp <?= number_format($ringkasan['subtotal_keranjang'], 0, ',', '.'); ?></span>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="checkout_pesanan.php" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                            Lanjut ke Checkout <i class="fa fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn btn-warning w-100 py-3 fw-bold text-dark rounded-3 shadow-sm">
                            <i class="fa fa-sign-in-alt me-2"></i> Login untuk Checkout
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>