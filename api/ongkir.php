<?php
// api/ongkir.php
session_start();
header('Content-Type: application/json');

require '../config/database.php';
require '../config/api.php';
require '../includes/cart_helper.php';

function callBiteshipAPI($method, $endpoint, $payload = null) {
    $ch = curl_init(BITESHIP_BASE_URL . $endpoint);
    $headers = [
        'Authorization: ' . BITESHIP_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    // Nonaktifkan verifikasi SSL hanya untuk lokal XAMPP jika sertifikat CA belum terpasang
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['http_code' => 0, 'error' => 'cURL Error: ' . $curl_error, 'data' => null];
    }

    return [
        'http_code' => $http_code,
        'error'     => null,
        'data'      => json_decode($body, true)
    ];
}

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? '';

// 1. TEST KONEKSI API SANDBOX (Bukti WBS-2.6.1)
if ($aksi === 'test') {
    $res = callBiteshipAPI('GET', '/maps/areas?countries=ID&input=Bangkalan&type=single');
    echo json_encode([
        'wbs'       => 'WBS-2.6.1',
        'provider'  => 'Biteship Sandbox',
        'http_code' => $res['http_code'],
        'connected' => ($res['http_code'] === 200 && !empty($res['data']['success'])),
        'sample'    => $res['data']['areas'][0] ?? $res['data'],
        'error'     => $res['error']
    ], JSON_PRETTY_PRINT);
    exit;
}

// 2. CARI AREA / KECAMATAN TUJUAN
if ($aksi === 'cari_area') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 3) {
        echo json_encode(['success' => false, 'areas' => [], 'message' => 'Ketik minimal 3 huruf nama kecamatan/kota.']);
        exit;
    }

    $res = callBiteshipAPI('GET', '/maps/areas?countries=ID&input=' . urlencode($q) . '&type=single');
    if ($res['http_code'] === 200 && !empty($res['data']['success'])) {
        echo json_encode([
            'success' => true,
            'areas'   => $res['data']['areas'] ?? []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'areas'   => [],
            'message' => $res['data']['error'] ?? $res['error'] ?? 'Gagal menghubungi API Biteship. Cek API Key di config/api.php.'
        ]);
    }
    exit;
}

// 3. HITUNG ONGKOS KIRIM REAL-TIME (WBS-2.6.2)
if ($aksi === 'hitung_ongkir') {
    $area_id     = trim($_POST['area_id'] ?? '');
    $postal_code = (int)($_POST['postal_code'] ?? 0);

    if ($area_id === '' && $postal_code <= 0) {
        echo json_encode(['success' => false, 'message' => 'Pilih alamat tujuan terlebih dahulu.']);
        exit;
    }

    $ringkasan = getRingkasanKeranjang($conn);
    if (empty($ringkasan['items'])) {
        echo json_encode(['success' => false, 'message' => 'Keranjang belanja kosong.']);
        exit;
    }

    // Susun daftar item untuk dikirim ke Biteship Rates API
    $biteship_items = [];
    foreach ($ringkasan['items'] as $itm) {
        if ($itm['tipe'] !== 'barang') continue;
        $berat_satuan = ($itm['qty'] > 0 && $itm['berat_total'] > 0)
            ? (int)ceil($itm['berat_total'] / $itm['qty'])
            : 800;

        $biteship_items[] = [
            'name'        => substr($itm['nama_item'], 0, 50),
            'description' => 'Produk Fino Motor',
            'value'       => (int)$itm['harga_satuan'],
            'weight'      => max(100, $berat_satuan),
            'quantity'    => (int)$itm['qty']
        ];
    }

    // Jika keranjang hanya jasa, ongkir = 0
    if (empty($biteship_items)) {
        $_SESSION['ongkir_options'] = [
            'AMBIL_TOKO' => [
                'kurir'   => 'AMBIL_TOKO',
                'layanan' => 'Layanan di Bengkel Fino Motor',
                'durasi'  => 'Sesuai jadwal',
                'harga'   => 0
            ]
        ];
        echo json_encode(['success' => true, 'rates' => $_SESSION['ongkir_options']]);
        exit;
    }

    $payload = [
        'origin_postal_code' => ORIGIN_POSTAL_CODE,
        'couriers'           => ACTIVE_COURIERS,
        'items'              => $biteship_items
    ];
    if ($area_id !== '') {
        $payload['destination_area_id'] = $area_id;
    } else {
        $payload['destination_postal_code'] = $postal_code;
    }

    $res = callBiteshipAPI('POST', '/rates/couriers', $payload);

    if ($res['http_code'] === 200 && !empty($res['data']['success']) && !empty($res['data']['pricing'])) {
        $options = [];
        foreach ($res['data']['pricing'] as $p) {
            $kode_unik = strtoupper($p['courier_code'] . '_' . $p['courier_service_code']);
            $options[$kode_unik] = [
                'kurir'   => strtoupper($p['courier_name']),
                'layanan' => $p['courier_service_name'],
                'durasi'  => $p['duration'] ?? '-',
                'harga'   => (int)$p['price']
            ];
        }
        // Tambahkan opsi ambil di bengkel (Rp 0)
        $options['AMBIL_TOKO'] = [
            'kurir'   => 'AMBIL_TOKO',
            'layanan' => 'Ambil Langsung di Bengkel',
            'durasi'  => 'Hari yang sama',
            'harga'   => 0
        ];

        // Simpan ke session untuk validasi server-side saat checkout
        $_SESSION['ongkir_options'] = $options;

        echo json_encode([
            'success' => true,
            'rates'   => $options
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $res['data']['error'] ?? $res['error'] ?? 'Tarif pengiriman tidak tersedia untuk area tersebut.'
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Parameter aksi tidak valid.']);
?>