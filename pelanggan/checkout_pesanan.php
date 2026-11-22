<?php
// pelanggan/checkout_pesanan.php (WBS-2.5.1 Form Data Penerima & Alamat + WBS-2.6.2 Ongkir)
session_start();
require '../config/database.php';
require '../config/api.php';
require '../includes/cart_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt_u = mysqli_prepare($conn, "SELECT nama_lengkap, no_telp, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_u, "i", $user_id);
mysqli_stmt_execute($stmt_u);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u));
mysqli_stmt_close($stmt_u);

$ringkasan = getRingkasanKeranjang($conn);
$flash = $_SESSION['flash_checkout'] ?? null;
unset($_SESSION['flash_checkout']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penerima & Checkout - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-fino { background-color: #121F45; border-bottom: 4px solid #FFC906; }
        .card-clean { border: none; border-radius: 16px; }
        .area-dropdown { position: absolute; z-index: 1000; width: 100%; max-height: 220px; overflow-y: auto; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-fino py-3 mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fa fa-motorcycle text-warning me-2"></i>FINO MOTOR</a>
        <div class="ms-auto d-flex gap-2">
            <a href="keranjang_belanja.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Keranjang
            </a>
            <a href="pesanan_saya.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark">Pesanan Saya</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <h3 class="fw-bold mb-4" style="color: #121F45;">
        <i class="fa fa-address-card text-warning me-2"></i>Data Penerima & Pengiriman
    </h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']); ?> rounded-4 shadow-sm fw-bold">
            <?= htmlspecialchars($flash['pesan']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ringkasan['items'])): ?>
        <div class="card card-clean shadow-sm p-5 text-center bg-white">
            <i class="fa fa-shopping-cart fa-3x text-muted opacity-25 mb-3"></i>
            <h5 class="fw-bold">Keranjang belanja kosong</h5>
            <p class="text-muted">Pilih produk terlebih dahulu di halaman katalog sebelum mengisi form penerima.</p>
            <div><a href="../katalog.php" class="btn btn-primary rounded-pill px-4 fw-bold">Belanja Sekarang</a></div>
        </div>
    <?php else: ?>
        <form method="POST" action="proses_checkout.php" id="formCheckout">
            <div class="row g-4">
                <!-- KIRI: FORM DATA PENERIMA & ALAMAT (WBS-2.5.1) -->
                <div class="col-lg-7">
                    <div class="card card-clean shadow-sm p-4 bg-white mb-4">
                        <h5 class="fw-bold mb-3">1. Informasi Penerima & Alamat Pengiriman</h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Lengkap Penerima <span class="text-danger">*</span></label>
                                <input type="text" name="nama_penerima" class="form-control"
                                       value="<?= htmlspecialchars($user['nama_lengkap'] ?? ''); ?>"
                                       placeholder="Masukkan nama penerima" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Kontak (WhatsApp / HP) <span class="text-danger">*</span></label>
                                <input type="tel" name="no_telp_penerima" class="form-control"
                                       value="<?= htmlspecialchars($user['no_telp'] ?? ''); ?>"
                                       placeholder="Contoh: 081234567890" required>
                            </div>
                        </div>

                        <!-- Pencarian Kecamatan / Kota Tujuan -->
                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-bold">Kecamatan / Kota / Kode Pos Tujuan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="inputCariArea" class="form-control"
                                       placeholder="Ketik kecamatan/kota (misal: Bangkalan, Surabaya, Kamal)..." autocomplete="off">
                                <button type="button" class="btn btn-outline-primary fw-bold" id="btnCariArea">
                                    <i class="fa fa-search me-1"></i> Cari Lokasi
                                </button>
                            </div>
                            <div id="hasilArea" class="list-group shadow area-dropdown mt-1" style="display:none;"></div>
                            <small class="text-muted" id="labelAreaTerpilih">Ketik minimal 3 huruf lalu klik "Cari Lokasi".</small>
                            <input type="hidden" name="tujuan_area_id" id="tujuanAreaId" required>
                            <input type="hidden" id="tujuanPostalCode">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alamat Lengkap Pengiriman <span class="text-danger">*</span></label>
                            <textarea name="alamat_pengiriman" class="form-control" rows="3"
                                      placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/desa, dan patokan lokasi" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">
                                Layanan Pengiriman (Total Berat: <?= number_format($ringkasan['total_berat_gram'], 0, ',', '.'); ?> gram) <span class="text-danger">*</span>
                            </label>
                            <div id="loadingOngkir" class="text-primary small fw-bold mb-2" style="display:none;">
                                <i class="fa fa-spinner fa-spin me-1"></i> Mengambil tarif ongkos kirim...
                            </div>
                            <select name="kurir_kode" id="selectKurir" class="form-select" required disabled>
                                <option value="">-- Pilih kecamatan/kota tujuan terlebih dahulu --</option>
                            </select>
                            <div id="errorOngkir" class="text-danger small mt-1"></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-bold">Catatan untuk Penjual / Kurir (Opsional)</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Contoh: Titip di pos satpam / packing kayu">
                        </div>
                    </div>
                </div>

                <!-- KANAN: RINGKASAN TRANSAKSI (WBS-2.5.2) -->
                <div class="col-lg-5">
                    <div class="card card-clean shadow-sm p-4 bg-white">
                        <h5 class="fw-bold mb-3">2. Ringkasan Pesanan</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($ringkasan['items'] as $itm): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($itm['nama_item']); ?></div>
                                        <small class="text-muted">
                                            <?= $itm['qty']; ?> x Rp <?= number_format($itm['harga_satuan'], 0, ',', '.'); ?>
                                            <?php if ($itm['opsi_item'] === 'plus_pasang'): ?>
                                                <span class="badge bg-danger ms-1">+ Pasang</span>
                                            <?php elseif ($itm['opsi_item'] === 'bawa_pulang'): ?>
                                                <span class="badge bg-secondary ms-1">Bawa Pulang</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="fw-bold">Rp <?= number_format($itm['subtotal'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Subtotal Produk</span>
                            <span class="fw-bold text-dark">Rp <?= number_format($ringkasan['subtotal_keranjang'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Ongkos Kirim</span>
                            <span class="fw-bold text-primary" id="teksOngkir">Rp 0</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Total Pembayaran</span>
                            <span class="fw-bold fs-4 text-success" id="teksTotalBayar">
                                Rp <?= number_format($ringkasan['subtotal_keranjang'], 0, ',', '.'); ?>
                            </span>
                        </div>

                        <button type="submit" id="btnSubmitCheckout" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm" disabled>
                            <i class="fa fa-check-circle me-2"></i> Buat Pesanan Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
const subtotalProduk = <?= (int)$ringkasan['subtotal_keranjang']; ?>;
const inputCari = document.getElementById('inputCariArea');
const btnCari = document.getElementById('btnCariArea');
const hasilArea = document.getElementById('hasilArea');
const labelArea = document.getElementById('labelAreaTerpilih');
const inputAreaId = document.getElementById('tujuanAreaId');
const inputPostal = document.getElementById('tujuanPostalCode');
const selectKurir = document.getElementById('selectKurir');
const loadingOngkir = document.getElementById('loadingOngkir');
const errorOngkir = document.getElementById('errorOngkir');
const teksOngkir = document.getElementById('teksOngkir');
const teksTotalBayar = document.getElementById('teksTotalBayar');
const btnSubmit = document.getElementById('btnSubmitCheckout');

function formatRupiah(num) {
    return 'Rp ' + parseInt(num || 0, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

async function cariArea() {
    const q = inputCari.value.trim();
    if (q.length < 3) {
        alert('Ketik minimal 3 huruf nama kota/kecamatan.');
        return;
    }
    hasilArea.innerHTML = '<div class="list-group-item small text-muted">Mencari lokasi...</div>';
    hasilArea.style.display = 'block';

    try {
        const res = await fetch('../api/ongkir.php?aksi=cari_area&q=' + encodeURIComponent(q));
        const json = await res.json();
        hasilArea.innerHTML = '';

        if (!json.success || !json.areas.length) {
            hasilArea.innerHTML = `<div class="list-group-item small text-danger">${json.message || 'Lokasi tidak ditemukan.'}</div>`;
            return;
        }

        json.areas.forEach(area => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action small';
            btn.textContent = area.name;
            btn.onclick = () => pilihArea(area);
            hasilArea.appendChild(btn);
        });
    } catch (e) {
        hasilArea.innerHTML = '<div class="list-group-item small text-danger">Gagal memuat daftar area.</div>';
    }
}

async function pilihArea(area) {
    inputCari.value = area.name;
    inputAreaId.value = area.id;
    inputPostal.value = area.postal_code || '';
    labelArea.innerHTML = `<i class="fa fa-map-marker-alt text-success me-1"></i>Tujuan: <strong>${area.name}</strong>`;
    hasilArea.style.display = 'none';

    loadingOngkir.style.display = 'block';
    errorOngkir.textContent = '';
    selectKurir.disabled = true;
    btnSubmit.disabled = true;
    selectKurir.innerHTML = '<option value="">Memuat tarif...</option>';

    const formData = new FormData();
    formData.append('aksi', 'hitung_ongkir');
    formData.append('area_id', area.id);
    formData.append('postal_code', area.postal_code || '');

    try {
        const res = await fetch('../api/ongkir.php', { method: 'POST', body: formData });
        const json = await res.json();
        loadingOngkir.style.display = 'none';

        if (!json.success || !json.rates) {
            errorOngkir.textContent = json.message || 'Gagal mengambil tarif pengiriman.';
            selectKurir.innerHTML = '<option value="">Tarif tidak tersedia</option>';
            return;
        }

        selectKurir.innerHTML = '<option value="">-- Pilih Kurir & Layanan --</option>';
        Object.entries(json.rates).forEach(([kode, info]) => {
            const opt = document.createElement('option');
            opt.value = kode;
            opt.dataset.harga = info.harga;
            opt.textContent = `${info.kurir} - ${info.layanan} (${info.durasi}) — ${formatRupiah(info.harga)}`;
            selectKurir.appendChild(opt);
        });
        selectKurir.disabled = false;
    } catch (e) {
        loadingOngkir.style.display = 'none';
        errorOngkir.textContent = 'Terjadi kesalahan jaringan saat menghitung ongkir.';
    }
}

selectKurir.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (!this.value) {
        teksOngkir.textContent = 'Rp 0';
        teksTotalBayar.textContent = formatRupiah(subtotalProduk);
        btnSubmit.disabled = true;
        return;
    }
    const ongkir = parseInt(selected.dataset.harga || 0, 10);
    teksOngkir.textContent = formatRupiah(ongkir);
    teksTotalBayar.textContent = formatRupiah(subtotalProduk + ongkir);
    btnSubmit.disabled = false;
});

btnCari?.addEventListener('click', cariArea);
inputCari?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); cariArea(); }
});
</script>
</body>
</html>