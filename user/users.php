<?php
include '../auth/auth_check.php';
include '../config/koneksi.php';
if ($_SESSION['user']['role'] != 'admin') exit;

$pageTitle  = 'User Management – GBN Arisan';
$activeMenu = 'users';
$rootPath   = '../';
$assetBase  = '../';
include '../partials/header.php';
?>
<body class="app-wrapper">
<?php include '../partials/navbar.php'; ?>

<div class="page-header">
  <h1><i class="bi bi-person-badge-fill"></i> User Management</h1>
  <p>Daftar akun pengguna sistem GBN Arisan.</p>
</div>

<div class="card mb-3">
  <div class="card-header flex-between">
    <span>Daftar Akun Pengguna</span>
    <a href="user_add.php" class="btn btn-primary btn-sm">
      <i class="bi bi-person-plus-fill"></i> Tambah User
    </a>
  </div>
  <div class="card-body-tight">
    <div class="table-container" style="border:none;border-radius:0 0 var(--radius) var(--radius)">
      <table class="data-table">
        <thead><tr>
          <th>#</th>
          <th>Username</th>
          <th>Role</th>
          <th>Keterkaitan Anggota</th>
          <th>Aksi</th>
        </tr></thead>
        <tbody>
          <?php
          $q = mysqli_query($koneksi,"SELECT u.*, a.nama as nama_anggota FROM users u LEFT JOIN anggota a ON u.anggota_id=a.id ORDER BY u.username");
          $no = 1;
          while ($u = mysqli_fetch_assoc($q)):
          ?>
          <tr>
            <td class="text-muted text-sm"><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td>
              <span class="badge <?= $u['role']==='admin' ? 'badge-danger' : 'badge-success' ?>">
                <i class="bi <?= $u['role']==='admin' ? 'bi-shield-fill' : 'bi-person-fill' ?>"></i>
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td>
              <?php if ($u['nama_anggota']): ?>
                <span class="badge badge-primary"><?= htmlspecialchars($u['nama_anggota']) ?></span>
              <?php else: ?>
                <span class="text-muted text-sm">— Tidak dikaitkan</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['username'] !== 'admin'): ?>
                <button class="btn btn-danger btn-sm"
                  onclick="if(confirm('Hapus user <?= htmlspecialchars(addslashes($u['username'])) ?>?')){window.location.href='user_delete.php?id=<?= $u['id'] ?>';}">
                  <i class="bi bi-trash3-fill"></i> Hapus
                </button>
              <?php else: ?>
                <span class="text-muted text-sm">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<a href="../dashboard.php" class="btn btn-outline btn-sm">
  <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
</a>

<?php include '../partials/footer.php'; ?>
