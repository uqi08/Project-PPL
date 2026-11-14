<?php
// detail_produk.php
session_start();
require 'config/database.php';

// Mengamankan teks sebelum ditampilkan ke HTML.
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Mengambil ID produk dari URL.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Mengambil detail produk berdasarkan ID.
$item = null;

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM items WHERE id = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
}

// Menentukan lokasi gambar produk.
$img_src = '';

if ($item && !empty($item['gambar'])) {
    if (filter_var($item['gambar'], FILTER_VALIDATE_URL)) {
        $img_src = $item['gambar'];
    } else {
        $img_src = 'assets/uploads/' . rawurlencode(basename($item['gambar']));
    }
} else {
    $img_src = 'https://via.placeholder.com/500x400?text=Fino+Motor';
}

include 'includes/header.php';
?>

<!-- DETAIL PRODUK -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <?php if (!$item): ?>

        <!-- Pesan jika produk tidak ditemukan -->
        <div class="text-center py-20">
            <i class="fa fa-box-open text-5xl text-gray-400 mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-800">
                Produk tidak ditemukan.
            </h2>

            <a href="katalog.php"
               class="inline-block mt-5 bg-[#121F45] text-white px-6 py-3 rounded-lg font-bold">
                Kembali ke Katalog
            </a>
        </div>

    <?php else: ?>

        <!-- Tombol kembali ke katalog -->
        <a href="katalog.php" class="text-blue-700 font-bold hover:underline">
            <i class="fa fa-arrow-left mr-2"></i> Kembali ke Katalog
        </a>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mt-8">

            <!-- Gambar Produk -->
            <div class="bg-white rounded-2xl shadow-md p-5">
                <img src="<?= h($img_src); ?>"
                     alt="<?= h($item['nama_item']); ?>"
                     class="w-full max-h-[450px] object-contain rounded-xl">
            </div>

            <!-- Informasi Produk -->
            <div class="bg-white rounded-2xl shadow-md p-6">

                <span class="text-sm font-bold text-blue-600 uppercase">
                    <?= $item['tipe'] === 'barang' ? 'Produk' : 'Layanan'; ?>
                </span>

                <h1 class="text-3xl font-bold text-gray-900 mt-3">
                    <?= h($item['nama_item']); ?>
                </h1>

                <p class="text-gray-500 mt-2">
                    Kategori: <?= h($item['kategori'] ?: 'Umum'); ?>
                </p>

                <div class="border-t border-b border-gray-200 py-5 my-5">

                    <!-- Menampilkan harga berdasarkan jenis produk -->
                    <?php if ((int)$item['is_oli'] === 1): ?>

                        <p class="text-gray-600">
                            Oli Saja:
                            <b class="text-green-600">
                                Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
                            </b>
                        </p>

                        <p class="text-gray-600 mt-2">
                            Plus Pasang:
                            <b class="text-red-600">
                                Rp <?= number_format($item['harga_pasang'], 0, ',', '.'); ?>
                            </b>
                        </p>

                    <?php else: ?>

                        <p class="text-2xl font-bold text-green-600">
                            Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
                        </p>

                    <?php endif; ?>

                </div>

                <!-- Deskripsi Produk -->
                <h3 class="font-bold text-gray-800 mb-2">Deskripsi</h3>

                <p class="text-gray-600 leading-relaxed">
                    <?= nl2br(h($item['deskripsi'] ?: 'Belum ada deskripsi produk.')); ?>
                </p>

                <!-- Informasi stok dan berat -->
                <?php if ($item['tipe'] === 'barang'): ?>

                    <div class="mt-5 space-y-2 text-gray-600">
                        <p>Stok: <b><?= (int)$item['stok']; ?></b></p>

                        <?php if (!empty($item['berat'])): ?>
                            <p>Berat: <b><?= h($item['berat']); ?> gram</b></p>
                        <?php endif; ?>
                    </div>

                    <!-- Form tambah produk ke keranjang -->
                    <?php if ((int)$item['stok'] > 0): ?>

                        <form method="POST" action="katalog.php" class="mt-6">

                            <input type="hidden" name="id_item" value="<?= (int)$item['id']; ?>">

                            <!-- Pilihan pembelian untuk produk oli -->
                            <?php if ((int)$item['is_oli'] === 1): ?>

                                <label class="block font-bold text-gray-700 mb-2">
                                    Pilihan Pembelian
                                </label>

                                <select name="opsi" class="w-full border rounded-lg p-3 mb-4">
                                    <option value="saja">Oli Saja</option>
                                    <option value="pasang">Oli + Jasa Pasang</option>
                                </select>

                            <?php else: ?>

                                <input type="hidden" name="opsi" value="normal">

                            <?php endif; ?>

                            <label class="block font-bold text-gray-700 mb-2">
                                Jumlah Pembelian
                            </label>

                            <input type="number" name="qty"
                                   value="1" min="1"
                                   max="<?= (int)$item['stok']; ?>"
                                   required
                                   class="w-full border rounded-lg p-3 mb-4">

                            <button type="submit" name="tambah_keranjang"
                                    class="w-full bg-[#121F45] hover:bg-blue-900 text-white font-bold py-3 rounded-lg">
                                <i class="fa fa-shopping-cart mr-2"></i>
                                Tambahkan ke Keranjang
                            </button>

                        </form>

                    <?php else: ?>

                        <button disabled
                                class="w-full mt-6 bg-gray-300 text-gray-600 font-bold py-3 rounded-lg">
                            Stok Habis
                        </button>

                    <?php endif; ?>

                <?php else: ?>

                    <!-- Informasi khusus untuk layanan -->
                    <div class="mt-6 bg-blue-50 text-blue-800 p-4 rounded-lg">
                        Layanan ini dapat dipesan melalui bengkel Fino Motor.
                    </div>

                <?php endif; ?>

            </div>
        </div>

    <?php endif; ?>

</div>

<?php
// Memanggil footer.
include 'includes/footer.php';
?>