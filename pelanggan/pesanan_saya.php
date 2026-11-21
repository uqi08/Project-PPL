<?php
// pelanggan/pesanan_saya.php
session_start();
require '../config/database.php';
require '../config/api.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$flash = $_SESSION['flash_pesanan'] ?? null;
unset($_SESSION['flash_pesanan']);

$stmt_list = mysqli_prepare($conn, "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC");
mysqli_stmt_bind_param($stmt_list, "i", $user_id);
mysqli_stmt_execute($stmt_list);
$result_pesanan = mysqli_stmt_get_result($stmt_list);

function badgeStatus($status) {
    $map = [
        'pending' => 'bg-warning text-dark',
        'dibayar' => 'bg-info text-dark',
        'dikirim' => 'bg-primary',
        'selesai' => 'bg-success',
        'batal'   => 'bg-danger'
    ];
    $cls = $map[$status] ?? 'bg-secondary';
    return "<span class='badge $cls rounded-pill px-3 py-2 text-uppercase'>" . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan & Pembayaran - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Script resmi Midtrans Snap Sandbox -->
    <script src="<?= MIDTRANS_SNAP_JS; ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY; ?>"></script>
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fa fa-motorcycle text-warning me-2"></i>FINO MOTOR</a>
        <div class="ms-auto d-flex gap-2">
            <a href="../katalog.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Belanja Lagi</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <h3 class="fw-bold mb-4" style="color: #121F45;">
        <i class="fa fa-receipt text-warning me-2"></i>Pesanan & Pembayaran Saya
    </h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']); ?> rounded-4 shadow-sm fw-bold">
            <?= htmlspecialchars($flash['pesan']); ?>
        </div>
    <?php endif; ?>

    <div id="notifDinamis"></div>

    <?php if (mysqli_num_rows($result_pesanan) === 0): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="fa fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
            <h5 class="fw-bold">Belum ada pesanan tercatat</h5>
            <p class="text-muted">Pesanan yang Anda buat akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <?php while ($trx = mysqli_fetch_assoc($result_pesanan)):
            $kode = $trx['kode_pesanan'] ?: ('TRX-' . $trx['id']);
            $stmt_d = mysqli_prepare($conn, "
                SELECT td.*, i.nama_item
                FROM transaction_details td
                JOIN items i ON td.item_id = i.id
                WHERE td.transaction_id = ?
            ");
            mysqli_stmt_bind_param($stmt_d, "i", $trx['id']);
            mysqli_stmt_execute($stmt_d);
            $items_res = mysqli_stmt_get_result($stmt_d);
        ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
            <div class="card-header bg-light py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-bold text-dark me-2">#<?= htmlspecialchars($kode); ?></span>
                    <small class="text-muted"><i class="fa fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($trx['created_at'])); ?></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border small">
                        Payment: <?= strtoupper(htmlspecialchars($trx['payment_status'] ?? 'unpaid')); ?>
                    </span>
                    <?= badgeStatus($trx['status']); ?>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-secondary small text-uppercase mb-2">Rincian Item</h6>
                        <ul class="list-unstyled mb-0">
                            <?php while ($d = mysqli_fetch_assoc($items_res)): ?>
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span><strong><?= $d['qty']; ?>x</strong> <?= htmlspecialchars($d['nama_item']); ?></span>
                                    <span class="fw-bold">Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endwhile; mysqli_stmt_close($stmt_d); ?>
                        </ul>
                    </div>

                    <div class="col-md-5 border-start">
                        <h6 class="fw-bold text-secondary small text-uppercase mb-2">Pengiriman & Pembayaran</h6>
                        <p class="small mb-1"><strong>Penerima:</strong> <?= htmlspecialchars($trx['nama_penerima'] ?: '-'); ?> (<?= htmlspecialchars($trx['no_telp_penerima'] ?: '-'); ?>)</p>
                        <p class="small mb-1"><strong>Alamat:</strong> <?= htmlspecialchars($trx['alamat_pengiriman'] ?: '-'); ?></p>
                        <p class="small mb-1"><strong>Kurir:</strong> <?= htmlspecialchars($trx['kurir'] ?: '-'); ?> <?= !empty($trx['layanan_kurir']) ? '— ' . htmlspecialchars($trx['layanan_kurir']) : ''; ?></p>
                        <?php if (!empty($trx['payment_type'])): ?>
                            <p class="small mb-1"><strong>Metode Bayar:</strong> <span class="badge bg-secondary"><?= strtoupper(htmlspecialchars($trx['payment_type'])); ?></span></p>
                        <?php endif; ?>

                        <div class="bg-light p-3 rounded-3 mt-2">
                            <div class="d-flex justify-content-between small">
                                <span>Subtotal Produk</span>
                                <span>Rp <?= number_format($trx['subtotal_produk'] ?: ($trx['total_harga'] - $trx['ongkir']), 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Ongkos Kirim</span>
                                <span>Rp <?= number_format($trx['ongkir'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold text-success fs-5 mt-1">
                                <span>Total Bayar</span>
                                <span>Rp <?= number_format($trx['total_harga'], 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <?php if ($trx['status'] === 'pending' && !empty($trx['kode_pesanan'])): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-primary btn-sm flex-fill fw-bold"
                                        onclick="bayarSekarang('<?= htmlspecialchars($trx['kode_pesanan']); ?>', this)">
                                    <i class="fa fa-credit-card me-1"></i> Bayar Sekarang (Sandbox)
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm fw-bold"
                                        onclick="cekStatus('<?= htmlspecialchars($trx['kode_pesanan']); ?>', this)">
                                    <i class="fa fa-sync-alt me-1"></i> Cek Status
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<script>
function tampilkanNotif(tipe, pesan) {
    document.getElementById('notifDinamis').innerHTML =
        `<div class="alert alert-${tipe} rounded-4 shadow-sm fw-bold">${pesan}</div>`;
}

// Memanggil WBS-2.7.1 (midtrans_token.php) lalu membuka popup Snap Sandbox
async function bayarSekarang(kodePesanan, btn) {
    const labelAsli = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Memuat...';

    const fd = new FormData();
    fd.append('kode_pesanan', kodePesanan);

    try {
        const res = await fetch('../api/midtrans_token.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (!json.success || !json.snap_token) {
            tampilkanNotif('danger', json.message || 'Gagal membuka pembayaran Midtrans.');
            btn.disabled = false;
            btn.innerHTML = labelAsli;
            return;
        }

        window.snap.pay(json.snap_token, {
            onSuccess: function(result) {
                tampilkanNotif('success', 'Pembayaran berhasil! Sedang memperbarui status pesanan...');
                cekStatus(kodePesanan);
            },
            onPending: function(result) {
                tampilkanNotif('warning', 'Menunggu pembayaran diselesaikan (gunakan Midtrans Simulator Sandbox untuk bayar VA/QRIS).');
                cekStatus(kodePesanan);
            },
            onError: function(result) {
                tampilkanNotif('danger', 'Pembayaran gagal atau dibatalkan.');
                cekStatus(kodePesanan);
            },
            onClose: function() {
                btn.disabled = false;
                btn.innerHTML = labelAsli;
            }
        });
    } catch (e) {
        tampilkanNotif('danger', 'Kesalahan koneksi ke server.');
        btn.disabled = false;
        btn.innerHTML = labelAsli;
    }
}

// Memanggil WBS-2.7.2 (cek_status_pembayaran.php) untuk sinkronisasi status ke database
async function cekStatus(kodePesanan, btn = null) {
    if (btn) btn.disabled = true;
    const fd = new FormData();
    fd.append('kode_pesanan', kodePesanan);

    try {
        const res = await fetch('../api/cek_status_pembayaran.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
            tampilkanNotif('success', `Status pesanan #${kodePesanan} diperbarui: ${json.status.toUpperCase()} (${json.payment_status}). Memuat ulang...`);
            setTimeout(() => location.reload(), 1200);
        } else {
            tampilkanNotif('warning', json.message || 'Status belum berubah.');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        tampilkanNotif('danger', 'Gagal mengecek status pembayaran.');
        if (btn) btn.disabled = false;
    }
}
</script>
</body>
</html>