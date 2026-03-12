<?php
include '../auth/auth_check.php';
cek_admin();
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $r = $_POST['role'] === 'admin' ? 'admin' : 'user';
  $aid = !empty($_POST['anggota_id']) ? (int) $_POST['anggota_id'] : null;

  $stmt = mysqli_prepare($koneksi, "INSERT INTO users(username, password, role, anggota_id) VALUES(?, ?, ?, ?)");
  mysqli_stmt_bind_param($stmt, "sssi", $u, $p, $r, $aid);

  if (mysqli_stmt_execute($stmt)) {
    $_SESSION['swal_success'] = "User $u berhasil ditambahkan.";
  } else {
    $_SESSION['swal_error'] = "Gagal menambahkan user.";
  }
  header("Location: users.php");
  exit;
}

$q_anggota = mysqli_query($koneksi, "SELECT id, nama FROM anggota ORDER BY nama ASC");

$pageTitle = 'Tambah User – GBN Arisan';
$activeMenu = 'users';
$rootPath = '../';
$assetBase = '../';
include '../partials/header.php';
?>

<body class="app-wrapper">
  <?php include '../partials/navbar.php'; ?>

  <div class="page-header">
    <h1><i class="bi bi-person-plus-fill"></i> Tambah User Baru</h1>
    <p>Buat akun pengguna baru untuk sistem.</p>
  </div>

  <div class="card" style="max-width:480px">
    <div class="card-header">Informasi Akun</div>
    <div class="card-body">
      <form method="post">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" placeholder="Contoh: budi_santoso" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" required>
            <option value="user">User (Anggota)</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Kaitkan ke Anggota <span class="text-muted text-sm">(khusus Role
              User)</span></label>
          <select class="form-select" name="anggota_id">
            <option value="">-- Tidak dikaitkan --</option>
            <?php while ($ang = mysqli_fetch_assoc($q_anggota)): ?>
              <option value="<?= $ang['id'] ?>"><?= htmlspecialchars($ang['nama']) ?></option>
            <?php endwhile; ?>
          </select>
          <div class="text-muted text-sm mt-1">Jika role Admin, biarkan kosong.</div>
        </div>
        <div class="flex-gap mt-2">
          <button class="btn btn-primary">
            <i class="bi bi-floppy-fill"></i> Simpan User
          </button>
          <a href="users.php" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Batal
          </a>
        </div>
      </form>
    </div>
  </div>

  <?php include '../partials/footer.php'; ?>