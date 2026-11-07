<?php
// profil.php
session_start();
require 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan = '';

// Jika tombol Simpan ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password_baru = trim($_POST['password_baru']);
    
    // Cek apakah username sudah dipakai orang lain
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $user_id");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "<div class='alert alert-danger'>Username sudah digunakan orang lain.</div>";
    } else {
        // Jika input password baru TIDAK KOSONG
        if (!empty($password_baru)) {
            $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            $query = "UPDATE users SET 
                        nama_lengkap = '$nama_lengkap', 
                        username = '$username', 
                        email = '$email',
                        no_telp = '$no_telp',
                        password = '$password_hashed' 
                      WHERE id = $user_id";
        } else {
            // Jika kosong, update biodata saja tanpa ubah password
            $query = "UPDATE users SET 
                        nama_lengkap = '$nama_lengkap', 
                        username = '$username',
                        email = '$email',
                        no_telp = '$no_telp'
                      WHERE id = $user_id";
        }
        
        if (mysqli_query($conn, $query)) {
            // Update session agar nama di pojok kanan atas langsung berubah
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $pesan = "<div class='alert alert-success'><i class='fa fa-check-circle me-2'></i> Profil berhasil diperbarui!</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Terjadi kesalahan sistem: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Ambil data user saat ini dari database untuk ditampilkan di form
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query_user);

include 'includes/header.php';
?>

<div class="container my-5" style="min-height: 60vh;">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fa fa-user-circle fa-5x text-muted mb-3"></i>
                        <h3 class="font-serif fw-bold text-dark">Profil Saya</h3>
                        <p class="text-muted small">Update informasi akun Anda di sini.</p>
                        <span class="badge bg-primary px-3 py-2 text-uppercase fw-bold"><?= $user['role']; ?></span>
                    </div>

                    <?= $pesan; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control py-2" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Username</label>
                            <input type="text" name="username" class="form-control py-2" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <!-- INPUT BARU: EMAIL & NO TELP -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">Email</label>
                                <input type="email" name="email" class="form-control py-2" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" placeholder="contoh@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted">No. Telepon / WA</label>
                                <input type="text" name="no_telp" class="form-control py-2" value="<?= htmlspecialchars($user['no_telp'] ?? ''); ?>" placeholder="0812xxxxxx">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted">Ganti Password (Opsional)</label>
                            <input type="password" name="password_baru" class="form-control py-2" placeholder="Kosongkan jika tidak ingin ganti password">
                        </div>
                        
                        <button type="submit" class="btn btn-rb-red w-100 py-2 fw-bold rounded-pill shadow-sm">
                            <i class="fa fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>