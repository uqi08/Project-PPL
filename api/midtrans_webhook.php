<?php
// api/midtrans_webhook.php
header('Content-Type: application/json');

require '../config/database.php';
require '../config/api.php';
require '../includes/payment_helper.php';

$raw_body = file_get_contents('php://input');
$notif = json_decode($raw_body, true);

if (!$notif || empty($notif['order_id']) || empty($notif['signature_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification payload.']);
    exit;
}

// Verifikasi Signature Key resmi Midtrans: SHA512(order_id + status_code + gross_amount + ServerKey)
$expected_signature = hash(
    'sha512',
    $notif['order_id'] . $notif['status_code'] . $notif['gross_amount'] . MIDTRANS_SERVER_KEY
);

if (!hash_equals($expected_signature, $notif['signature_key'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature key.']);
    exit;
}

$hasil = terapkanStatusPembayaran(
    $conn,
    $notif['order_id'],
    $notif['transaction_status'] ?? 'pending',
    $notif['fraud_status'] ?? 'accept',
    $notif['payment_type'] ?? null
);

http_response_code($hasil['success'] ? 200 : 500);
echo json_encode($hasil);
?>