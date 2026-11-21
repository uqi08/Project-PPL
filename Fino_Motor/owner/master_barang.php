<?php
// owner/master_barang.php
session_start();
require '../config/database.php';

// Proteksi Akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$pesan_tipe = '';

// --- 1. LOGIKA TAMBAH DATA ---
if (isset($_POST['tambah_item'])) {
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $tipe = $_POST['tipe'];
    $kategori = $_POST['kategori'];
    $harga = (int)$_POST['harga'];
    $stok = ($tipe === 'barang') ? (int)$_POST['stok'] : 0;
    
    // Logika Oli otomatis
    $is_oli = ($kategori === 'Oli') ? 1 : 0;
    $harga_pasang = $is_oli ? (int)$_POST['harga_pasang'] : 0;

    $sumber_gambar = $_POST['sumber_gambar'];
    $gambar = '';

    if ($sumber_gambar === 'url') {
        $gambar = mysqli_real_escape_string($conn, $_POST['gambar_url']);
    } elseif ($sumber_gambar === 'upload' && isset($_FILES['gambar_file']['name']) && $_FILES['gambar_file']['name'] != '') {
        $nama_file = time() . '_' . basename($_FILES['gambar_file']['name']);
        if (move_uploaded_file($_FILES['gambar_file']['tmp_name'], "../assets/uploads/" . $nama_file)) {
            $gambar = $nama_file;
        }
    }

    $query = "INSERT INTO items (nama_item, tipe, kategori, stok, harga, harga_pasang, is_oli, gambar) 
              VALUES ('$nama_item', '$tipe', '$kategori', $stok, $harga, $harga_pasang, $is_oli, '$gambar')";
    
    if (mysqli_query($conn, $query)) {
        $pesan = "Produk/Jasa berhasil ditambahkan!"; $pesan_tipe = "success";
    } else {
        $pesan = "Gagal menambahkan data."; $pesan_tipe = "danger";
    }
}

// --- 2. LOGIKA EDIT DATA ---
if (isset($_POST['edit_item'])) {
    $id_item = (int)$_POST['id_item'];
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $tipe = $_POST['tipe'];
    $kategori = $_POST['kategori'];
    $harga = (int)$_POST['harga'];
    $stok = ($tipe === 'barang') ? (int)$_POST['stok'] : 0;
    
    $is_oli = ($kategori === 'Oli') ? 1 : 0;
    $harga_pasang = $is_oli ? (int)$_POST['harga_pasang'] : 0;

    $aksi_gambar = $_POST['aksi_gambar'];
    $gambar_tambahan = ""; 

    if ($aksi_gambar === 'url') {
        $gambar_baru = mysqli_real_escape_string($conn, $_POST['gambar_url']);
        $gambar_tambahan = ", gambar = '$gambar_baru'";
    } elseif ($aksi_gambar === 'upload' && isset($_FILES['gambar_file']['name']) && $_FILES['gambar_file']['name'] != '') {
        $nama_file = time() . '_' . basename($_FILES['gambar_file']['name']);
        if (move_uploaded_file($_FILES['gambar_file']['tmp_name'], "../assets/uploads/" . $nama_file)) {
            $gambar_tambahan = ", gambar = '$nama_file'";
        }
    }

    $query_update = "UPDATE items SET 
                        nama_item = '$nama_item', 
                        tipe = '$tipe', 
                        kategori = '$kategori', 
                        stok = $stok, 
                        harga = $harga, 
                        harga_pasang = $harga_pasang, 
                        is_oli = $is_oli 
                        $gambar_tambahan 
                     WHERE id = $id_item";
                     
    if (mysqli_query($conn, $query_update)) {
        $pesan = "Data berhasil diperbarui!"; $pesan_tipe = "success";
    } else {
        $pesan = "Gagal memperbarui data."; $pesan_tipe = "danger";
    }
}

// --- 3. LOGIKA HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM items WHERE id = $id_hapus");
    header("Location: master_barang.php?msg=hapus_sukses"); exit;
}
if(isset($_GET['msg']) && $_GET['msg'] == 'hapus_sukses'){
    $pesan = "Data berhasil dihapus."; $pesan_tipe = "success";
}

// --- 4. LOGIKA FILTER & PENCARIAN SEKALIGUS ---
$filter = $_GET['filter'] ?? 'Semua Produk';
$search = $_GET['search'] ?? '';

$conditions = [];

if ($filter !== 'Semua Produk') {
    $safe_filter = mysqli_real_escape_string($conn, $filter);
    if ($safe_filter === 'Jasa') {
        $conditions[] = "tipe = 'jasa'";
    } else {
        $conditions[] = "kategori = '$safe_filter' AND tipe = 'barang'";
    }
}

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $conditions[] = "nama_item LIKE '%$safe_search%'";
}

$where_clause = "";
if (count($conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Ambil data dari database
$query_items = mysqli_query($conn, "SELECT * FROM items $where_clause ORDER BY tipe ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Gudang - Fino Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; margin: 0; overflow-x: hidden; }
        
        /* SIDEBAR DEFAULT DESKTOP */
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background-color: #121F45; color: white; padding-top: 20px; border-right: 4px solid #FFC906; z-index: 1050; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 10px 0; margin-bottom: 5px !important; }
        .sidebar-brand img { max-width: 85%; height: auto; }
        .sidebar-menu { list-style: none; padding: 0; margin-top: 0px; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { color: white; background-color: rgba(255,255,255,0.08); border-left: 5px solid #CC1E4A; }
        .sidebar-menu li a i { width: 30px; }
        
        /* MAIN CONTENT */
        .main-content { margin-left: 260px; padding: 40px; min-height: 100vh; transition: margin-left 0.3s ease; }
        
        .item-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border-radius: 15px; border: none; overflow: hidden; }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .img-wrapper { height: 160px; overflow: hidden; background-color: #eee; position: relative; }
        .img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .filter-btn { border-radius: 20px; font-weight: 600; padding: 8px 20px; margin-right: 10px; margin-bottom: 10px; transition: 0.3s; }

        /* MOBILE RESPONSIVE QUERIES */
        .mobile-toggle { display: none; background: none; border: none; font-size: 24px; color: #121F45; cursor: pointer; padding: 0; margin-right: 15px; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; opacity: 0; transition: opacity 0.3s ease; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); } 
            .main-content { margin-left: 0 !important; padding: 20px !important; } 
            .mobile-toggle { display: block; } 
            .sidebar-overlay.active { display: block; opacity: 1; }
            .header-title-container { flex-direction: column; align-items: flex-start !important; gap: 15px; }
            
            /* Menyesuaikan kotak search dan filter di HP */
            .search-form { width: 100%; margin-top: 15px; }
            .search-form .input-group { width: 100% !important; }
        }
    </style>
</head>
<body>
    
    <!-- OVERLAY GELAP UNTUK MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand text-center position-relative">
            <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo">
            <!-- Tombol Close Sidebar (Hanya tampil di HP) -->
            <button class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-3 d-md-none" onclick="toggleSidebar()"></button>
        </div>
        <ul class="sidebar-menu mt-3">
            <li><a href="dashboard.php"><i class="fa fa-chart-pie text-white"></i> Dashboard Utama</a></li>
            <li><a href="kasir.php"><i class="fa fa-cash-register text-white"></i> Mesin Kasir</a></li>
            <li><a href="master_barang.php" class="active"><i class="fa fa-boxes text-white"></i> Master Gudang</a></li>
            <li><a href="uang_masuk.php"><i class="fa fa-wallet text-white"></i> Uang Masuk</a></li>
            <li><a href="uang_keluar.php"><i class="fa fa-hand-holding-usd text-white"></i> Uang Keluar</a></li>
            <li><a href="tambah_admin.php"><i class="fa fa-user-shield text-white"></i> Manajemen Staf</a></li>
            <li><a href="profil.php"><i class="fa fa-id-card text-white"></i> Profil Saya</a></li>
            <li class="mt-4 border-top border-secondary pt-2">
                <a href="../index.php" class="text-info fw-bold"><i class="fa fa-home"></i> Ke Halaman Utama</a>
            </li>
            <li><a href="../auth/logout.php" class="text-danger fw-bold"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 header-title-container">
            <div class="d-flex align-items-center">
                <!-- TOMBOL HAMBURGER MOBILE -->
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                <h2 class="m-0" style="font-family:'Playfair Display',serif; font-weight:900; color:#121F45;">
                    <i class="fa fa-boxes text-warning me-2 d-none d-sm-inline"></i> Master Inventaris
                </h2>
            </div>
            
            <button class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fa fa-plus me-2"></i> Tambah Item
            </button>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $pesan_tipe ?> py-3 rounded-4 shadow-sm fw-bold">
                <i class="fa <?= $pesan_tipe == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $pesan; ?>
            </div>
        <?php endif; ?>

        <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
            <div class="d-flex flex-wrap m-0">
                <?php 
                $filters = ['Semua Produk', 'Oli', 'Ban', 'Sparepart', 'Perawatan Motor', 'Jasa'];
                foreach($filters as $f): 
                    $search_param = !empty($search) ? '&search=' . urlencode($search) : '';
                    $active_class = ($filter === $f) ? 'btn-warning text-dark border-warning' : 'btn-outline-secondary bg-light border-0 text-secondary shadow-sm';
                ?>
                    <a href="master_barang.php?filter=<?= urlencode($f); ?><?= $search_param; ?>" class="btn filter-btn <?= $active_class; ?>">
                        <?= $f; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <form method="GET" action="" class="d-flex ms-auto ms-md-3 search-form">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                <div class="input-group shadow-sm rounded-pill overflow-hidden border bg-light">
                    <input type="text" name="search" class="form-control border-0 bg-transparent px-3 py-2" placeholder="Cari nama produk..." value="<?= htmlspecialchars($search); ?>" style="min-width: 220px;">
                    <button class="btn btn-warning fw-bold text-dark border-0 px-3" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </form>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php if (mysqli_num_rows($query_items) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query_items)): 
                    $img_src = '';
                    if (!empty($row['gambar'])) {
                        $img_src = filter_var($row['gambar'], FILTER_VALIDATE_URL) ? $row['gambar'] : "/Fino_Motor/assets/uploads/" . $row['gambar'];
                    } else {
                        $img_src = "https://via.placeholder.com/400x300?text=No+Img"; 
                    }
                ?>
                <div class="col">
                    <div class="card item-card h-100 bg-white">
                        <div class="position-absolute top-0 start-0 m-2 z-1">
                            <span class="badge <?= $row['tipe'] == 'barang' ? 'bg-primary' : 'bg-success' ?> shadow-sm text-uppercase"><?= $row['tipe']; ?></span>
                        </div>
                        
                        <div class="img-wrapper">
                            <img src="<?= $img_src; ?>" alt="Produk">
                        </div>
                        
                        <div class="card-body d-flex flex-column p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold text-dark m-0 lh-sm"><?= htmlspecialchars($row['nama_item']); ?></h6>
                            </div>
                            
                            <div class="mb-2">
                                <span class="badge bg-light text-dark border"><i class="fa fa-tag text-muted me-1"></i><?= $row['kategori']; ?></span>
                                <?php if($row['tipe'] == 'barang'): ?>
                                    <span class="badge <?= $row['stok'] < 5 ? 'bg-danger text-white' : 'bg-light text-dark border' ?>"><i class="fa fa-box text-muted me-1"></i><?= $row['stok']; ?> Pcs</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto">
                                <h5 class="text-success fw-bold m-0">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h5>
                                <?php if($row['is_oli'] == 1 && $row['harga_pasang'] > 0): ?>
                                    <small class="text-danger fw-bold d-block mt-1">+ Pasang: Rp <?= number_format($row['harga_pasang'], 0, ',', '.'); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-light border-0 p-3 d-flex gap-2">
                            <button class="btn btn-sm btn-primary w-50 fw-bold rounded-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>"><i class="fa fa-edit"></i> Edit</button>
                            <a href="master_barang.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-danger w-50 fw-bold rounded-3" onclick="return confirm('Hapus item ini?');"><i class="fa fa-trash"></i> Hapus</a>
                        </div>
                    </div>
                </div>

                <!-- MODAL EDIT KHUSUS UNTUK ITEM INI -->
                <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content rounded-4 border-0 shadow-lg">
                            <div class="modal-header text-white" style="background-color: var(--rb-dark-navy);">
                                <h5 class="modal-title font-serif fw-bold"><i class="fa fa-edit text-warning me-2"></i>Edit Data: <?= htmlspecialchars($row['nama_item']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="id_item" value="<?= $row['id']; ?>">
                                <div class="modal-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-bold">Nama Barang / Jasa</label>
                                            <input type="text" name="nama_item" class="form-control" value="<?= htmlspecialchars($row['nama_item']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Kategori Spesifik</label>
                                            <select name="kategori" id="editKategori<?= $row['id']; ?>" class="form-select" onchange="toggleEditOli(<?= $row['id']; ?>)" required>
                                                <option value="Oli" <?= $row['kategori']=='Oli'?'selected':''; ?>>Oli Mesin / Gardan</option>
                                                <option value="Ban" <?= $row['kategori']=='Ban'?'selected':''; ?>>Ban Motor</option>
                                                <option value="Sparepart" <?= $row['kategori']=='Sparepart'?'selected':''; ?>>Sparepart</option>
                                                <option value="Perawatan Motor" <?= $row['kategori']=='Perawatan Motor'?'selected':''; ?>>Perawatan Motor</option>
                                                <option value="Jasa" <?= $row['kategori']=='Jasa'?'selected':''; ?>>Hanya Jasa Layanan</option>
                                                <option value="Umum" <?= $row['kategori']=='Umum'?'selected':''; ?>>Lainnya (Umum)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Tipe Utama</label>
                                            <select name="tipe" class="form-select" required>
                                                <option value="barang" <?= $row['tipe']=='barang'?'selected':''; ?>>Barang (Fisik)</option>
                                                <option value="jasa" <?= $row['tipe']=='jasa'?'selected':''; ?>>Jasa Layanan</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Stok Tersedia</label>
                                            <input type="number" name="stok" class="form-control" value="<?= $row['stok']; ?>" min="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Harga Dasar (Rp)</label>
                                            <input type="number" name="harga" class="form-control" value="<?= $row['harga']; ?>" required min="0">
                                        </div>

                                        <div class="col-12 mt-3" id="editWrapOli<?= $row['id']; ?>" style="display: <?= $row['is_oli'] == 1 ? 'block' : 'none'; ?>;">
                                            <label class="form-label small fw-bold text-danger">Harga Jika Sekalian Pasang (Rp)</label>
                                            <input type="number" name="harga_pasang" class="form-control border-danger" value="<?= $row['harga_pasang']; ?>" min="0">
                                        </div>

                                        <div class="col-12 mt-4">
                                            <label class="form-label small fw-bold">Ubah Foto Produk</label>
                                            <div class="d-flex gap-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="aksi_gambar" id="editKeep<?= $row['id']; ?>" value="keep" checked onchange="toggleEditGambar(<?= $row['id']; ?>)">
                                                    <label class="form-check-label small" for="editKeep<?= $row['id']; ?>">Biarkan Foto Lama</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="aksi_gambar" id="editUpload<?= $row['id']; ?>" value="upload" onchange="toggleEditGambar(<?= $row['id']; ?>)">
                                                    <label class="form-check-label small" for="editUpload<?= $row['id']; ?>">Upload File Baru</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="aksi_gambar" id="editUrl<?= $row['id']; ?>" value="url" onchange="toggleEditGambar(<?= $row['id']; ?>)">
                                                    <label class="form-check-label small" for="editUrl<?= $row['id']; ?>">Ganti Link URL</label>
                                                </div>
                                            </div>
                                            
                                            <div id="editInputUpload<?= $row['id']; ?>" style="display: none;">
                                                <input type="file" name="gambar_file" class="form-control" accept="image/*">
                                            </div>
                                            <div id="editInputUrl<?= $row['id']; ?>" style="display: none;">
                                                <input type="url" name="gambar_url" class="form-control" placeholder="https://contoh.com/gambar_baru.jpg">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_item" class="btn btn-primary fw-bold"><i class="fa fa-save me-2"></i>Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted w-100">
                    <i class="fa fa-search-minus fa-3x mb-3 opacity-25"></i>
                    <h5>Produk atau jasa yang Anda cari tidak ditemukan.</h5>
                    <p class="small text-secondary">Coba periksa kembali kata kunci pencarian Anda atau pindah ke kategori lain.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL TAMBAH PRODUK -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--rb-dark-navy);">
                    <h5 class="modal-title font-serif fw-bold"><i class="fa fa-box-open text-warning me-2"></i>Tambah Data Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Nama Barang / Jasa</label>
                                <input type="text" name="nama_item" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kategori Spesifik</label>
                                <select name="kategori" id="tambahKategori" class="form-select" onchange="toggleTambahOli()" required>
                                    <option value="Oli">Oli Mesin / Gardan</option>
                                    <option value="Ban">Ban Motor</option>
                                    <option value="Sparepart">Sparepart</option>
                                    <option value="Perawatan Motor">Perawatan Motor</option>
                                    <option value="Jasa">Hanya Jasa Layanan</option>
                                    <option value="Umum" selected>Lainnya (Umum)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipe Utama</label>
                                <select name="tipe" id="tipeItem" class="form-select" required>
                                    <option value="barang">Barang (Fisik)</option>
                                    <option value="jasa">Jasa Layanan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Jumlah Stok</label>
                                <input type="number" name="stok" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Harga Dasar (Rp)</label>
                                <input type="number" name="harga" class="form-control" required min="0">
                            </div>

                            <div class="col-12 mt-2" id="tambahWrapOli" style="display: none;">
                                <label class="form-label small fw-bold text-danger">Harga Jika Sekalian Pasang (Rp)</label>
                                <input type="number" name="harga_pasang" class="form-control border-danger" value="0" min="0">
                                <small class="text-muted">Muncul otomatis karena Anda memilih kategori Oli.</small>
                            </div>

                            <div class="col-12 mt-4">
                                <label class="form-label small fw-bold">Foto Produk (Opsional)</label>
                                <div class="d-flex gap-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sumber_gambar" id="optUpload" value="upload" checked onchange="toggleTambahGambar()">
                                        <label class="form-check-label small" for="optUpload">Upload File Laptop</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sumber_gambar" id="optUrl" value="url" onchange="toggleTambahGambar()">
                                        <label class="form-check-label small" for="optUrl">Link URL (Internet)</label>
                                    </div>
                                </div>
                                <div id="inputUpload"><input type="file" name="gambar_file" class="form-control" accept="image/*"></div>
                                <div id="inputUrl" style="display: none;"><input type="url" name="gambar_url" class="form-control" placeholder="https://contoh.com/gambar_oli.jpg"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_item" class="btn btn-primary fw-bold"><i class="fa fa-save me-2"></i>Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT UTAMA -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SCRIPT SIDEBAR RESPONSIVE & FORM LOGIC -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function toggleTambahOli() {
            var kat = document.getElementById("tambahKategori").value;
            var wrap = document.getElementById("tambahWrapOli");
            var tipe = document.getElementById("tipeItem");
            
            if(kat === 'Oli') {
                wrap.style.display = 'block';
                tipe.value = 'barang'; 
            } else if (kat === 'Jasa') {
                wrap.style.display = 'none';
                tipe.value = 'jasa'; 
            } else {
                wrap.style.display = 'none';
            }
        }

        function toggleTambahGambar() {
            var isUpload = document.getElementById("optUpload").checked;
            document.getElementById("inputUpload").style.display = isUpload ? 'block' : 'none';
            document.getElementById("inputUrl").style.display = isUpload ? 'none' : 'block';
        }

        function toggleEditOli(id) {
            var kat = document.getElementById("editKategori" + id).value;
            var wrap = document.getElementById("editWrapOli" + id);
            wrap.style.display = (kat === 'Oli') ? 'block' : 'none';
        }

        function toggleEditGambar(id) {
            var optUpload = document.getElementById("editUpload" + id).checked;
            var optUrl = document.getElementById("editUrl" + id).checked;
            
            document.getElementById("editInputUpload" + id).style.display = optUpload ? 'block' : 'none';
            document.getElementById("editInputUrl" + id).style.display = optUrl ? 'block' : 'none';
        }
    </script>
</body>
</html>