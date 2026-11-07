<?php
// pelanggan/keranjang.php
session_start();
require '../config/database.php';

// Proteksi: Harus login sebagai pelanggan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan = '';

// --- LOGIKA HAPUS ITEM DARI KERANJANG ---
if (isset($_GET['hapus'])) {
    $key_hapus = $_GET['hapus'];
    if (isset($_SESSION['keranjang'][$key_hapus])) {
        unset($_SESSION['keranjang'][$key_hapus]);
    }
    header("Location: keranjang.php");
    exit;
}

// --- LOGIKA PROSES SELESAIKAN BOOKING (CHECKOUT) ---
if (isset($_POST['proses_booking']) && !empty($_SESSION['keranjang'])) {
    $tanggal_booking = mysqli_real_escape_string($conn, $_POST['tanggal_booking']);
    $waktu_booking   = mysqli_real_escape_string($conn, $_POST['waktu_booking']);
    $waktu_dibuat    = date('Y-m-d H:i:s');

    // 1. Hitung ulang total harga seluruh isi keranjang secara aman di sisi server
    $grand_total = 0;
    foreach ($_SESSION['keranjang'] as $cart_key => $qty) {
        // Pecah KEY untuk mendeteksi opsi oli
        if (strpos($cart_key, '_saja') !== false) {
            $id_item = (int)str_replace('_saja', '', $cart_key);
            $opsi = 'saja';
        } elseif (strpos($cart_key, '_pasang') !== false) {
            $id_item = (int)str_replace('_pasang', '', $cart_key);
            $opsi = 'pasang';
        } else {
            $id_item = (int)$cart_key;
            $opsi = 'normal';
        }

        $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga, harga_pasang FROM items WHERE id = $id_item"));
        if ($item) {
            $harga_berlaku = ($opsi === 'pasang') ? $item['harga_pasang'] : $item['harga'];
            $grand_total += ($harga_berlaku * $qty);
        }
    }

    // 2. Masukkan data induk transaksi ke tabel transactions (Status awal: pending)
    $query_transaksi = "INSERT INTO transactions (user_id, total_harga, status, tanggal_booking, waktu_booking, created_at) 
                        VALUES ($user_id, $grand_total, 'pending', '$tanggal_booking', '$waktu_booking', '$waktu_dibuat')";
    
    if (mysqli_query($conn, $query_transaksi)) {
        $id_transaksi_baru = mysqli_insert_id($conn);

        // 3. Masukkan rincian item ke tabel transaction_details
        foreach ($_SESSION['keranjang'] as $cart_key => $qty) {
            if (strpos($cart_key, '_saja') !== false) {
                $id_item = (int)str_replace('_saja', '', $cart_key);
                $opsi = 'saja';
            } elseif (strpos($cart_key, '_pasang') !== false) {
                $id_item = (int)str_replace('_pasang', '', $cart_key);
                $opsi = 'pasang';
            } else {
                $id_item = (int)$cart_key;
                $opsi = 'normal';
            }

            $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga, harga_pasang FROM items WHERE id = $id_item"));
            if ($item) {
                $harga_berlaku = ($opsi === 'pasang') ? $item['harga_pasang'] : $item['harga'];
                $subtotal = $harga_berlaku * $qty;

                mysqli_query($conn, "INSERT INTO transaction_details (transaction_id, item_id, qty, harga, subtotal) 
                                     VALUES ($id_transaksi_baru, $id_item, $qty, $harga_berlaku, $subtotal)");
            }
        }

        // 4. Bersihkan keranjang belanja karena booking sudah sukses dimasukkan ke database
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

// Panggil navbar rapi milikmu
include '../includes/header.php';
?>

<div class="container my-5" style="min-height: 70vh;">
    <h2 class="font-serif fw-bold mb-4" style="color: var(--rb-dark-navy);">
        <i class="fa fa-shopping-cart text-danger me-2"></i>Keranjang Belanja & Jadwal Booking
    </h2>

    <?= $pesan; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Item Pesanan</th>
                                <th>Jenis Pembelian</th>
                                <th>Harga Satuan</th>
                                <th class="text-center">Jumlah</th>
                                <th>Subtotal</th>
                                <th class="text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_seluruhnya = 0;
                            if (!empty($_SESSION['keranjang'])):
                                foreach ($_SESSION['keranjang'] as $cart_key => $qty):
                                    // Proses pemecahan kode session key pintar
                                    if (strpos($cart_key, '_saja') !== false) {
                                        $id_item = (int)str_replace('_saja', '', $cart_key);
                                        $opsi = 'saja';
                                    } elseif (strpos($cart_key, '_pasang') !== false) {
                                        $id_item = (int)str_replace('_pasang', '', $cart_key);
                                        $opsi = 'pasang';
                                    } else {
                                        $id_item = (int)$cart_key;
                                        $opsi = 'normal';
                                    }

                                    // Ambil data produk berdasarkan id yang sudah dipecah
                                    $query_produk = mysqli_query($conn, "SELECT * FROM items WHERE id = $id_item");
                                    $produk = mysqli_fetch_assoc($query_produk);

                                    if ($produk):
                                        // Tentukan harga berdasarkan opsi pilihan pelanggan
                                        $harga_final = ($opsi === 'pasang') ? $produk['harga_pasang'] : $produk['harga'];
                                        $subtotal = $harga_final * $qty;
                                        $total_seluruhnya += $subtotal;
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($produk['nama_item']); ?></td>
                                <td>
                                    <?php if ($opsi === 'saja'): ?>
                                        <span class="badge bg-secondary px-2 py-1"><i class="fa fa-box me-1"></i> Oli Saja (Bawa Pulang)</span>
                                    <?php elseif ($opsi === 'pasang'): ?>
                                        <span class="badge bg-danger px-2 py-1"><i class="fa fa-wrench me-1"></i> Plus Jasa Pasang Bengkel</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border text-uppercase px-2 py-1"><?= $produk['tipe']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?= number_format($harga_final, 0, ',', '.'); ?></td>
                                <td class="text-center fw-bold"><?= $qty; ?></td>
                                <td class="fw-bold text-success">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <a href="keranjang.php?hapus=<?= $cart_key; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus item ini dari keranjang?');">
                                        <i class="fa fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; endforeach; else: ?>
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

        <div class="col-lg-4">
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
                        <h4 class="text-success fw-bold m-0">Rp <?= number_format($total_seluruhnya, 0, ',', '.'); ?></h4>
                    </div>

                    <?php if ($total_seluruhnya > 0): ?>
                        <button type="submit" name="proses_booking" class="btn btn-danger w-100 py-3 fw-bold rounded-3 shadow-sm fs-5 text-uppercase"><i class="fa fa-check-circle me-2"></i> Selesaikan Booking</button>
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