<?php
// index.php
session_start();
require 'config/database.php';

// --- FITUR BARU: LOGIKA PROSES TAMBAH KE KERANJANG LANGSUNG DARI BERANDA ---
if (isset($_POST['tambah_keranjang'])) {
    // Simbol || diganti menjadi or agar tidak kena error saat di-copy
    if (!isset($_SESSION['role']) or $_SESSION['role'] !== 'pelanggan') {
        echo "<script>alert('Silakan login sebagai akun pelanggan untuk melakukan pemesanan.'); window.location.href='/Fino_Motor/auth/login.php';</script>";
        exit;
    }

    $id_item = (int)$_POST['id_item'];
    $qty = (int)$_POST['qty'];
    $opsi = $_POST['opsi'] ?? 'normal'; 

    // Membuat KEY unik untuk keranjang belanja (Oli Saja vs Plus Pasang)
    $cart_key = $id_item;
    if ($opsi === 'saja') {
        $cart_key = $id_item . "_saja";
    } elseif ($opsi === 'pasang') {
        $cart_key = $id_item . "_pasang";
    }

    if (isset($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key] += $qty;
    } else {
        $_SESSION['keranjang'][$cart_key] = $qty;
    }

    echo "<script>alert('Berhasil dimasukkan ke keranjang belanja!'); window.location.href='index.php';</script>";
    exit;
}

include 'includes/header.php';

// Ambil data produk & layanan (Dibatasi 4 untuk di beranda sebagai Produk Unggulan)
$query_items = mysqli_query($conn, "SELECT * FROM items ORDER BY tipe ASC, nama_item ASC LIMIT 4");
?>

<!-- HERO SECTION -->
<div class="relative bg-[url('https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center h-[80vh] flex items-center justify-center">
    <!-- Overlay Gelap -->
    <div class="absolute inset-0 bg-[#121F45] bg-opacity-60"></div>
    
    <!-- Kotak Teks Hero -->
    <div class="relative z-10 bg-[#121F45]/80 backdrop-blur-md border border-[#FFC906]/40 p-10 md:p-16 rounded-2xl text-center max-w-3xl mx-4 shadow-2xl">
        <h1 class="text-white font-serif-nav font-bold text-4xl md:text-5xl mb-4 tracking-widest leading-tight">PERAWATAN MOTOR PREMIUM</h1>
        <p class="text-gray-300 text-lg md:text-xl italic font-serif-nav">Solusi tepat dari cuci kilat hingga performa mesin maksimal.</p>
    </div>
</div>

<!-- FITUR KEUNGGULAN SECTION -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Fitur 1 -->
        <div class="bg-gray-50 rounded-2xl p-8 text-center hover:-translate-y-2 hover:bg-white hover:shadow-xl transition-all duration-300">
            <i class="fa fa-medal text-5xl mb-4 text-[#121F45]"></i>
            <h4 class="font-serif-nav font-bold text-xl text-[#121F45] mb-2">Mekanik Handal</h4>
            <p class="text-gray-500 text-sm leading-relaxed">Ditangani langsung oleh mekanik berpengalaman untuk memastikan kendaraan Anda selalu dalam performa terbaik.</p>
        </div>
        <!-- Fitur 2 -->
        <div class="bg-gray-50 rounded-2xl p-8 text-center hover:-translate-y-2 hover:bg-white hover:shadow-xl transition-all duration-300">
            <i class="fa fa-tags text-5xl mb-4 text-red-600"></i>
            <h4 class="font-serif-nav font-bold text-xl text-[#121F45] mb-2">Harga Bersahabat</h4>
            <p class="text-gray-500 text-sm leading-relaxed">Kualitas perawatan premium layaknya dealer resmi, namun dengan harga yang ramah di kantong.</p>
        </div>
        <!-- Fitur 3 -->
        <div class="bg-gray-50 rounded-2xl p-8 text-center hover:-translate-y-2 hover:bg-white hover:shadow-xl transition-all duration-300">
            <i class="fa fa-stopwatch text-5xl mb-4 text-[#FFC906]"></i>
            <h4 class="font-serif-nav font-bold text-xl text-[#121F45] mb-2">Pengerjaan Cepat</h4>
            <p class="text-gray-500 text-sm leading-relaxed">Waktu Anda berharga. Kami menggunakan peralatan modern agar proses service & cuci selesai lebih efisien.</p>
        </div>
    </div>
</div>

<!-- PRODUK UNGGULAN SECTION -->
<div class="bg-gray-50/50 py-16 rounded-[2.5rem] mx-2 md:mx-8 mb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-center text-[#121F45] font-black text-2xl md:text-3xl uppercase tracking-widest mb-10">Produk Unggulan Fino Motor</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php if (mysqli_num_rows($query_items) > 0): ?>
                <?php while($item = mysqli_fetch_assoc($query_items)): 
                    // PARSER GAMBAR
                    $img_src = '';
                    if (!empty($item['gambar'])) {
                        if (filter_var($item['gambar'], FILTER_VALIDATE_URL)) {
                            $img_src = $item['gambar'];
                        } else {
                            $img_src = "/Fino_Motor/assets/uploads/" . $item['gambar'];
                        }
                    }
                ?>
                    <!-- CARD ITEM -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative flex flex-col hover:-translate-y-2 hover:shadow-xl transition-all duration-300 group">
                        
                        <!-- Gambar -->
                        <div class="h-48 w-full bg-gray-100 overflow-hidden flex items-center justify-center">
                            <?php if (!empty($img_src)): ?>
                                <img src="<?= $img_src; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?= htmlspecialchars($item['nama_item']); ?>">
                            <?php else: ?>
                                <i class="fa <?= $item['tipe'] == 'jasa' ? 'fa-tools' : 'fa-oil-can' ?> text-5xl text-gray-300"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Konten Card -->
                        <div class="p-5 flex flex-col flex-grow justify-between text-center">
                            <div>
                                <h5 class="text-lg font-bold text-gray-900 mb-2 leading-tight"><?= htmlspecialchars($item['nama_item']); ?></h5>
                                
                                <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                                    <div class="mt-2 mb-3 bg-gray-50 p-2 rounded-lg border border-gray-100 text-left space-y-1">
                                        <span class="text-gray-500 text-xs block">Oli Saja: <b class="text-green-600">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></b></span>
                                        <span class="text-gray-500 text-xs block">Plus Pasang: <b class="text-red-600">Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?></b></span>
                                    </div>
                                <?php else: ?>
                                    <h4 class="text-red-600 font-bold text-xl mt-2 mb-3">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></h4>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto">
                                <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                                    <button type="button" onclick="openModalBeranda(<?= $item['id']; ?>)" class="w-full border-2 border-gray-800 text-gray-800 hover:bg-gray-800 hover:text-white font-bold py-2 rounded-full transition-colors">
                                        <i class="fa fa-sliders mr-1"></i> Pilih Opsi Oli
                                    </button>
                                <?php else: ?>
                                    <?php if ($item['tipe'] == 'barang'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="id_item" value="<?= $item['id']; ?>">
                                            <input type="hidden" name="qty" value="1">
                                            <input type="hidden" name="opsi" value="normal">
                                            <button type="submit" name="tambah_keranjang" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-full transition-colors shadow-md">
                                                Pesan Sekarang
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="w-full bg-gray-300 text-gray-500 font-bold py-2 rounded-full cursor-not-allowed" disabled>Tersedia di Bengkel</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($item['is_oli']) && $item['is_oli'] == 1): ?>
                    <!-- MODAL TAILWIND (Vanilla JS) -->
                    <div id="modalOliBeranda<?= $item['id']; ?>" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black bg-opacity-50 transition-opacity">
                        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
                            <!-- Header Modal -->
                            <div class="bg-[#121F45] px-6 py-4 flex justify-between items-center">
                                <h5 class="text-white font-serif-nav font-bold text-lg flex items-center">
                                    <i class="fa fa-oil-can text-[#FFC906] mr-3"></i> Opsi Pembelian Oli
                                </h5>
                                <button type="button" onclick="closeModalBeranda(<?= $item['id']; ?>)" class="text-gray-300 hover:text-white transition-colors">
                                    <i class="fa-solid fa-xmark text-xl"></i>
                                </button>
                            </div>
                            
                            <!-- Body Modal -->
                            <form method="POST" action="">
                                <input type="hidden" name="id_item" value="<?= $item['id']; ?>">
                                <div class="p-6">
                                    <div class="text-center mb-6">
                                        <?php if (!empty($img_src)): ?>
                                            <img src="<?= $img_src; ?>" class="rounded-xl shadow-sm mx-auto mb-3 h-28 w-28 object-cover">
                                        <?php endif; ?>
                                        <h4 class="font-bold text-gray-900 text-xl mb-1"><?= htmlspecialchars($item['nama_item']); ?></h4>
                                        <p class="text-gray-500 text-sm">Pilih jenis layanan penggantian di bawah</p>
                                    </div>

                                    <div class="space-y-3 mb-5">
                                        <!-- Opsi Saja -->
                                        <label class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 transition-colors bg-gray-50">
                                            <div class="flex items-center">
                                                <input type="radio" name="opsi" value="saja" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                                <span class="ml-3 font-bold text-gray-800">Bawa Pulang (Oli Saja)</span>
                                            </div>
                                            <span class="text-green-600 font-bold">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></span>
                                        </label>

                                        <!-- Opsi Pasang -->
                                        <label class="flex items-center justify-between p-4 border-2 border-red-200 rounded-xl cursor-pointer hover:border-red-500 transition-colors bg-red-50">
                                            <div class="flex items-center">
                                                <input type="radio" name="opsi" value="pasang" class="w-4 h-4 text-red-600 focus:ring-red-500 cursor-pointer">
                                                <span class="ml-3 font-bold text-gray-800">Ganti di Bengkel (+ Jasa)</span>
                                            </div>
                                            <span class="text-red-600 font-bold">Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?></span>
                                        </label>
                                    </div>

                                    <div>
                                        <label class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2 block">Kuantitas (Qty)</label>
                                        <input type="number" name="qty" class="w-full border-2 border-gray-200 rounded-lg text-center font-bold text-xl py-2 outline-none focus:border-blue-500" value="1" min="1" required>
                                    </div>
                                </div>

                                <!-- Footer Modal -->
                                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                                    <button type="button" onclick="closeModalBeranda(<?= $item['id']; ?>)" class="px-5 py-2.5 font-bold text-gray-600 hover:bg-gray-200 rounded-lg transition-colors">Batal</button>
                                    <button type="submit" name="tambah_keranjang" class="px-5 py-2.5 font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-md transition-colors flex items-center">
                                        <i class="fa fa-cart-plus mr-2"></i> Tambah Keranjang
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-400 py-10">Belum ada produk di database.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CTA BANNER SECTION -->
<div class="bg-[#121F45] text-white py-16 text-center border-t-4 border-red-600 border-b-4 border-[#FFC906]">
    <div class="max-w-3xl mx-auto px-4">
        <h2 class="font-serif-nav font-bold text-3xl md:text-4xl text-[#FFC906] mb-4">JELAJAHI LAYANAN KAMI!</h2>
        <p class="text-lg md:text-xl italic font-serif-nav mb-8 text-gray-300">Ingin melihat berbagai pilihan oli dan daftar harga service lengkap dari Fino Motor?<br>Silakan klik tombol katalog di bawah ini.</p>
        <a href="katalog.php" class="inline-block bg-white text-[#121F45] font-bold text-lg px-8 py-3 rounded-full hover:bg-gray-200 transition-colors shadow-lg">Lihat Katalog Penuh</a>
    </div>
</div>

<!-- GALERI SECTION -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h2 class="text-center text-[#121F45] font-black text-2xl md:text-3xl uppercase tracking-widest mb-10">Galeri Fino Motor</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="overflow-hidden rounded-xl shadow-sm">
            <img src="https://images.unsplash.com/photo-1625047509168-a7026f36de04?q=80&w=600&auto=format&fit=crop" class="w-full h-64 object-cover hover:scale-105 transition-transform duration-500 cursor-pointer" alt="Galeri 1">
        </div>
        <div class="overflow-hidden rounded-xl shadow-sm">
            <img src="https://images.unsplash.com/photo-1599256872237-5dcc0fbe9668?q=80&w=600&auto=format&fit=crop" class="w-full h-64 object-cover hover:scale-105 transition-transform duration-500 cursor-pointer" alt="Galeri 2">
        </div>
        <div class="overflow-hidden rounded-xl shadow-sm">
            <img src="https://images.unsplash.com/photo-1632249622959-1e359ab8f8ec?q=80&w=600&auto=format&fit=crop" class="w-full h-64 object-cover hover:scale-105 transition-transform duration-500 cursor-pointer" alt="Galeri 3">
        </div>
        <div class="overflow-hidden rounded-xl shadow-sm">
            <img src="https://images.unsplash.com/photo-1508821430932-b258597305aa?q=80&w=600&auto=format&fit=crop" class="w-full h-64 object-cover hover:scale-105 transition-transform duration-500 cursor-pointer" alt="Galeri 4">
        </div>
    </div>
</div>

<!-- TESTIMONIAL SECTION -->
<div class="bg-gray-200 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-center text-[#121F45] font-black text-2xl md:text-3xl uppercase tracking-widest mb-12">Apa Kata Pelanggan Kami?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Testi 1 -->
            <div class="bg-[#121F45] text-white rounded-2xl p-8 shadow-lg flex flex-col justify-between h-full">
                <div>
                    <div class="text-[#FFC906] mb-4 text-center"><i class="fa fa-quote-left text-3xl"></i></div>
                    <p class="font-serif-nav text-center text-lg italic mb-6 text-gray-300">"Oli mesin yang disarankan mekaniknya mantap! Tarikan motor jadi enteng banget buat jalan jauh."</p>
                </div>
                <div class="text-center mt-auto">
                    <h6 class="font-bold text-[#FFC906] text-lg">Budi S.</h6>
                    <span class="text-sm text-gray-400">Surabaya</span>
                </div>
            </div>
            <!-- Testi 2 -->
            <div class="bg-[#121F45] text-white rounded-2xl p-8 shadow-lg flex flex-col justify-between h-full">
                <div>
                    <div class="text-[#FFC906] mb-4 text-center"><i class="fa fa-quote-left text-3xl"></i></div>
                    <p class="font-serif-nav text-center text-lg italic mb-6 text-gray-300">"Cuci saljunya bersih sampai ke sela-sela mesin. Nyaman banget sambil nunggu bisa santai."</p>
                </div>
                <div class="text-center mt-auto">
                    <h6 class="font-bold text-[#FFC906] text-lg">Ahmad Dani</h6>
                    <span class="text-sm text-gray-400">Bangkalan</span>
                </div>
            </div>
            <!-- Testi 3 -->
            <div class="bg-[#121F45] text-white rounded-2xl p-8 shadow-lg flex flex-col justify-between h-full">
                <div>
                    <div class="text-[#FFC906] mb-4 text-center"><i class="fa fa-quote-left text-3xl"></i></div>
                    <p class="font-serif-nav text-center text-lg italic mb-6 text-gray-300">"Booking oli lewat web gampang banget. Datang ke lokasi tinggal pasang tanpa harus antre lama."</p>
                </div>
                <div class="text-center mt-auto">
                    <h6 class="font-bold text-[#FFC906] text-lg">Siti N.</h6>
                    <span class="text-sm text-gray-400">Arosbaya</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NEWSLETTER & ABOUT SECTION -->
<div class="max-w-4xl mx-auto px-4 py-16 text-center">
    <div class="mb-12">
        <h4 class="font-serif-nav font-bold text-2xl text-[#121F45] mb-4">Dapatkan Info Promo Terbaru</h4>
        <div class="flex flex-col sm:flex-row shadow-sm rounded-full overflow-hidden max-w-lg mx-auto border border-gray-200">
            <input type="email" class="flex-grow px-6 py-3 outline-none text-gray-700" placeholder="Masukkan email @gmail.com Anda">
            <button class="bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-3 transition-colors" type="button">Subscribe</button>
        </div>
    </div>
    
    <div>
        <h3 class="font-serif-nav font-bold text-3xl text-[#121F45] mb-4">Tentang Fino Motor</h3>
        <p class="text-gray-500 leading-loose text-lg">
            Fino Motor berdiri dengan komitmen memberikan solusi perawatan kendaraan roda dua terbaik di area Bangkalan dan sekitarnya. 
            Kami percaya setiap pengendara berhak mendapatkan suku cadang yang terjamin keasliannya dan pelayanan prima dengan harga yang transparan. 
            Percayakan perawatan motor Anda kepada kami, karena kualitas mesin adalah prioritas utama Fino Motor.
        </p>
    </div>
</div>

<!-- SCRIPT UNTUK MODAL BERANDA -->
<script>
    function openModalBeranda(id) {
        const modal = document.getElementById('modalOliBeranda' + id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModalBeranda(id) {
        const modal = document.getElementById('modalOliBeranda' + id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php 
include 'includes/footer.php'; 
?>