<?php
// pelanggan/proses_checkout.php
session_start();
require '../config/database.php';
require '../includes/cart_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

$user_id           = (int)$_SESSION['user_id'];
$nama_penerima     = trim($_POST['nama_penerima'] ?? '');
$no_telp_penerima  = trim($_POST['no_telp_penerima'] ?? '');
$alamat_pengiriman = trim($_POST['alamat_pengiriman'] ?? '');
$tujuan_area_id    = trim($_POST['tujuan_area_id'] ?? '');
$kurir_kode        = trim($_POST['kurir_kode'] ?? '');
$catatan           = trim($_POST['catatan'] ?? '');

$errors = [];
if ($nama_penerima === '')     $errors[] = 'Nama penerima wajib diisi.';
if ($no_telp_penerima === '')  $errors[] = 'Nomor kontak wajib diisi.';
if ($alamat_pengiriman === '') $errors[] = 'Alamat pengiriman wajib diisi.';

// Validasi tarif ongkir resmi yang sebelumnya diambil dari API Biteship
$ongkir_options = $_SESSION['ongkir_options'] ?? [];
if ($kurir_kode === '' || !isset($ongkir_options[$kurir_kode])) {
    $errors[] = 'Silakan pilih tujuan dan layanan pengiriman terlebih dahulu.';
}

$ringkasan = getRingkasanKeranjang($conn);
if (empty($ringkasan['items'])) {
    $errors[] = 'Keranjang belanja kosong.';
}

if (!empty($errors)) {
    $_SESSION['flash_checkout'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
    header("Location: checkout.php");
    exit;
}

$tarif_terpilih  = $ongkir_options[$kurir_kode];
$ongkir          = (int)$tarif_terpilih['harga'];
$kurir_nama      = $tarif_terpilih['kurir'];
$layanan_kurir   = $tarif_terpilih['layanan'] . ' (' . $tarif_terpilih['durasi'] . ')';
$subtotal_produk = (int)$ringkasan['subtotal_keranjang'];
$total_harga     = $subtotal_produk + $ongkir;

$kode_pesanan     = 'FM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$status_awal      = 'pending';
$payment_status   = 'unpaid';
$tanggal_sekarang = date('Y-m-d');
$waktu_sekarang   = date('H:i:s');

mysqli_begin_transaction($conn);
try {
    // 1. Validasi & kunci stok barang
    $stmt_cek = mysqli_prepare($conn, "SELECT nama_item, tipe, stok FROM items WHERE id = ? FOR UPDATE");
    foreach ($ringkasan['items'] as $itm) {
        if ($itm['tipe'] === 'barang') {
            mysqli_stmt_bind_param($stmt_cek, "i", $itm['id']);
            mysqli_stmt_execute($stmt_cek);
            $db_item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
            if (!$db_item || (int)$db_item['stok'] < $itm['qty']) {
                $sisa = $db_item ? (int)$db_item['stok'] : 0;
                throw new Exception("Stok '{$itm['nama_item']}' tidak cukup (tersisa {$sisa}).");
            }
        }
    }
    mysqli_stmt_close($stmt_cek);

    // 2. Simpan pesanan utama ke tabel transactions
    $stmt_trx = mysqli_prepare($conn, "
        INSERT INTO transactions (
            kode_pesanan, user_id, nama_penerima, no_telp_penerima, alamat_pengiriman,
            tujuan_area_id, subtotal_produk, ongkir, kurir, layanan_kurir,
            total_harga, catatan, status, payment_status, tanggal_booking, waktu_booking
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param(
        $stmt_trx,
        "sissssiississsss",
        $kode_pesanan,
        $user_id,
        $nama_penerima,
        $no_telp_penerima,
        $alamat_pengiriman,
        $tujuan_area_id,
        $subtotal_produk,
        $ongkir,
        $kurir_nama,
        $layanan_kurir,
        $total_harga,
        $catatan,
        $status_awal,
        $payment_status,
        $tanggal_sekarang,
        $waktu_sekarang
    );
    if (!mysqli_stmt_execute($stmt_trx)) {
        throw new Exception("Gagal menyimpan data transaksi.");
    }
    $transaction_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_trx);

    // 3. Simpan rincian item & kurangi stok
    $stmt_det  = mysqli_prepare($conn, "INSERT INTO transaction_details (transaction_id, item_id, opsi_item, qty, harga, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_stok = mysqli_prepare($conn, "UPDATE items SET stok = stok - ? WHERE id = ?");

    foreach ($ringkasan['items'] as $itm) {
        mysqli_stmt_bind_param($stmt_det, "iisiii", $transaction_id, $itm['id'], $itm['opsi_item'], $itm['qty'], $itm['harga_satuan'], $itm['subtotal']);
        if (!mysqli_stmt_execute($stmt_det)) {
            throw new Exception("Gagal menyimpan rincian produk.");
        }

        if ($itm['tipe'] === 'barang') {
            mysqli_stmt_bind_param($stmt_stok, "ii", $itm['qty'], $itm['id']);
            if (!mysqli_stmt_execute($stmt_stok)) {
                throw new Exception("Gagal memperbarui stok produk.");
            }
        }
    }
    mysqli_stmt_close($stmt_det);
    mysqli_stmt_close($stmt_stok);

    mysqli_commit($conn);

    unset($_SESSION['keranjang'], $_SESSION['ongkir_options']);

    $_SESSION['flash_pesanan'] = [
        'tipe'  => 'success',
        'pesan' => "Pesanan #{$kode_pesanan} berhasil dibuat! Silakan klik 'Bayar Sekarang' untuk menyelesaikan pembayaran."
    ];
    header("Location: pesanan_saya.php?kode=" . urlencode($kode_pesanan));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['flash_checkout'] = ['tipe' => 'danger', 'pesan' => $e->getMessage()];
    header("Location: checkout.php");
    exit;
}
?>