<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Menghitung total item di keranjang untuk ditampilkan di Badge Header
$cart_count = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $qty) {
        $cart_count += $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fino Motor - Cuci, Oli, & Ban</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --rb-dark-navy: #121F45;
            --rb-navy: #223971;
            --rb-red: #CC1E4A;
            --rb-yellow: #FFC906;
            --rb-light: #F8F9FA;
        }

        body {
            background-color: var(--rb-light);
            padding-top: 135px; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }

        h1, h2, h3, h4, h5, h6, .font-serif {
            font-family: 'Playfair Display', serif;
            letter-spacing: 0.5px;
        }

        .navbar-custom {
            background: rgba(12, 31, 69, 0.85) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--rb-yellow);
            transition: all 0.3s ease;
            padding-top: 0 !important; 
            padding-bottom: 0 !important;
        }

        .navbar-brand img {
            max-height: 130px; 
            transition: all 0.3s ease;
        }

        .nav-link {
            font-family: 'Playfair Display', serif; 
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 900 !important; 
            font-size: 1.35rem; 
            margin-right: 20px; 
            letter-spacing: 1px;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--rb-yellow) !important;
        }

        .btn-rb-red {
            background-color: var(--rb-red);
            color: white;
            border: none;
            font-weight: 700; 
            font-size: 1.05rem; 
        }

        .btn-rb-red:hover {
            background-color: #b0163e;
            color: white;
        }

        .btn-outline-rb {
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5); 
            font-weight: 700; 
            font-size: 1.05rem; 
        }

        .btn-outline-rb:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--rb-yellow);
            border-color: var(--rb-yellow);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container-fluid px-4 px-lg-5">
        
        <a class="navbar-brand d-flex align-items-center me-4 py-0" href="/Fino_Motor/index.php">
            <img src="/Fino_Motor/assets/logo_text.png" alt="Fino Motor Logo" class="my-1">
        </a>
        
        <button class="navbar-expand-lg navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/Fino_Motor/index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Fino_Motor/katalog.php">Produk & Layanan</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['role'])): ?>
                    
                    <?php if ($_SESSION['role'] === 'pelanggan'): ?>
                        <a href="/Fino_Motor/pelanggan/keranjang.php" class="text-white text-decoration-none position-relative me-3">
                            <i class="fa fa-shopping-cart fs-4"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" style="font-size: 0.7rem;">
                                    <?= $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <a class="text-white text-decoration-none fw-bold dropdown-toggle fs-5 d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-user-circle text-warning fs-4 me-2"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 rounded-3">
                            <li><a class="dropdown-item fw-semibold py-2" href="/Fino_Motor/profil.php"><i class="fa fa-id-card me-2 text-primary"></i>Profil Saya</a></li>
                            
                            <?php if ($_SESSION['role'] === 'pelanggan'): ?>
                                <li><a class="dropdown-item fw-semibold py-2" href="/Fino_Motor/pelanggan/riwayat.php"><i class="fa fa-history me-2 text-warning"></i>Riwayat Booking</a></li>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['role'] === 'owner'): ?>
                                <li><a class="dropdown-item fw-semibold py-2" href="/Fino_Motor/owner/dashboard.php"><i class="fa fa-chart-line me-2 text-success"></i>Dashboard Owner</a></li>
                            <?php elseif ($_SESSION['role'] === 'karyawan'): ?>
                                <li><a class="dropdown-item fw-semibold py-2" href="/Fino_Motor/karyawan/kasir.php"><i class="fa fa-cash-register me-2 text-success"></i>Mesin Kasir</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-bold py-2" href="/Fino_Motor/auth/logout.php"><i class="fa fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <a href="/Fino_Motor/auth/login.php" class="btn btn-outline-rb px-4 py-2 rounded-3">Login</a>
                    <a href="/Fino_Motor/auth/register.php" class="btn btn-rb-red px-4 py-2 rounded-3">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>