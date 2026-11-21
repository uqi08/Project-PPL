<?php
// api/cek_status_pembayaran.php
session_start();
header('Content-Type: application/json');

require '../config/database.php';
require '../config/api.php';
require '../includes/payment_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$kode_pesanan = trim($_POST['kode_pesanan'] ?? $_GET['kode_pesanan'] ?? '');
if ($kode_pesanan === '') {
    echo json_encode(['success' => false, 'message' => 'Kode pesanan kosong.']);
    exit;
}

// Ambil status resmi dari Midtrans Status API Sandbox
$url = MIDTRANS_STATUS_URL . '/' . urlencode($kode_pesanan) . '/status';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $curl_err]);
    exit;
}

$data = json_decode($response, true);

if ($http_code === 200 && !empty($data['transaction_status'])) {
    $hasil = terapkanStatusPembayaran(
        $conn,
        $kode_pesanan,
        $data['transaction_status'],
        $data['fraud_status'] ?? 'accept',
        $data['payment_type'] ?? null
    );
    echo json_encode($hasil);
} else {
    echo json_encode([
        'success' => false,
        'message' => $data['status_message'] ?? 'Transaksi belum dipilih metode pembayarannya di Midtrans.'
    ]);
}
?>