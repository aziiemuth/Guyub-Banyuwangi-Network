<?php
include '../auth/auth_check.php';
cek_admin();
include '../config/koneksi.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = (int) $_POST['user_id'];
  $new_pass = $_POST['password'];

  if (strlen($new_pass) < 6) {
    $msg = "Password minimal 6 karakter.";
    $msgType = 'danger';
  } else {
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $hash, $user_id);

    if (mysqli_stmt_execute($stmt)) {
      $msg = "Password berhasil di-reset!";
      $msgType = 'success';
    } else {
      $msg = "Gagal mereset password.";
      $msgType = 'danger';
    }
  }
}

$users = mysqli_query($koneksi, "SELECT id, username, role FROM users ORDER BY username");

$pageTitle = 'Reset Password – GBN Arisan';
$activeMenu = 'users';
$rootPath = '../';
$assetBase = '../';
include '../partials/header.php';
?>

<body class="app-wrapper">
  <?php include '../partials/navbar.php'; ?>

  <div class="page-header">
    <h1><i class="bi bi-key-fill"></i> Reset Password User</h1>
    <p>Atur ulang kata sandi pengguna sistem.</p>
  </div>

  <div class="card" style="max-width:460px">
    <div class="card-header">Form Reset Password</div>
    <div class="card-body">
      <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> mb-3">
          <i class="bi <?= $msgType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label class="form-label">Pilih User</label>
          <select name="user_id" class="form-select" required>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
              <option value="<?= $u['id'] ?>">
                <?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password Baru</label>
          <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="flex-gap mt-2">
          <button class="btn btn-danger">
            <i class="bi bi-arrow-repeat"></i> Reset Password
          </button>
          <a href="users.php" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </form>
    </div>
  </div>

  <?php include '../partials/footer.php'; ?>