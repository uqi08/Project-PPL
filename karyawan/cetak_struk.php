<?php
// karyawan/cetak_struk.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'karyawan' && $_SESSION['role'] !== 'owner')) {
    header("Location: ../auth/login.php");
    exit;
}

$id_transaksi = (int)$_GET['id'];

// Ambil data utama transaksi
$query_transaksi = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.no_telp 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = $id_transaksi
");
$transaksi = mysqli_fetch_assoc($query_transaksi);

if (!$transaksi) {
    echo "Data transaksi tidak ditemukan.";
    exit;
}

// Ambil rincian produk/jasa didalamnya
$query_detail = mysqli_query($conn, "
    SELECT td.*, i.nama_item 
    FROM transaction_details td 
    JOIN items i ON td.item_id = i.id 
    WHERE td.transaction_id = $id_transaksi
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Transaksi #TRX-<?= $id_transaksi; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; width: 300px; margin: 10px auto; padding: 10px; background-color: #fff; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 3px 0; vertical-align: top; }
        .item-name { font-weight: bold; }
        .total-section td { font-weight: bold; font-size: 13px; }
        
        /* Sembunyikan tombol saat dicetak di kertas */
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="text-center">
        <h3 style="margin: 0; text-transform: uppercase;">FINO MOTOR</h3>
        <p style="margin: 2px 0;">Jasa Cuci, Oli & Ban Kendaraan</p>
        <p style="margin: 2px 0; font-size: 10px;">Bangkalan, Jawa Timur</p>
    </div>

    <div class="line"></div>

    <table>
        <tr><td>Nota : #TRX-<?= $transaksi['id']; ?></td></tr>
        <tr><td>Tgl  : <?= date('d-M-Y H:i', strtotime($transaksi['created_at'])); ?></td></tr>
        <tr><td>Staf : <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></td></tr>
        <tr><td>Pel. : <?= htmlspecialchars($transaksi['nama_lengkap']); ?> (<?= $transaksi['no_telp'] ?: '-'; ?>)</td></tr>
    </table>

    <div class="line"></div>

    <table>
        <?php while($row = mysqli_fetch_assoc($query_detail)): ?>
        <tr>
            <td colspan="2" class="item-name"><?= $row['nama_item']; ?></td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;<?= $row['qty']; ?> x Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
            <td class="text-right">Rp <?= number_format($row['subtotal'], 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="line"></div>

    <table class="total-section">
        <tr>
            <td>TOTAL BAYAR:</td>
            <td class="text-right">Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></td>
        </tr>
    </table>

    <div class="line"></div>
    <div class="text-center" style="margin-top: 15px;">
        <p style="margin: 0;">-- TERIMA KASIH --</p>
        <p style="margin: 5px 0 0 0; font-size: 10px;">Periksa kembali kendaraan Anda<br>sebelum meninggalkan bengkel.</p>
    </div>

    <div class="text-center no-print" style="margin-top: 30px;">
        <a href="kasir.php" style="padding: 10px 15px; background: #121F45; color: white; text-decoration: none; border-radius: 5px; font-family: sans-serif;">Kembali ke Kasir</a>
    </div>

    <script>
        window.print();
        // Otomatis mengembalikan layar ke menu kasir setelah nge-print
        window.onafterprint = function() {
            window.location.href = 'kasir.php';
        };
    </script>

</body>
</html>