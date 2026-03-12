<?php
$pageTitle = 'Kelola Anggota – GBN Arisan';
$activeMenu = 'anggota';
$rootPath = '';
$assetBase = '';

include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

// EDIT Anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
  $eid = (int) $_POST['edit_id'];
  $nama = trim($_POST['nama']);
  $no_hp = trim($_POST['no_hp']);
  $alamat = trim($_POST['alamat']);

  $stmt = mysqli_prepare($koneksi, "UPDATE anggota SET nama=?, no_hp=?, alamat=? WHERE id=?");
  mysqli_stmt_bind_param($stmt, "sssi", $nama, $no_hp, $alamat, $eid);

  if (mysqli_stmt_execute($stmt)) {
    $_SESSION['swal_success'] = "Data anggota berhasil diperbarui.";
  } else {
    $_SESSION['swal_error'] = "Gagal memperbarui data.";
  }
  header("Location: anggota.php");
  exit;
}

// Pagination & Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "";
if (!empty($search)) {
  $where = "WHERE nama LIKE '%$search%' OR no_hp LIKE '%$search%' OR alamat LIKE '%$search%'";
}

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) t FROM anggota $where");
$total_rows = mysqli_fetch_assoc($q_count)['t'];
$total_pages = max(1, ceil($total_rows / $per_page));

$q = mysqli_query($koneksi, "SELECT * FROM anggota $where ORDER BY nama ASC LIMIT $per_page OFFSET $offset");

include 'partials/header.php';
?>

<body class="app-wrapper">
  <?php include 'partials/navbar.php'; ?>

  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h1>Kelola Anggota</h1>
      <p>Total <?= $total_rows ?> anggota terdaftar</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-circle"></i> Tambah Anggota
    </button>
  </div>

  <!-- Search -->
  <div class="card mb-3">
    <div class="card-body" style="padding:12px">
      <form method="get" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm"
          placeholder="Cari nama, no hp, atau alamat..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
          <a href="anggota.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body-tight">
      <div class="table-container" style="border:none">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama</th>
              <th>No. HP</th>
              <th>Alamat</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = $offset + 1;
            while ($a = mysqli_fetch_assoc($q)): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($a['nama']) ?></strong></td>
                <td>
                  <a href="https://wa.me/<?= preg_replace('/^0/', '62', $a['no_hp']) ?>" target="_blank"
                    class="text-decoration-none">
                    <?= htmlspecialchars($a['no_hp']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($a['alamat']) ?></td>
                <td>
                  <span class="badge <?= $a['status'] === 'LUNAS' ? 'badge-success' : 'badge-warning' ?>">
                    <?= $a['status'] ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                      data-bs-target="#editModal<?= $a['id'] ?>" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <a href="anggota_hapus.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                      onclick="return confirm('Hapus anggota <?= htmlspecialchars($a['nama']) ?>? Semua data transaksi terkait juga akan dihapus.')">
                      <i class="bi bi-trash3"></i>
                    </a>
                  </div>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editModal<?= $a['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Anggota</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="anggota.php">
                      <div class="modal-body">
                        <input type="hidden" name="edit_id" value="<?= $a['id'] ?>">
                        <div class="form-group">
                          <label class="form-label">Nama</label>
                          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($a['nama']) ?>"
                            required>
                        </div>
                        <div class="form-group">
                          <label class="form-label">No. HP</label>
                          <input type="text" name="no_hp" class="form-control"
                            value="<?= htmlspecialchars($a['no_hp']) ?>" required>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Alamat</label>
                          <input type="text" name="alamat" class="form-control"
                            value="<?= htmlspecialchars($a['alamat']) ?>" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="card-body d-flex justify-content-center">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">‹</a>
              </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">›</a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>

  <!-- Add Modal -->
  <div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus-fill"></i> Tambah Anggota Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="anggota_simpan.php">
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" placeholder="Nama anggota" required>
            </div>
            <div class="form-group">
              <label class="form-label">No. HP</label>
              <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxx" required>
            </div>
            <div class="form-group">
              <label class="form-label">Alamat</label>
              <input type="text" name="alamat" class="form-control" placeholder="Alamat lengkap" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include 'partials/footer.php'; ?>