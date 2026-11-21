<?php
// pelanggan/checkout.php
session_start();
require '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../auth/login.php");
    exit;
}

// Tendang balik jika keranjang kosong
if (empty($_SESSION['keranjang'])) {
    echo "<script>alert('Keranjang Anda kosong!'); window.location.href='../katalog.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$total_belanja = 0;

// Logika Proses Booking & Simpan ke Database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_booking = $_POST['tanggal_booking'];
    $waktu_booking   = $_POST['waktu_booking'];
    
    // 1. Hitung ulang total harga demi keamanan
    foreach ($_SESSION['keranjang'] as $id_barang => $jumlah) {
        $query_harga = mysqli_query($conn, "SELECT harga FROM items WHERE id = $id_barang");
        $item_harga = mysqli_fetch_assoc($query_harga);
        $total_belanja += $item_harga['harga'] * $jumlah;
    }

    // 2. Simpan Data Utama ke tabel `transactions` (Status: pending)
    $query_transaksi = "INSERT INTO transactions (user_id, total_harga, status, tanggal_booking, waktu_booking) 
                        VALUES ($user_id, $total_belanja, 'pending', '$tanggal_booking', '$waktu_booking')";
    
    if (mysqli_query($conn, $query_transaksi)) {
        // Dapatkan ID transaksi yang baru saja dibuat
        $id_transaksi_baru = mysqli_insert_id($conn);

        // 3. Simpan Detail Barang ke `transaction_details`
        foreach ($_SESSION['keranjang'] as $id_barang => $jumlah) {
            $query_item = mysqli_query($conn, "SELECT harga FROM items WHERE id = $id_barang");
            $item = mysqli_fetch_assoc($query_item);
            
            $harga_satuan = $item['harga'];
            $subtotal_item = $harga_satuan * $jumlah;
            
            mysqli_query($conn, "INSERT INTO transaction_details (transaction_id, item_id, qty, harga, subtotal) 
                                 VALUES ($id_transaksi_baru, $id_barang, $jumlah, $harga_satuan, $subtotal_item)");
        }

        // 4. Kosongkan keranjang setelah berhasil checkout
        unset($_SESSION['keranjang']);

        // 5. Lempar ke notifikasi sukses dan kembali ke Beranda
        echo "<script>
                alert('Booking Berhasil! Silakan datang ke Fino Motor pada jadwal yang ditentukan.');
                window.location.href='../index.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal membuat pesanan: " . mysqli_error($conn) . "');</script>";
    }
}

include '../includes/header.php';
?>

<div class="container my-5" style="min-height: 60vh;">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-white text-center py-4 rounded-top-4" style="background-color: var(--rb-dark-navy);">
                    <h3 class="font-serif fw-bold mb-0"><i class="fa fa-calendar-check me-2 text-warning"></i>Konfirmasi Booking</h3>
                </div>
                
                <div class="card-body p-5">
                    <div class="alert alert-info rounded-3 mb-4 shadow-sm border-0 bg-light border-start border-4 border-info">
                        <i class="fa fa-info-circle me-2 text-info fs-5"></i> 
                        Hai <b><?= $_SESSION['nama_lengkap']; ?></b>, tentukan jadwal kedatangan Anda ke bengkel kami. Pembayaran dilakukan secara langsung (Offline) di kasir.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted">Pilih Tanggal Kedatangan</label>
                                <!-- Membatasi tanggal minimal adalah hari ini -->
                                <input type="date" name="tanggal_booking" class="form-control py-2" min="<?= date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted">Pilih Jam</label>
                                <input type="time" name="waktu_booking" class="form-control py-2" required>
                                <small class="text-muted d-block mt-1">Jam operasional: 08:00 - 17:00</small>
                            </div>
                        </div>

                        <hr class="border-secondary mb-4 opacity-25">
                        
                        <h5 class="fw-bold text-dark mb-3">Rincian Item:</h5>
                        <ul class="list-group mb-4 shadow-sm rounded-3">
                            <?php 
                            $grand_total = 0;
                            foreach ($_SESSION['keranjang'] as $id_barang => $jumlah): 
                                $query_view = mysqli_query($conn, "SELECT nama_item, harga FROM items WHERE id = $id_barang");
                                $view = mysqli_fetch_assoc($query_view);
                                $sub = $view['harga'] * $jumlah;
                                $grand_total += $sub;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                                <div>
                                    <span class="fw-bold d-block text-dark"><?= $view['nama_item']; ?></span>
                                    <small class="text-muted"><?= $jumlah; ?> x Rp <?= number_format($view['harga'], 0, ',', '.'); ?></small>
                                </div>
                                <span class="fw-bold" style="color: var(--rb-dark-navy);">Rp <?= number_format($sub, 0, ',', '.'); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 border rounded-3 mb-4 shadow-sm">
                            <span class="fs-5 fw-bold text-muted">Total Tagihan:</span>
                            <span class="fs-4 fw-bold" style="color: var(--rb-red);">Rp <?= number_format($grand_total, 0, ',', '.'); ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100 py-3 rounded-pill fw-bold fs-5 shadow-sm">
                            <i class="fa fa-check-circle me-2"></i> Konfirmasi & Selesaikan Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>