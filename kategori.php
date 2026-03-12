<?php
$pageTitle = 'Kategori Transaksi – GBN Arisan';
$activeMenu = 'kategori';
$rootPath = '';

include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) t FROM kategori");
$total_rows = mysqli_fetch_assoc($q_count)['t'];
$total_pages = max(1, ceil($total_rows / $per_page));

$q = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC LIMIT $per_page OFFSET $offset");

include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1>Kategori Transaksi</h1>
            <p>Kelola kategori untuk pengeluaran</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
        </button>
    </div>

    <div class="card">
        <div class="card-body-tight">
            <div class="table-container" style="border:none">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Nama Kategori</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = $offset + 1;
                        if (mysqli_num_rows($q) > 0):
                            while ($k = mysqli_fetch_assoc($q)): ?>
                                <tr>
                                    <td>
                                        <?= $no++ ?>
                                    </td>
                                    <td><strong>
                                            <?= htmlspecialchars($k['nama_kategori']) ?>
                                        </strong></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $k['id'] ?>" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="kategori_aksi.php?aksi=hapus&id=<?= $k['id'] ?>"
                                                class="btn btn-sm btn-outline-danger" title="Hapus"
                                                onclick="return confirm('Hapus kategori <?= htmlspecialchars($k['nama_kategori']) ?>?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $k['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Kategori</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="kategori_aksi.php?aksi=edit">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <div class="form-group">
                                                        <label class="form-label">Nama Kategori</label>
                                                        <input type="text" name="nama_kategori" class="form-control"
                                                            value="<?= htmlspecialchars($k['nama_kategori']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i>
                                                        Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">Belum ada kategori.</td>
                            </tr>
                        <?php endif; ?>
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
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">‹</a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">›</a></li>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="kategori_aksi.php?aksi=tambah">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control"
                                placeholder="Contoh: Konsumsi, Sewa Ruangan, dll" required>
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