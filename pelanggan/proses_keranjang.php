<?php
// pelanggan/proses_keranjang.php
session_start();
require '../config/database.php';
require '../includes/cart_helper.php';

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$aksi     = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'keranjang_belanja.php';
$is_ajax  = (isset($_POST['ajax']) || isset($_GET['ajax']));

$response = [
    'success' => false,
    'message' => 'Aksi tidak dikenali.'
];

// --- WBS-2.4.1: TAMBAH PRODUK KE KERANJANG ---
if ($aksi === 'tambah') {
    $item_id = (int)($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
    $qty     = max(1, (int)($_POST['qty'] ?? $_GET['qty'] ?? 1));
    $opsi    = $_POST['opsi'] ?? $_GET['opsi'] ?? 'normal'; // 'normal', 'saja', 'pasang'

    $stmt = mysqli_prepare($conn, "SELECT id, nama_item, tipe, is_oli, stok FROM items WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$item) {
        $response['message'] = 'Produk tidak ditemukan di database.';
    } else {
        // Bentuk cart_key unik (membedakan oli bawa pulang vs plus pasang)
        $cart_key = (string)$item_id;
        if ((int)$item['is_oli'] === 1 && in_array($opsi, ['saja', 'pasang'], true)) {
            $cart_key = $item_id . '_' . $opsi;
        }

        $qty_lama = (int)($_SESSION['keranjang'][$cart_key] ?? 0);
        $qty_baru = $qty_lama + $qty;

        if ($item['tipe'] === 'barang' && $qty_baru > (int)$item['stok']) {
            $response['message'] = "Stok '{$item['nama_item']}' hanya tersisa {$item['stok']}.";
        } else {
            $_SESSION['keranjang'][$cart_key] = $qty_baru;
            $response['success'] = true;
            $response['message'] = "'{$item['nama_item']}' berhasil ditambahkan ke keranjang.";
        }
    }
}

// --- WBS-2.4.2 (Bagian 1): UBAH JUMLAH PRODUK ---
elseif ($aksi === 'ubah') {
    $cart_key = trim((string)($_POST['cart_key'] ?? ''));
    $qty_baru = (int)($_POST['qty'] ?? 0);

    if ($cart_key === '' || !isset($_SESSION['keranjang'][$cart_key])) {
        $response['message'] = 'Item tidak ditemukan di dalam keranjang.';
    } elseif ($qty_baru <= 0) {
        // Jika jumlah diubah menjadi 0 atau kurang, otomatis hapus item
        unset($_SESSION['keranjang'][$cart_key]);
        $response['success'] = true;
        $response['message'] = 'Produk dihapus dari keranjang.';
    } else {
        list($item_id, $opsi) = parseCartKey($cart_key);
        $stmt = mysqli_prepare($conn, "SELECT nama_item, tipe, stok FROM items WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$item) {
            unset($_SESSION['keranjang'][$cart_key]);
            $response['message'] = 'Produk sudah tidak tersedia.';
        } elseif ($item['tipe'] === 'barang' && $qty_baru > (int)$item['stok']) {
            $response['message'] = "Maksimal pembelian '{$item['nama_item']}' sesuai stok adalah {$item['stok']}.";
        } else {
            $_SESSION['keranjang'][$cart_key] = $qty_baru;
            $response['success'] = true;
            $response['message'] = 'Jumlah produk berhasil diperbarui.';
        }
    }
}

// --- WBS-2.4.2 (Bagian 2): HAPUS PRODUK DARI KERANJANG ---
elseif ($aksi === 'hapus') {
    $cart_key = trim((string)($_POST['cart_key'] ?? $_GET['cart_key'] ?? ''));

    if ($cart_key !== '' && isset($_SESSION['keranjang'][$cart_key])) {
        unset($_SESSION['keranjang'][$cart_key]);
        $response['success'] = true;
        $response['message'] = 'Produk berhasil dihapus dari keranjang.';
    } else {
        $response['message'] = 'Item yang ingin dihapus tidak ada di keranjang.';
    }
}

// --- OPSIONAL: KOSONGKAN KERANJANG ---
elseif ($aksi === 'kosongkan') {
    $_SESSION['keranjang'] = [];
    $response['success'] = true;
    $response['message'] = 'Keranjang berhasil dikosongkan.';
}

// Keluaran JSON jika dipanggil via Fetch/AJAX
if ($is_ajax) {
    header('Content-Type: application/json');
    $response['ringkasan'] = getRingkasanKeranjang($conn);
    echo json_encode($response);
    exit;
}

// Keluaran Redirect + Flash Message jika dipanggil via Form HTML
$_SESSION['flash_cart'] = [
    'tipe'  => $response['success'] ? 'success' : 'danger',
    'pesan' => $response['message']
];

header("Location: " . $redirect);
exit;
?>