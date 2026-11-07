<?php
// pelanggan/proses_booking.php
session_start();
require '../config/database.php';

// 1. KEAMANAN: Pastikan hanya Pelanggan yang login yang bisa mengakses file ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pelanggan_id = $_SESSION['user_id'];
    
    // Menerima data dari keranjang belanja
    $banyak_item_dipilih = $_POST['item_id']; 
    $jumlah_beli = $_POST['jumlah'];
    
    // Menerima data opsi pasang (Berupa array 'ya' atau 'tidak' yang sejajar dengan item_id)
    // Jika tidak ada data yang dikirim (misal beli sparepart), default ke array kosong
    $opsi_pasang_array = isset($_POST['opsi_pasang']) ? $_POST['opsi_pasang'] : [];

    // Membuat kode transaksi dengan inisial 'BKG'
    $tanggal = date('Ymd');
    $random  = rand(100, 999);
    $kode_transaksi = "BKG-" . $tanggal . "-" . $random;

    // Menentukan batas waktu maksimal 24 Jam
    $batas_waktu_pengambilan = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // ==============================================================================
    // KONFIGURASI MASTER JASA GANTI OLI
    // PENTING: Ubah angka 99 di bawah ini menjadi ID yang sesuai dengan ID 
    // item "Jasa Ganti Oli" di tabel 'items' database phpMyAdmin kamu nantinya.
    // ==============================================================================
    $id_jasa_ganti_oli = 99; 
    $harga_jasa_pasang = 5000;

    // Mulai Database Transaction
    mysqli_begin_transaction($conn);

    try {
        // A. Insert ke tabel transactions dengan status 'pending'
        $query_booking = "INSERT INTO transactions (kode_transaksi, pelanggan_id, batas_waktu_pengambilan, status_transaksi, total_biaya) 
                          VALUES ('$kode_transaksi', $pelanggan_id, '$batas_waktu_pengambilan', 'pending', 0)";
        mysqli_query($conn, $query_booking);
        
        $transaction_id = mysqli_insert_id($conn);
        $total_pembayaran_akhir = 0;

        // B. Looping item di keranjang
        foreach ($banyak_item_dipilih as $index => $item_id) {
            $id_item = (int)$item_id;
            $qty     = (int)$jumlah_beli[$index];
            
            // Ambil pilihan opsi pasang untuk item ini (default: 'tidak')
            $opsi_pasang = isset($opsi_pasang_array[$index]) ? $opsi_pasang_array[$index] : 'tidak';

            // Cek harga, tipe, stok, dan kategori (untuk mengecek apakah ini oli mesin/gear)
            $query_item = mysqli_query($conn, "SELECT nama_item, harga, tipe, stok, kategori FROM items WHERE id = $id_item");
            $item_data  = mysqli_fetch_assoc($query_item);

            // Validasi: Blokir Jasa
            if ($item_data['tipe'] === 'jasa') {
                throw new Exception("Layanan jasa ({$item_data['nama_item']}) tidak bisa di-booking secara online.");
            }

            // Validasi: Cek Stok
            if ($item_data['stok'] < $qty) {
                throw new Exception("Maaf, stok {$item_data['nama_item']} tidak mencukupi atau baru saja habis.");
            }

            // C. KUNCI STOK BARANG FISIK
            $query_potong_stok = "UPDATE items SET stok = stok - $qty WHERE id = $id_item";
            mysqli_query($conn, $query_potong_stok);

            // Simpan rincian barang ke tabel details
            $harga_satuan = $item_data['harga'];
            $subtotal = $harga_satuan * $qty;
            $total_pembayaran_akhir += $subtotal;

            $query_detail = "INSERT INTO transaction_details (transaction_id, item_id, jumlah, harga_satuan) 
                             VALUES ($transaction_id, $id_item, $qty, $harga_satuan)";
            mysqli_query($conn, $query_detail);

            // D. INJEKSI OTOMATIS JASA PASANG
            // Pastikan itemnya masuk kategori oli_mesin atau oli_gear, DAN pelanggan memilih 'ya'
            if ($opsi_pasang === 'ya' && in_array($item_data['kategori'], ['oli_mesin', 'oli_gear'])) {
                
                $query_tambah_jasa = "INSERT INTO transaction_details (transaction_id, item_id, jumlah, harga_satuan) 
                                      VALUES ($transaction_id, $id_jasa_ganti_oli, $qty, $harga_jasa_pasang)";
                mysqli_query($conn, $query_tambah_jasa);
                
                // Tambahkan biaya jasa ke total tagihan (Dikalikan qty jika olinya beli 2, jasanya dihitung 2)
                $total_pembayaran_akhir += ($harga_jasa_pasang * $qty);
            }
        }

        // E. Update Grand Total Pembayaran
        mysqli_query($conn, "UPDATE transactions SET total_biaya = $total_pembayaran_akhir WHERE id = $transaction_id");

        // Simpan permanen ke database
        mysqli_commit($conn);

        echo "<script>
                alert('Booking Berhasil! Pesanan Anda harus diambil maksimal pada " . date('d M Y, H:i', strtotime($batas_waktu_pengambilan)) . "'); 
                window.location.href='riwayat_pesanan.php';
              </script>";

    } catch (Exception $e) {
        // Batalkan seluruh proses jika terjadi error
        mysqli_rollback($conn);
        $pesan_error = $e->getMessage();
        echo "<script>alert('Gagal: $pesan_error'); window.location.href='keranjang.php';</script>";
    }

} else {
    header("Location: katalog.php");
    exit;
}
?>