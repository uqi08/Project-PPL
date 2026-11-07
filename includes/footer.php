</main> <!-- Menutup tag <main> yang sebelumnya kita buka di header.php -->

<!-- FOOTER START -->
<footer class="bg-[#121F45] text-white border-t-4 border-red-600 mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Grid Layout (Dibagi menjadi 12 kolom untuk meniru col-md Bootstrap) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            
            <!-- Kolom 1: Logo & Tagline (Porsi 4 kolom) -->
            <div class="md:col-span-4 text-center md:text-left">
                <!-- Memanggil logo dari folder assets -->
                <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo" class="mb-4 mx-auto md:mx-0 h-24 object-contain drop-shadow-lg">
                <p class="text-gray-300 italic font-bold text-lg tracking-wide">
                    "Cuci Motor Detail, Ganti Oli Tepat, Ban Nyaman!"
                </p>
            </div>
            
            <!-- Kolom 2: Lokasi (Porsi 5 kolom) -->
            <div class="md:col-span-5 text-center md:text-left">
                <h5 class="font-bold text-[#FFC906] text-xl mb-3">
                    <i class="fa fa-map-marker-alt text-red-500 mr-2"></i> Lokasi Kami
                </h5>
                <p class="font-semibold text-gray-200 mb-4 leading-relaxed">
                    Jl. Ki Lemah Duwur, Pacian, Bancaran, Kec. Bangkalan,<br>
                    Kabupaten Bangkalan, Jawa Timur 69112.
                </p>
                <a href="https://maps.app.goo.gl/QWoUbfXx6PWQUVLf6" target="_blank" class="inline-block bg-[#FFC906] hover:bg-yellow-500 text-gray-900 font-bold px-6 py-2 rounded-lg shadow-md transition-colors">
                    <i class="fa fa-directions mr-2"></i> Buka di Google Maps
                </a>
            </div>

            <!-- Kolom 3: Jam Buka (Porsi 3 kolom) -->
            <div class="md:col-span-3 text-center md:text-right">
                <h5 class="font-bold text-[#FFC906] text-xl mb-3">
                    <i class="fa fa-clock mr-2"></i> Jam Buka
                </h5>
                <p class="font-bold text-gray-200 text-lg mb-1">Setiap Hari Kerja</p>
                <p class="text-gray-400 font-semibold text-base">07.30 - 16.30 WIB</p>
            </div>
        </div>
        
        <!-- Garis Pemisah -->
        <hr class="border-gray-700 my-8">
        
        <!-- Copyright -->
        <div class="text-center">
            <p class="text-sm text-gray-400 font-semibold">
                © <?php echo date('Y'); ?> Fino Motor-cycle wash & Garage. All rights reserved.
            </p>
        </div>
    </div>
</footer>
<!-- FOOTER END -->

</body>
</html>