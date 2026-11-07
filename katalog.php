<?php
// katalog.php
session_start();
require 'config/database.php';

// --- LOGIKA PROSES TAMBAH KE KERANJANG ---
if (isset($_POST['tambah_keranjang'])) {
    // Pengaman: Harus login sebagai pelanggan terlebih dahulu
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
        echo "<script>alert('Silakan login sebagai akun pelanggan untuk melakukan pemesanan.'); window.location.href='auth/login.php';</script>";
        exit;
    }

    $id_item = (int)$_POST['id_item'];
    $qty = (int)$_POST['qty'];
    $opsi = $_POST['opsi'] ?? 'normal'; // Opsi bisa: 'saja', 'pasang', atau 'normal'

    // Membuat KEY unik untuk keranjang agar tidak bercampur antara oli saja & plus pasang
    $cart_key = $id_item;
    if ($opsi === 'saja') {
        $cart_key = $id_item . "_saja";
    } elseif ($opsi === 'pasang') {
        $cart_key = $id_item . "_pasang";
    }

    // Masukkan ke dalam session keranjang belanja
    if (isset($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key] += $qty;
    } else {
        $_SESSION['keranjang'][$cart_key] = $qty;
    }

    echo "<script>alert('Berhasil dimasukkan ke keranjang belanja!'); window.location.href='katalog.php';</script>";
    exit;
}

// --- LOGIKA FILTER PENCARIAN ---
$search = $_GET['search'] ?? '';
$where_clause = "";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where_clause = "WHERE nama_item LIKE '%$search_safe%'";
}

// Ambil data produk & layanan dari database
$query_items = mysqli_query($conn, "SELECT * FROM items $where_clause ORDER BY tipe ASC, nama_item ASC");

// Memanggil header/navbar
include 'includes/header.php';
?>

<!-- HERO SECTION -->
<div class="py-12 text-white text-center relative mb-12 bg-gradient-to-br from-[#121F45] to-[#223971] border-b-4 border-[#FFC906]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-4xl md:text-5xl font-black font-serif-nav uppercase mb-4 tracking-widest text-white drop-shadow-md">Katalog Produk & Layanan</h1>
        <p class="text-lg md:text-xl text-gray-200 mb-8 max-w-2xl mx-auto">Temukan suku cadang berkualitas, oli original, hingga layanan cuci motor terbaik dari Fino Motor.</p>
        
        <!-- SEARCH BAR -->
        <div class="max-w-2xl mx-auto">
            <form method="GET" action="" class="flex shadow-lg rounded-full overflow-hidden">
                <input type="text" name="search" class="w-full px-6 py-4 text-gray-700 outline-none text-lg" placeholder="Cari oli, ban, atau nama jasa..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="bg-[#FFC906] hover:bg-yellow-500 text-[#121F45] px-8 font-bold text-lg transition-colors flex items-center">
                    <i class="fa fa-search mr-2"></i> Cari
                </button>
            </form>
        </div>
    </div>
</div>

<!-- KATALOG GRID -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        
        <?php if (mysqli_num_rows($query_items) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_items)): 
                // Deteksi Jalur Gambar
                $img_src = '';
                if (!empty($row['gambar'])) {
                    if (filter_var($row['gambar'], FILTER_VALIDATE_URL)) {
                        $img_src = $row['gambar'];
                    } else {
                        $img_src = "/Fino_Motor/assets/uploads/" . $row['gambar'];
                    }
                } else {
                    $img_src = "https://via.placeholder.com/400x300?text=Fino+Motor";
                }
            ?>
            
            <!-- CARD ITEM -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative flex flex-col hover:-translate-y-2 hover:shadow-xl transition-all duration-300 group">
                
                <!-- Badge Tipe -->
                <span class="absolute top-3 left-3 px-3 py-1 text-xs font-bold uppercase text-white rounded-md shadow-sm z-10 <?= $row['tipe'] === 'barang' ? 'bg-blue-600' : 'bg-green-600' ?>">
                    <?= $row['tipe']; ?>
                </span>

                <!-- Gambar -->
                <div class="h-52 w-full bg-gray-100 overflow-hidden">
                    <img src="<?= $img_src; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Gambar Produk">
                </div>

                <!-- Konten Card -->
                <div class="p-5 flex flex-col flex-grow justify-between">
                    <div>
                        <h5 class="text-lg font-bold text-gray-900 mb-2 leading-tight"><?= htmlspecialchars($row['nama_item']); ?></h5>
                        
                        <?php if ($row['is_oli'] == 1): ?>
                            <div class="mt-2 space-y-1">
                                <span class="text-gray-500 text-sm block">Oli Saja: <b class="text-green-600">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></b></span>
                                <span class="text-gray-500 text-sm block">Plus Pasang: <b class="text-red-600">Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></b></span>
                            </div>
                        <?php else: ?>
                            <h4 class="text-green-600 font-bold text-xl mt-2">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h4>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6">
                        <?php if ($row['is_oli'] == 1): ?>
                            <!-- Tombol Buka Modal Oli -->
                            <button type="button" onclick="openModal(<?= $row['id']; ?>)" class="w-full border-2 border-gray-800 text-gray-800 hover:bg-gray-800 hover:text-white font-bold py-2.5 rounded-lg transition-colors flex justify-center items-center">
                                <i class="fa fa-sliders mr-2"></i> Pilih Opsi Oli
                            </button>
                        <?php else: ?>
                            <!-- Form Langsung Pesan -->
                            <form method="POST" action="">
                                <input type="hidden" name="id_item" value="<?= $row['id']; ?>">
                                <input type="hidden" name="qty" value="1">
                                <input type="hidden" name="opsi" value="normal">
                                <button type="submit" name="tambah_keranjang" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-2.5 rounded-lg transition-colors flex justify-center items-center shadow-md">
                                    <i class="fa fa-shopping-basket mr-2"></i> Pesan Sekarang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($row['is_oli'] == 1): ?>
            <!-- MODAL TAILWIND (Sembunyi by default via class 'hidden') -->
            <div id="modalOli<?= $row['id']; ?>" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black bg-opacity-50 transition-opacity">
                <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-[scale-in_0.2s_ease-out]">
                    <!-- Header Modal -->
                    <div class="bg-[#121F45] px-6 py-4 flex justify-between items-center">
                        <h5 class="text-white font-serif-nav font-bold text-lg flex items-center">
                            <i class="fa fa-oil-can text-[#FFC906] mr-3"></i> Opsi Pemasangan Oli
                        </h5>
                        <button type="button" onclick="closeModal(<?= $row['id']; ?>)" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Body Modal -->
                    <form method="POST" action="">
                        <input type="hidden" name="id_item" value="<?= $row['id']; ?>">
                        <div class="p-6">
                            <div class="text-center mb-6">
                                <img src="<?= $img_src; ?>" class="rounded-xl shadow-sm mx-auto mb-3 h-28 w-28 object-cover">
                                <h4 class="font-bold text-gray-900 text-xl mb-1"><?= htmlspecialchars($row['nama_item']); ?></h4>
                                <p class="text-gray-500 text-sm">Silakan pilih jenis pembelian di bawah ini</p>
                            </div>

                            <div class="space-y-3 mb-5">
                                <label class="text-sm font-bold text-gray-500 uppercase tracking-wider">Jenis Pembelian</label>
                                
                                <!-- Opsi Saja -->
                                <label class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 transition-colors bg-gray-50">
                                    <div class="flex items-center">
                                        <input type="radio" name="opsi" value="saja" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                        <span class="ml-3 font-bold text-gray-800">Bawa Pulang (Oli Saja)</span>
                                    </div>
                                    <span class="text-green-600 font-bold">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></span>
                                </label>

                                <!-- Opsi Pasang -->
                                <label class="flex items-center justify-between p-4 border-2 border-red-200 rounded-xl cursor-pointer hover:border-red-500 transition-colors bg-red-50">
                                    <div class="flex items-center">
                                        <input type="radio" name="opsi" value="pasang" class="w-4 h-4 text-red-600 focus:ring-red-500 cursor-pointer">
                                        <span class="ml-3 font-bold text-gray-800">Ganti di Bengkel (+ Jasa)</span>
                                    </div>
                                    <span class="text-red-600 font-bold">Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></span>
                                </label>
                            </div>

                            <div>
                                <label class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2 block">Jumlah (Qty)</label>
                                <input type="number" name="qty" class="w-full border-2 border-gray-200 rounded-lg text-center font-bold text-xl py-2 focus:border-blue-500 focus:ring-0 outline-none" value="1" min="1" required>
                            </div>
                        </div>

                        <!-- Footer Modal -->
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                            <button type="button" onclick="closeModal(<?= $row['id']; ?>)" class="px-5 py-2.5 font-bold text-gray-600 hover:bg-gray-200 rounded-lg transition-colors">Batal</button>
                            <button type="submit" name="tambah_keranjang" class="px-5 py-2.5 font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-md transition-colors flex items-center">
                                <i class="fa fa-cart-plus mr-2"></i> Masukkan Keranjang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-20 text-gray-400">
                <i class="fa fa-search-minus fa-4x mb-4 opacity-50"></i>
                <h5 class="text-xl font-medium">Produk atau layanan tidak ditemukan.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPT UNTUK MODAL -->
<script>
    function openModal(id) {
        const modal = document.getElementById('modalOli' + id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById('modalOli' + id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<!-- Tambahan sedikit CSS untuk animasi pop-up (Opsional ditaruh di sini) -->
<style>
    @keyframes scale-in {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<?php 
// Memanggil footer
include 'includes/footer.php'; 
?>