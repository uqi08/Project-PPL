<?php
// katalog.php
session_start();
require 'config/database.php';

// Fungsi untuk mengamankan teks sebelum ditampilkan ke HTML.
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk menentukan lokasi gambar produk.
function gambarItem(?string $gambar): string
{
    if (!$gambar) return '';

    if (filter_var($gambar, FILTER_VALIDATE_URL)) {
        return $gambar;
    }

    return 'assets/uploads/' . rawurlencode(basename($gambar));
}

// --- LOGIKA PROSES TAMBAH KE KERANJANG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {

    // Pengaman: Harus login sebagai pelanggan terlebih dahulu
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
        header('Location: auth/login.php');
        exit;
    }

    $id_item = filter_input(INPUT_POST, 'id_item', FILTER_VALIDATE_INT);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
    $opsi = $_POST['opsi'] ?? 'normal'; // Opsi bisa: 'saja', 'pasang', atau 'normal'

    // Memastikan data pembelian valid.
    if (!$id_item || !$qty || $qty < 1 || !in_array($opsi, ['normal', 'saja', 'pasang'], true)) {
        header('Location: katalog.php?status=invalid');
        exit;
    }

    // Mengambil informasi produk dari database berdasarkan ID.
    $stmtItem = mysqli_prepare($conn, 'SELECT id, tipe, is_oli, stok FROM items WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtItem, 'i', $id_item);
    mysqli_stmt_execute($stmtItem);
    $itemCart = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtItem));
    mysqli_stmt_close($stmtItem);

    // Memastikan produk ditemukan dan merupakan barang.
    if (!$itemCart || $itemCart['tipe'] !== 'barang') {
        header('Location: katalog.php?status=unavailable');
        exit;
    }

    $stok = (int)$itemCart['stok'];

    // Memastikan stok produk masih tersedia.
    if ($stok < 1) {
        header('Location: katalog.php?status=stock');
        exit;
    }

    // Menyesuaikan pilihan pembelian berdasarkan jenis produk.
    if ((int)$itemCart['is_oli'] === 1) {
        if (!in_array($opsi, ['saja', 'pasang'], true)) {
            $opsi = 'saja';
        }
    } else {
        $opsi = 'normal';
    }

    // Membuat KEY unik untuk keranjang agar tidak bercampur antara oli saja & plus pasang.
    $cart_key = (string)$id_item;

    if ($opsi === 'saja') {
        $cart_key = $id_item . "_saja";
    } elseif ($opsi === 'pasang') {
        $cart_key = $id_item . "_pasang";
    }

    // Memeriksa jumlah produk yang sudah ada di keranjang agar tidak melebihi stok.
    $qtySaatIni = (int)($_SESSION['keranjang'][$cart_key] ?? 0);

    if (($qtySaatIni + $qty) > $stok) {
        header('Location: katalog.php?status=stock');
        exit;
    }

    // Masukkan ke dalam session keranjang belanja.
    if (isset($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key] += $qty;
    } else {
        $_SESSION['keranjang'][$cart_key] = $qty;
    }

    header('Location: katalog.php?status=added');
    exit;
}

// --- LOGIKA FILTER PENCARIAN ---

// Mengambil nilai pencarian dan filter dari URL.
$search = trim((string)($_GET['search'] ?? ''));
$tipe = $_GET['tipe'] ?? '';
$kategori = trim((string)($_GET['kategori'] ?? ''));
$tersedia = isset($_GET['tersedia']) && $_GET['tersedia'] === '1';

// Memastikan pilihan jenis item sesuai dengan data yang tersedia.
if (!in_array($tipe, ['', 'barang', 'jasa'], true)) {
    $tipe = '';
}

// Menyiapkan kondisi pencarian database.
$where = [];
$params = [];
$paramTypes = '';

// Mencari produk berdasarkan nama, deskripsi, atau kategori.
if ($search !== '') {
    $where[] = '(nama_item LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?)';
    $keyword = '%' . $search . '%';
    array_push($params, $keyword, $keyword, $keyword);
    $paramTypes .= 'sss';
}

// Memfilter produk berdasarkan jenis barang atau jasa.
if ($tipe !== '') {
    $where[] = 'tipe = ?';
    $params[] = $tipe;
    $paramTypes .= 's';
}

// Memfilter produk berdasarkan kategori.
if ($kategori !== '') {
    $where[] = 'kategori = ?';
    $params[] = $kategori;
    $paramTypes .= 's';
}

// Menampilkan barang yang stoknya tersedia.
if ($tersedia) {
    $where[] = "(tipe = 'jasa' OR stok > 0)";
}

// Mengambil data produk dan layanan dari database.
$sql = 'SELECT id, nama_item, tipe, is_oli, kategori, deskripsi, berat, harga, harga_pasang, stok, gambar FROM items';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY CASE WHEN tipe = 'barang' THEN 0 ELSE 1 END, nama_item ASC";

// Menjalankan pencarian menggunakan prepared statement.
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die('Query katalog gagal disiapkan.');
}

if ($paramTypes !== '') {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Mempertahankan nama variabel lama sampai tampilan katalog diganti.
$query_items = $result;

// Mengambil daftar kategori untuk pilihan filter.
$kategoriResult = mysqli_query($conn, "SELECT DISTINCT kategori FROM items WHERE kategori IS NOT NULL AND kategori <> '' ORDER BY kategori ASC");

$daftarKategori = [];

while ($kategoriRow = mysqli_fetch_assoc($kategoriResult)) {
    $daftarKategori[] = $kategoriRow['kategori'];
}

// Memanggil header/navbar
include 'includes/header.php';
?>

<!-- HERO SECTION -->
<div class="py-12 text-white text-center relative mb-12 bg-gradient-to-br from-[#121F45] to-[#223971] border-b-4 border-[#FFC906]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <h1 class="text-4xl md:text-5xl font-black font-serif-nav uppercase mb-4 tracking-widest text-white">
            Katalog Produk & Layanan
        </h1>

        <p class="text-lg text-gray-200 mb-8 max-w-2xl mx-auto">
            Temukan suku cadang berkualitas, oli original, hingga layanan bengkel Fino Motor.
        </p>

        <!-- FORM PENCARIAN DAN FILTER -->
        <form method="GET" action="katalog.php" class="max-w-5xl mx-auto bg-white p-4 rounded-xl shadow-lg">

            <!-- Kolom pencarian produk -->
            <input
                type="text"
                name="search"
                value="<?= h($search); ?>"
                placeholder="Cari nama, deskripsi, atau kategori produk..."
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-700 mb-3"
            >

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                <!-- Filter jenis barang atau jasa -->
                <select name="tipe" class="border border-gray-300 rounded-lg px-3 py-3 text-gray-700">
                    <option value="">Semua Jenis</option>
                    <option value="barang" <?= $tipe === 'barang' ? 'selected' : ''; ?>>Produk</option>
                    <option value="jasa" <?= $tipe === 'jasa' ? 'selected' : ''; ?>>Layanan</option>
                </select>

                <!-- Filter berdasarkan kategori -->
                <select name="kategori" class="border border-gray-300 rounded-lg px-3 py-3 text-gray-700">
                    <option value="">Semua Kategori</option>

                    <?php foreach ($daftarKategori as $namaKategori): ?>
                        <option value="<?= h($namaKategori); ?>" <?= $kategori === $namaKategori ? 'selected' : ''; ?>>
                            <?= h($namaKategori); ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <!-- Filter ketersediaan stok -->
                <label class="flex items-center gap-2 text-gray-700 px-3 py-3 border border-gray-300 rounded-lg">
                    <input type="checkbox" name="tersedia" value="1" <?= $tersedia ? 'checked' : ''; ?>>
                    Stok tersedia
                </label>

            </div>

            <!-- Tombol pencarian dan reset filter -->
            <div class="flex flex-wrap gap-3 mt-4">
                <button type="submit" class="bg-[#121F45] hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-bold">
                    <i class="fa fa-search mr-2"></i> Cari Produk
                </button>

                <a href="katalog.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-bold">
                    Reset Filter
                </a>
            </div>

        </form>

    </div>
</div>

<!-- PESAN STATUS TAMBAH KERANJANG -->
<?php if (isset($_GET['status'])): ?>
    <div class="max-w-7xl mx-auto px-4 mb-6">

        <?php if ($_GET['status'] === 'added'): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-lg">
                Produk berhasil dimasukkan ke keranjang.
            </div>

        <?php elseif ($_GET['status'] === 'stock'): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg">
                Jumlah pembelian melebihi stok yang tersedia.
            </div>

        <?php elseif ($_GET['status'] === 'invalid'): ?>
            <div class="bg-yellow-100 text-yellow-700 p-4 rounded-lg">
                Data pembelian tidak valid.
            </div>

        <?php elseif ($_GET['status'] === 'unavailable'): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg">
                Produk tidak ditemukan atau tidak tersedia.
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<!-- KATALOG GRID -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

        <?php if (mysqli_num_rows($query_items) > 0): ?>

            <?php while ($row = mysqli_fetch_assoc($query_items)):

                // Mengambil gambar produk dari database.
                $img_src = gambarItem($row['gambar'] ?? null);

                if (!$img_src) {
                    $img_src = "https://via.placeholder.com/400x300?text=Fino+Motor";
                }

                $stok = (int)$row['stok'];
                $isOli = (int)$row['is_oli'] === 1;
                $isBarang = $row['tipe'] === 'barang';
            ?>

            <!-- CARD ITEM -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative flex flex-col hover:-translate-y-2 hover:shadow-xl transition-all duration-300">

                <!-- Badge Tipe -->
                <span class="absolute top-3 left-3 px-3 py-1 text-xs font-bold uppercase text-white rounded-md z-10 <?= $isBarang ? 'bg-blue-600' : 'bg-green-600'; ?>">
                    <?= $isBarang ? 'Produk' : 'Layanan'; ?>
                </span>

                <!-- Gambar Produk -->
                <div class="h-52 w-full bg-gray-100 overflow-hidden">
                    <img src="<?= h($img_src); ?>" class="w-full h-full object-cover" alt="<?= h($row['nama_item']); ?>">
                </div>

                <!-- Konten Card -->
                <div class="p-5 flex flex-col flex-grow">

                    <span class="text-xs text-blue-600 font-bold uppercase">
                        <?= h($row['kategori'] ?: 'Umum'); ?>
                    </span>

                    <h5 class="text-lg font-bold text-gray-900 mt-2">
                        <?= h($row['nama_item']); ?>
                    </h5>

                    <p class="text-sm text-gray-500 mt-2">
                        <?= h($row['deskripsi'] ?: 'Belum ada deskripsi.'); ?>
                    </p>

                    <!-- Menampilkan harga produk dan layanan -->
                    <?php if ($isOli): ?>

                        <div class="mt-3 space-y-1">
                            <span class="text-sm text-gray-500 block">
                                Oli Saja:
                                <b class="text-green-600">
                                    Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                                </b>
                            </span>

                            <span class="text-sm text-gray-500 block">
                                Plus Pasang:
                                <b class="text-red-600">
                                    Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?>
                                </b>
                            </span>
                        </div>

                    <?php else: ?>

                        <h4 class="text-green-600 font-bold text-xl mt-3">
                            Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                        </h4>

                    <?php endif; ?>

                    <!-- Menampilkan stok khusus untuk barang -->
                    <?php if ($isBarang): ?>
                        <p class="text-sm text-gray-500 mt-3">
                            Stok: <?= $stok; ?>
                        </p>
                    <?php endif; ?>

                    <!-- Tombol Detail dan Pembelian -->
                    <div class="mt-auto pt-5 space-y-2">

                        <a href="detail_produk.php?id=<?= (int)$row['id']; ?>"
                           class="block text-center border-2 border-gray-800 text-gray-800 hover:bg-gray-800 hover:text-white font-bold py-2 rounded-lg transition-colors">
                            Lihat Detail
                        </a>

                        <?php if ($isBarang): ?>

                            <?php if ($stok > 0): ?>

                                <?php if ($isOli): ?>

                                    <!-- Tombol Buka Modal Oli -->
                                    <button type="button"
                                            onclick="openModal(<?= (int)$row['id']; ?>)"
                                            class="w-full bg-[#FFC906] hover:bg-yellow-500 text-[#121F45] font-bold py-2.5 rounded-lg">
                                        <i class="fa fa-sliders mr-2"></i> Pilih Opsi Oli
                                    </button>

                                <?php else: ?>

                                    <!-- Form Langsung Pesan -->
                                    <form method="POST" action="katalog.php">
                                        <input type="hidden" name="id_item" value="<?= (int)$row['id']; ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <input type="hidden" name="opsi" value="normal">

                                        <button type="submit" name="tambah_keranjang"
                                                class="w-full bg-gray-900 hover:bg-gray-700 text-white font-bold py-2.5 rounded-lg">
                                            <i class="fa fa-shopping-basket mr-2"></i> Pesan Sekarang
                                        </button>
                                    </form>

                                <?php endif; ?>

                            <?php else: ?>

                                <button type="button" disabled
                                        class="w-full bg-gray-300 text-gray-600 font-bold py-2.5 rounded-lg cursor-not-allowed">
                                    Stok Habis
                                </button>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- MODAL PILIHAN OLI -->
            <?php if ($isBarang && $isOli): ?>

                <div id="modalOli<?= (int)$row['id']; ?>"
                     class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black bg-opacity-50">

                    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">

                        <!-- Header Modal -->
                        <div class="bg-[#121F45] px-6 py-4 flex justify-between items-center">
                            <h5 class="text-white font-bold text-lg">
                                <i class="fa fa-oil-can text-[#FFC906] mr-2"></i>
                                Opsi Pemasangan Oli
                            </h5>

                            <button type="button"
                                    onclick="closeModal(<?= (int)$row['id']; ?>)"
                                    class="text-white text-xl">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <form method="POST" action="katalog.php">
                            <input type="hidden" name="id_item" value="<?= (int)$row['id']; ?>">

                            <div class="p-6">

                                <div class="text-center mb-5">
                                    <img src="<?= h($img_src); ?>"
                                         class="rounded-xl mx-auto mb-3 h-28 w-28 object-cover">

                                    <h4 class="font-bold text-gray-900 text-xl">
                                        <?= h($row['nama_item']); ?>
                                    </h4>
                                </div>

                                <!-- Pilihan Jenis Pembelian Oli -->
                                <div class="space-y-3 mb-5">

                                    <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer">
                                        <div>
                                            <input type="radio" name="opsi" value="saja" checked>
                                            <span class="font-bold">Oli Saja</span>
                                        </div>

                                        <span class="text-green-600 font-bold">
                                            Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                                        </span>
                                    </label>

                                    <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer">
                                        <div>
                                            <input type="radio" name="opsi" value="pasang">
                                            <span class="font-bold">Plus Pasang</span>
                                        </div>

                                        <span class="text-red-600 font-bold">
                                            Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?>
                                        </span>
                                    </label>

                                </div>

                                <!-- Input Jumlah Pembelian -->
                                <label class="text-sm font-bold text-gray-500 mb-2 block">
                                    Jumlah (Qty)
                                </label>

                                <input type="number" name="qty"
                                       class="w-full border rounded-lg text-center font-bold text-xl py-2"
                                       value="1" min="1" max="<?= $stok; ?>" required>

                            </div>

                            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">

                                <button type="button"
                                        onclick="closeModal(<?= (int)$row['id']; ?>)"
                                        class="px-5 py-2 font-bold text-gray-600 hover:bg-gray-200 rounded-lg">
                                    Batal
                                </button>

                                <button type="submit" name="tambah_keranjang"
                                        class="px-5 py-2 font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg">
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
                <h5 class="text-xl font-medium">
                    Produk atau layanan tidak ditemukan.
                </h5>
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