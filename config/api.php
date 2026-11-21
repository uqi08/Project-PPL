
<?php

// ============================================================
// KONFIGURASI API FINO MOTOR
// Mengambil API Key dari file lokal
// ============================================================

$localConfigPath = __DIR__ . '/api.local.php';

if (!file_exists($localConfigPath)) {
    throw new RuntimeException(
        'File config/api.local.php tidak ditemukan. Silakan buat file konfigurasi API lokal.'
    );
}

$apiConfig = require $localConfigPath;

// Validasi konfigurasi API
$requiredKeys = [
    'BITESHIP_API_KEY',
    'MIDTRANS_SERVER_KEY',
    'MIDTRANS_CLIENT_KEY'
];

foreach ($requiredKeys as $key) {
    if (empty($apiConfig[$key])) {
        throw new RuntimeException(
            "Konfigurasi API belum lengkap: " . $key
        );
    }
}

// ============================================================
// 1. BITESHIP API - SANDBOX
// ============================================================

if (!defined('BITESHIP_API_KEY')) {
    define('BITESHIP_API_KEY', $apiConfig['BITESHIP_API_KEY']);
}

define('BITESHIP_BASE_URL', 'https://api.biteship.com/v1');

define('ORIGIN_POSTAL_CODE', 69112);

define('ACTIVE_COURIERS', 'jne,jnt,sicepat');

// ============================================================
// 2. MIDTRANS SNAP API - SANDBOX
// ============================================================

if (!defined('MIDTRANS_SERVER_KEY')) {
    define('MIDTRANS_SERVER_KEY', $apiConfig['MIDTRANS_SERVER_KEY']);
}

if (!defined('MIDTRANS_CLIENT_KEY')) {
    define('MIDTRANS_CLIENT_KEY', $apiConfig['MIDTRANS_CLIENT_KEY']);
}

define(
    'MIDTRANS_SNAP_URL',
    'https://app.sandbox.midtrans.com/snap/v1/transactions'
);

define(
    'MIDTRANS_SNAP_JS',
    'https://app.sandbox.midtrans.com/snap/snap.js'
);

define(
    'MIDTRANS_STATUS_URL',
    'https://api.sandbox.midtrans.com/v2'
);

?>
