<?php
// Pastikan session sudah dimulai di file utama (seperti index.php atau katalog.php) 
// sebelum memanggil header ini, agar variabel $_SESSION bisa digunakan.
// session_start(); 

// Variabel default untuk nama user jika belum login
$nama_user = isset($_SESSION['nama_user']) ? $_SESSION['nama_user'] : 'edward';
$base_url  = '/Fino_Motor'; // Sesuaikan dengan nama folder root project kamu di htdocs/www
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fino Motor</title>
    
    <!-- Tailwind CSS CDN untuk Fondasi Responsif -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome CDN untuk Ikon (Cart & User) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts untuk tipografi serif pada menu navigasi -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&display=swap" rel="stylesheet">
    
    <style>
        .font-serif-nav {
            font-family: 'Merriweather', serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

<!-- HEADER START -->
<header class="bg-[#30405d] shadow-md w-full sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            
            <!-- BAGIAN KIRI: Logo & Navigasi -->
            <div class="flex items-center gap-10">
                <!-- Logo -->
                <a href="<?php echo $base_url; ?>/index.php" class="flex-shrink-0 flex items-center">
                    <!-- Mengambil gambar dari folder assets, ukuran diperbesar menjadi h-16 (HP) dan h-20 (PC) -->
                    <img class="h-16 md:h-20 w-auto object-contain drop-shadow-sm" src="<?php echo $base_url; ?>/assets/logo_text.png" alt="Logo Fino Motor">
                </a>

                <!-- Navigasi Utama (Hidden di layar HP, muncul di layar besar) -->
                <nav class="hidden md:flex space-x-8 mt-1">
                    <a href="<?php echo $base_url; ?>/index.php" class="font-serif-nav text-white hover:text-gray-300 text-[1.1rem] tracking-wide transition-colors">Beranda</a>
                    <!-- Mengarah ke halaman katalog -->
                    <a href="<?php echo $base_url; ?>/katalog.php" class="font-serif-nav text-white hover:text-gray-300 text-[1.1rem] tracking-wide transition-colors">Produk & Layanan</a>
                </nav>
            </div>

            <!-- BAGIAN KANAN: Ikon Keranjang & Profil User -->
            <div class="flex items-center space-x-8">
                <!-- Keranjang -->
                <a href="<?php echo $base_url; ?>/pelanggan/keranjang.php" class="text-white hover:text-gray-300 relative transition-colors">
                    <i class="fa-solid fa-cart-shopping text-2xl"></i>
                    <!-- Contoh Badge Notifikasi Keranjang (Opsional, hilangkan class 'hidden' untuk menampilkan) -->
                    <span class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[0.65rem] font-bold rounded-full h-4 w-4 flex items-center justify-center hidden">2</span>
                </a>

                <!-- User Dropdown Menu -->
                <div class="relative group cursor-pointer">
                    <div class="flex items-center space-x-2 text-white hover:text-gray-300 transition-colors py-2">
                        <!-- Warna ikon user disesuaikan menjadi kuning/emas -->
                        <i class="fa-solid fa-circle-user text-[#ffb800] text-2xl"></i>
                        <span class="font-bold text-lg font-sans tracking-wide"><?php echo htmlspecialchars($nama_user); ?></span>
                        <i class="fa-solid fa-caret-down text-sm"></i>
                    </div>
                    
                    <!-- Isi Dropdown (Muncul saat di-hover) -->
                    <div class="absolute right-0 mt-0 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block transition-all duration-300 border border-gray-100">
                        <a href="<?php echo $base_url; ?>/pelanggan/profil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-user-pen mr-2"></i> Profil Saya</a>
                        <a href="<?php echo $base_url; ?>/pelanggan/riwayat.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-clock-rotate-left mr-2"></i> Riwayat Pesanan</a>
                        <hr class="my-1 border-gray-200">
                        <!-- Mengarah ke proses logout -->
                        <a href="<?php echo $base_url; ?>/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 font-semibold"><i class="fa-solid fa-right-from-bracket mr-2"></i> Keluar</a>
                    </div>
                </div>
                
                <!-- Hamburger Menu untuk Mobile (Opsional) -->
                <div class="md:hidden flex items-center">
                    <button class="text-white hover:text-gray-300 focus:outline-none">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</header>
<!-- HEADER END -->

<!-- Main Content Area Starts Here (akan ditutup di footer.php) -->
<main class="flex-grow">