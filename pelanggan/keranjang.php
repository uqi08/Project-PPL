<?php
// pelanggan/keranjang.php (WBS-2.4 Halaman Keranjang Belanja)
session_start();
require '../config/database.php';
require '../includes/cart_helper.php';

// Proteksi: Harus login sebagai pelanggan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan   = '';

// --- LOGIKA HAPUS ITEM DARI KERANJANG ---
if (isset($_GET['hapus'])) {
    $key_hapus = $_GET['hapus'];
    if (isset($_SESSION['keranjang'][$key_hapus])) {
        unset($_SESSION['keranjang'][$key_hapus]);
    }
    header("Location: keranjang.php");
    exit;
}

// --- LOGIKA PROSES SELESAIKAN BOOKING (JIKA LANGSUNG BOOKING) ---
if (isset($_POST['proses_booking']) && !empty($_SESSION['keranjang'])) {
    $tanggal_booking = mysqli_real_escape_string($conn, $_POST['tanggal_booking']);
    $waktu_booking   = mysqli_real_escape_string($conn, $_POST['waktu_booking']);
    $waktu_dibuat    = date('Y-m-d H:i:s');

    $ringkasan_booking = getRingkasanKeranjang($conn);
    $grand_total       = (int)$ringkasan_booking['subtotal_keranjang'];

    $query_transaksi = "INSERT INTO transactions (user_id, total_harga, status, tanggal_booking, waktu_booking, created_at) 
                        VALUES ($user_id, $grand_total, 'pending', '$tanggal_booking', '$waktu_booking', '$waktu_dibuat')";

    if (mysqli_query($conn, $query_transaksi)) {
        $id_transaksi_baru = mysqli_insert_id($conn);

        foreach ($ringkasan_booking['items'] as $itm) {
            $id_item  = (int)$itm['id'];
            $qty      = (int)$itm['qty'];
            $harga    = (int)$itm['harga_satuan'];
            $subtotal = (int)$itm['subtotal'];

            mysqli_query($conn, "INSERT INTO transaction_details (transaction_id, item_id, qty, harga, subtotal) 
                                 VALUES ($id_transaksi_baru, $id_item, $qty, $harga, $subtotal)");
        }

        unset($_SESSION['keranjang']);

        echo "<script>
                alert('Booking Berhasil Dibuat! Silakan datang ke bengkel sesuai jadwal.'); 
                window.location.href='riwayat.php';
              </script>";
        exit;
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal memproses booking. Silakan coba kembali.</div>";
    }
}

// Ambil ringkasan keranjang (Total Kuantitas, Estimasi Berat Total, Subtotal Keranjang)
$ringkasan = getRingkasanKeranjang($conn);
$flash     = $_SESSION['flash_cart'] ?? null;
unset($_SESSION['flash_cart']);

// Panggil navbar rapi milikmu
include '../includes/header.php';
?>

<div class="container my-5" style="min-height: 70vh;">
    <h2 class="font-serif fw-bold mb-4" style="color: var(--rb-dark-navy);">
        <i class="fa fa-shopping-cart text-danger me-2"></i>Keranjang Belanja & Jadwal Booking
    </h2>

    <?= $pesan; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']); ?> rounded-4 shadow-sm fw-bold">
            <?= htmlspecialchars($flash['pesan']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- KOLOM KIRI: DAFTAR ITEM KERANJANG -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                <?php if (!empty($ringkasan['items'])): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-muted small">Daftar Produk (<?= $ringkasan['total_qty']; ?> Item)</span>
                        <form method="POST" action="proses_keranjang.php" onsubmit="return confirm('Kosongkan seluruh keranjang?');">
                            <input type="hidden" name="aksi" value="kosongkan">
                            <input type="hidden" name="redirect" value="keranjang.php">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">
                                <i class="fa fa-trash-alt me-1"></i> Kosongkan
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Item Pesanan</th>
                                <th>Jenis Pembelian</th>
                                <th>Harga Satuan</th>
                                <th class="text-center" style="width: 145px;">Jumlah</th>
                                <th>Subtotal</th>
                                <th class="text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ringkasan['items'])): ?>
                                <?php foreach ($ringkasan['items'] as $itm): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <a href="../detail_produk.php?id=<?= $itm['id']; ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($itm['nama_item']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($itm['opsi_item'] === 'bawa_pulang'): ?>
                                            <span class="badge bg-secondary px-2 py-1"><i class="fa fa-box me-1"></i> Oli Saja (Bawa Pulang)</span>
                                        <?php elseif ($itm['opsi_item'] === 'plus_pasang'): ?>
                                            <span class="badge bg-danger px-2 py-1"><i class="fa fa-wrench me-1"></i> Plus Jasa Pasang Bengkel</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border text-uppercase px-2 py-1"><?= htmlspecialchars($itm['tipe']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?= number_format($itm['harga_satuan'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <!-- Ubah Jumlah Produk (WBS-2.4.2) -->
                                        <form method="POST" action="proses_keranjang.php" class="d-flex justify-content-center gap-1">
                                            <input type="hidden" name="aksi" value="ubah">
                                            <input type="hidden" name="cart_key" value="<?= htmlspecialchars($itm['cart_key']); ?>">
                                            <input type="hidden" name="redirect" value="keranjang.php">
                                            <input type="number" name="qty" class="form-control form-control-sm text-center fw-bold"
                                                   value="<?= $itm['qty']; ?>" min="1" max="<?= $itm['tipe'] === 'barang' ? max(1, $itm['stok']) : 10; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Simpan Jumlah">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="fw-bold text-success">Rp <?= number_format($itm['subtotal'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <a href="keranjang.php?hapus=<?= urlencode($itm['cart_key']); ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus item ini dari keranjang?');">
                                            <i class="fa fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-shopping-basket fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">Keranjang belanjamu masih kosong melompong.</p>
                                        <a href="../katalog.php" class="btn btn-sm btn-primary rounded-pill px-3 mt-3 fw-bold">Cari Produk / Layanan</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: RINGKASAN BELANJA (WBS-2.4.3) & KONFIRMASI BOOKING -->
        <div class="col-lg-4">
            <!-- Kartu 1: Ringkasan Belanja (Dipindahkan dari keranjang_belanja.php) -->
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 text-dark">Ringkasan Belanja</h5>
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
                    <span class="fw-bold text-dark">Subtotal Keranjang</span>
                    <span class="fw-bold fs-4 text-success">Rp <?= number_format($ringkasan['subtotal_keranjang'], 0, ',', '.'); ?></span>
                </div>

                <?php if (!empty($ringkasan['items'])): ?>
                    <a href="checkout_pesanan.php" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                        Lanjut ke Checkout <i class="fa fa-arrow-right ms-2"></i>
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary w-100 py-3 fw-bold rounded-3 opacity-50" disabled>
                        Keranjang Kosong
                    </button>
                <?php endif; ?>
            </div>

            <!-- Kartu 2: Konfirmasi Booking Bengkel -->
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white border-top border-5 border-warning">
                <h5 class="fw-bold mb-4 font-serif text-dark"><i class="fa fa-calendar-check text-muted me-2"></i>Konfirmasi Booking</h5>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Rencana Tanggal Kedatangan</label>
                        <input type="date" name="tanggal_booking" class="form-control" value="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Jam Kedatangan Servis</label>
                        <input type="time" name="waktu_booking" class="form-control" required>
                        <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;"><i class="fa fa-info-circle"></i> Jam operasional: 08:00 - 16:30 WIB</small>
                    </div>

                    <hr class="my-4 opacity-25">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted fw-bold">Total Pembayaran:</span>
                        <h4 class="text-success fw-bold m-0">Rp <?= number_format($ringkasan['subtotal_keranjang'], 0, ',', '.'); ?></h4>
                    </div>

                    <?php if ($ringkasan['subtotal_keranjang'] > 0): ?>
                        <button type="submit" name="proses_booking" class="btn btn-danger w-100 py-3 fw-bold rounded-3 shadow-sm fs-5 text-uppercase">
                            <i class="fa fa-check-circle me-2"></i> Selesaikan Booking
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100 py-3 fw-bold rounded-3 opacity-50" disabled>Keranjang Kosong</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Panggil footer rapi milikmu
include '../includes/footer.php';
?>