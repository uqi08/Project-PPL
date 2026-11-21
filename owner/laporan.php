<?php
// owner/laporan.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

// 1. FILTER TANGGAL: Default ke hari ini jika Owner belum memilih tanggal
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// ==========================================
// A. MENGHITUNG PEMASUKAN BERDASARKAN TIPE (BARANG VS JASA)
// ==========================================
// Kita melakukan JOIN antara transaksi, detail, dan items agar tahu mana yang jasa dan barang
$query_pemasukan = mysqli_query($conn, "
    SELECT 
        i.tipe, 
        SUM(d.jumlah * d.harga_satuan) as total_pendapatan 
    FROM transaction_details d
    JOIN transactions t ON d.transaction_id = t.id
    JOIN items i ON d.item_id = i.id
    WHERE DATE(t.created_at) = '$tanggal_filter' AND t.status_transaksi = 'selesai'
    GROUP BY i.tipe
");

$pemasukan_jasa = 0;
$pemasukan_barang = 0;

while ($row = mysqli_fetch_assoc($query_pemasukan)) {
    if ($row['tipe'] == 'jasa') {
        $pemasukan_jasa = $row['total_pendapatan'];
    } elseif ($row['tipe'] == 'barang') {
        $pemasukan_barang = $row['total_pendapatan'];
    }
}
$total_kotor = $pemasukan_jasa + $pemasukan_barang;

// ==========================================
// B. MENGHITUNG TOTAL PENGELUARAN HARI ITU
// ==========================================
$query_pengeluaran = mysqli_query($conn, "
    SELECT SUM(nominal) as total_keluar 
    FROM pengeluaran 
    WHERE tanggal = '$tanggal_filter'
");
$data_pengeluaran = mysqli_fetch_assoc($query_pengeluaran);
$total_pengeluaran = $data_pengeluaran['total_keluar'] ? $data_pengeluaran['total_keluar'] : 0;

// ==========================================
// C. MENGHITUNG LABA BERSIH
// ==========================================
$laba_bersih = $total_kotor - $total_pengeluaran;

// ==========================================
// D. MENGAMBIL RINCIAN DAFTAR PENGELUARAN (Untuk Ditampilkan di Tabel)
// ==========================================
$query_detail_pengeluaran = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap 
    FROM pengeluaran p
    JOIN users u ON p.user_id = u.id
    WHERE p.tanggal = '$tanggal_filter'
    ORDER BY p.id DESC
");
?>

<h2>Laporan Keuangan: <?= date('d F Y', strtotime($tanggal_filter)) ?></h2>

<form method="GET" action="laporan.php">
    Pilih Tanggal: <input type="date" name="tanggal" value="<?= $tanggal_filter ?>">
    <button type="submit">Lihat Laporan</button>
</form>

<hr>

<h3>Ringkasan Hari Ini</h3>
<ul>
    <li>Pemasukan dari Jasa (Cuci/Service): <b>Rp <?= number_format($pemasukan_jasa, 0, ',', '.') ?></b></li>
    <li>Pemasukan dari Sparepart/Oli: <b>Rp <?= number_format($pemasukan_barang, 0, ',', '.') ?></b></li>
    <li><b>Total Omzet Kotor: Rp <?= number_format($total_kotor, 0, ',', '.') ?></b></li>
</ul>
<ul>
    <li style="color:red;">Total Pengeluaran Toko: <b>- Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></b></li>
</ul>
<h3>Laba Bersih Hari Ini: <b style="color:green;">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></b></h3>

<hr>

<h3>Rincian Pengeluaran Hari Ini</h3>
<table border="1">
    <tr>
        <th>Keterangan</th>
        <th>Nominal</th>
        <th>Diinput Oleh</th>
    </tr>
    <?php while($row_pengeluaran = mysqli_fetch_assoc($query_detail_pengeluaran)): ?>
    <tr>
        <td><?= $row_pengeluaran['keterangan'] ?></td>
        <td>Rp <?= number_format($row_pengeluaran['nominal'], 0, ',', '.') ?></td>
        <td><?= $row_pengeluaran['nama_lengkap'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>