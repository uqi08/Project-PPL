<?php
// pelanggan/riwayat.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua daftar transaksi milik pembeli ini dari database
$query_riwayat = mysqli_query($conn, "
    SELECT * FROM transactions 
    WHERE user_id = $user_id 
    ORDER BY id DESC
");

// Muat navbar elegan milikmu
include '../includes/header.php';
?>

<div class="container my-5" style="min-height: 65vh; padding-top: 20px;">
    <h2 class="font-serif fw-bold mb-4" style="color: var(--rb-dark-navy);">
        <i class="fa fa-history text-warning me-2"></i>Riwayat Booking Anda
    </h2>

    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-muted">
                    <tr>
                        <th>ID Booking</th>
                        <th>Jadwal Kedatangan</th>
                        <th>Rincian Layanan / Produk</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                        <?php while ($trx = mysqli_fetch_assoc($query_riwayat)): 
                            $id_trx = $trx['id'];
                            // Ambil rincian detail barang dalam pesanan ini
                            $query_detail = mysqli_query($conn, "
                                SELECT td.*, i.nama_item 
                                FROM transaction_details td 
                                JOIN items i ON td.item_id = i.id 
                                WHERE td.transaction_id = $id_trx
                            ");
                        ?>
                        <tr>
                            <td><span class="fw-bold text-secondary">#TRX-<?= $trx['id']; ?></span></td>
                            <td>
                                <span class="d-block fw-bold text-dark"><i class="fa fa-calendar-alt text-muted me-2"></i> <?= date('d M Y', strtotime($trx['tanggal_booking'])); ?></span>
                                <small class="text-muted"><i class="fa fa-clock me-2"></i> <?= date('H:i', strtotime($trx['waktu_booking'])); ?> WIB</small>
                            </td>
                            <td>
                                <ul class="list-unstyled mb-0 small text-secondary">
                                    <?php while($item = mysqli_fetch_assoc($query_detail)): ?>
                                        <li>• <?= htmlspecialchars($item['nama_item']); ?> <span class="fw-bold">(x<?= $item['qty']; ?>)</span></li>
                                    <?php endwhile; ?>
                                </ul>
                            </td>
                            <td class="fw-bold text-dark">Rp <?= number_format($trx['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($trx['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-clock me-1"></i> PENDING (ANTRE)</span>
                                <?php else: ?>
                                    <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-check-circle me-1"></i> SELESAI & LUNAS</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fa fa-calendar-times fa-3x mb-3 opacity-50"></i>
                                <h5>Belum Ada Riwayat Booking</h5>
                                <p class="small">Yuk, lakukan pesanan servis atau produk pertamamu di katalog!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Muat footer
include '../includes/footer.php'; 
?>