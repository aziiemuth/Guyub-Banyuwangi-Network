<?php
// Edit now handled via modal in anggota.php
// Keep as fallback for backward compatibility
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT * FROM anggota WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$a) {
  $_SESSION['swal_error'] = 'Anggota tidak ditemukan.';
  header("Location: anggota.php");
  exit;
}

if (isset($_POST['update'])) {
  $nama = trim($_POST['nama']);
  $no_hp = trim($_POST['no_hp']);
  $alamat = trim($_POST['alamat']);

  $stmt2 = mysqli_prepare($koneksi, "UPDATE anggota SET nama=?, no_hp=?, alamat=? WHERE id=?");
  mysqli_stmt_bind_param($stmt2, "sssi", $nama, $no_hp, $alamat, $id);

  if (mysqli_stmt_execute($stmt2)) {
    $_SESSION['swal_success'] = "Data anggota berhasil diperbarui.";
  } else {
    $_SESSION['swal_error'] = "Gagal memperbarui data.";
  }
  header("Location: anggota.php");
  exit;
}

$pageTitle = 'Edit Anggota – GBN Arisan';
$activeMenu = 'anggota';
$rootPath = '';
$assetBase = '';
include 'partials/header.php';
?>

<body class="app-wrapper">
  <?php include 'partials/navbar.php'; ?>

  <div class="page-header">
    <h1><i class="bi bi-pencil-square"></i> Edit Anggota</h1>
    <p>Perbarui data anggota: <strong><?= htmlspecialchars($a['nama']) ?></strong></p>
  </div>

  <div class="card" style="max-width:520px">
    <div class="card-header">Data Anggota</div>
    <div class="card-body">
      <form method="post">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($a['nama']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP / WhatsApp</label>
          <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($a['no_hp']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" class="form-control" rows="3"
            required><?= htmlspecialchars($a['alamat']) ?></textarea>
        </div>
        <div class="flex-gap mt-2">
          <button name="update" class="btn btn-warning">
            <i class="bi bi-floppy-fill"></i> Simpan Perubahan
          </button>
          <a href="anggota.php" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </form>
    </div>
  </div>

  <?php include 'partials/footer.php'; ?>