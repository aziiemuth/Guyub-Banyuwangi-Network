<?php
$pageTitle = 'Catatan Keuangan – GBN Arisan';
$activeMenu = 'keuangan';
$rootPath = '';

include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

// Summary Calculations
// 1. Total Pemasukan (Pembayaran Lunas & Valid)
$q_pemasukan = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM pembayaran WHERE is_valid = 1");
$total_pemasukan = mysqli_fetch_assoc($q_pemasukan)['total'] ?? 0;

// 2. Total Pengeluaran
$q_pengeluaran = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM pengeluaran");
$total_pengeluaran = mysqli_fetch_assoc($q_pengeluaran)['total'] ?? 0;

// 3. Saldo
$saldo = $total_pemasukan - $total_pengeluaran;

// Unified Transaction History (Paginated)
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$union_sql = "
    (SELECT 'pemasukan' as tipe, id, nominal, tanggal, CONCAT('Pembayaran: ', (SELECT nama FROM anggota WHERE id=pembayaran.anggota_id)) as keterangan, NULL as kategori FROM pembayaran WHERE is_valid = 1)
    UNION ALL
    (SELECT 'pengeluaran' as tipe, id, nominal, tanggal, keterangan, (SELECT nama_kategori FROM kategori WHERE id=pengeluaran.kategori_id) as kategori FROM pengeluaran)
    ORDER BY tanggal DESC, id DESC
";

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) t FROM ($union_sql) as combined");
$total_rows = mysqli_fetch_assoc($q_count)['t'];
$total_pages = max(1, ceil($total_rows / $per_page));

$q_transactions = mysqli_query($koneksi, $union_sql . " LIMIT $per_page OFFSET $offset");

$categories = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1>Catatan Keuangan</h1>
            <p>Rekapitulasi pemasukan dan pengeluaran</p>
        </div>
        <div class="d-flex gap-2">
            <a href="kategori.php" class="btn btn-outline-primary">
                <i class="bi bi-tags"></i> Kelola Kategori
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="bi bi-dash-circle"></i> Tambah Pengeluaran
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-white-50 mb-0">Total Pemasukan</h6>
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <h3 class="mb-0">Rp
                        <?= number_format($total_pemasukan, 0, ',', '.') ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-danger text-white shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-white-50 mb-0">Total Pengeluaran</h6>
                        <i class="bi bi-graph-down-arrow fs-4"></i>
                    </div>
                    <h3 class="mb-0">Rp
                        <?= number_format($total_pengeluaran, 0, ',', '.') ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-white-50 mb-0">Saldo Saat Ini</h6>
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <h3 class="mb-0">Rp
                        <?= number_format($saldo, 0, ',', '.') ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="card">
        <div class="card-body-tight">
            <div class="table-container" style="border:none">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="120">Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th class="text-end">Nominal</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($q_transactions) > 0):
                            while ($t = mysqli_fetch_assoc($q_transactions)): ?>
                                <tr>
                                    <td>
                                        <?= date('d/m/Y', strtotime($t['tanggal'])) ?>
                                    </td>
                                    <td><strong>
                                            <?= htmlspecialchars($t['keterangan']) ?>
                                        </strong></td>
                                    <td><span class="text-muted">
                                            <?= htmlspecialchars($t['kategori'] ?? '-') ?>
                                        </span></td>
                                    <td>
                                        <span
                                            class="badge <?= $t['tipe'] === 'pemasukan' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($t['tipe']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end <?= $t['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <strong>
                                            <?= $t['tipe'] === 'pemasukan' ? '+' : '-' ?> Rp
                                            <?= number_format($t['nominal'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['tipe'] === 'pengeluaran'): ?>
                                            <a href="pengeluaran_aksi.php?aksi=hapus&id=<?= $t['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Hapus pengeluaran ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"
                                                title="Pembayaran anggota tidak dapat dihapus dari sini">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Belum ada transaksi.</td>
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

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-dash-circle"></i> Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="pengeluaran_aksi.php?aksi=tambah">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php mysqli_data_seek($categories, 0);
                                while ($c = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['nama_kategori']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Nominal (Rp)</label>
                            <input type="number" name="nominal" class="form-control" placeholder="0" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"
                                placeholder="Contoh: Beli snack rapat" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan
                            Pengeluaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>