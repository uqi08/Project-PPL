<?php
// includes/cart_helper.php

/**
 * Memecah key keranjang ('6', '6_saja', '6_pasang') menjadi [item_id, opsi_item]
 */
function parseCartKey($cart_key) {
    $id_item = (int)$cart_key;
    $opsi = 'normal';

    if (strpos((string)$cart_key, '_saja') !== false) {
        $opsi = 'bawa_pulang';
    } elseif (strpos((string)$cart_key, '_pasang') !== false) {
        $opsi = 'plus_pasang';
    }

    return [$id_item, $opsi];
}

/**
 * Daftar estimasi biaya kurir sementara sebelum integrasi API RajaOngkir/Biteship
 */
function getDaftarKurir() {
    return [
        'JNE REG'      => ['nama' => 'JNE Regular (2-3 Hari)', 'tarif_per_kg' => 12000],
        'JNT EXPRESS'  => ['nama' => 'J&T Express (1-2 Hari)', 'tarif_per_kg' => 13000],
        'SICEPAT REG'  => ['nama' => 'SiCepat REG (2-3 Hari)', 'tarif_per_kg' => 11000],
        'AMBIL_TOKO'   => ['nama' => 'Ambil Langsung di Bengkel', 'tarif_per_kg' => 0],
    ];
}

/**
 * WBS-2.4.3 & WBS-2.5.2:
 * Mengambil detail barang dari database, menghitung subtotal per item,
 * subtotal keranjang, total kuantitas, dan estimasi berat (gram/kg).
 */
function getRingkasanKeranjang($conn, $kurir_dipilih = null) {
    $keranjang = $_SESSION['keranjang'] ?? [];
    $items = [];
    $subtotal_keranjang = 0;
    $total_qty = 0;
    $total_berat_gram = 0;

    if (!empty($keranjang)) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama_item, tipe, is_oli, harga, harga_pasang, stok, gambar 
             FROM items WHERE id = ?"
        );

        foreach ($keranjang as $cart_key => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) {
                unset($_SESSION['keranjang'][$cart_key]);
                continue;
            }

            list($item_id, $opsi) = parseCartKey($cart_key);
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            // Jika produk sudah dihapus dari database, bersihkan dari keranjang
            if (!$row) {
                unset($_SESSION['keranjang'][$cart_key]);
                continue;
            }

            // Harga satuan resmi dari database (tidak bisa dimanipulasi dari frontend)
            $harga_satuan = ($opsi === 'plus_pasang' && (int)$row['harga_pasang'] > 0)
                ? (int)$row['harga_pasang']
                : (int)$row['harga'];

            $subtotal_item = $harga_satuan * $qty;

            // Default berat 800gr per barang fisik jika kolom berat belum dipakai
            $berat_satuan = ($row['tipe'] === 'barang') ? 800 : 0;
            $berat_total = $berat_satuan * $qty;

            $subtotal_keranjang += $subtotal_item;
            $total_qty += $qty;
            $total_berat_gram += $berat_total;

            $items[] = [
                'cart_key'     => (string)$cart_key,
                'id'           => (int)$row['id'],
                'nama_item'    => $row['nama_item'],
                'tipe'         => $row['tipe'],
                'opsi_item'    => $opsi,
                'harga_satuan' => $harga_satuan,
                'qty'          => $qty,
                'stok'         => (int)$row['stok'],
                'berat_total'  => $berat_total,
                'subtotal'     => $subtotal_item,
                'gambar'       => $row['gambar'],
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $berat_kg = ($total_berat_gram > 0) ? max(1, (int)ceil($total_berat_gram / 1000)) : 0;

    // Hitung biaya kirim berdasarkan kurir yang dipilih
    $daftar_kurir = getDaftarKurir();
    $ongkir = 0;
    if ($kurir_dipilih && isset($daftar_kurir[$kurir_dipilih])) {
        $ongkir = $daftar_kurir[$kurir_dipilih]['tarif_per_kg'] * max(1, $berat_kg);
    }

    return [
        'items'              => $items,
        'total_qty'          => $total_qty,
        'total_berat_gram'   => $total_berat_gram,
        'berat_kg'           => $berat_kg,
        'subtotal_keranjang' => $subtotal_keranjang,
        'ongkir'             => $ongkir,
        'total_bayar'        => $subtotal_keranjang + $ongkir,
    ];
}
?>