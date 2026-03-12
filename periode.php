<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

// ACTION: Hapus Periode
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    // Cek apakah ada pembayaran di periode ini
    $cek_bayar = mysqli_query($koneksi, "SELECT id FROM pembayaran WHERE periode_id=$id LIMIT 1");
    if (mysqli_num_rows($cek_bayar) > 0) {
        $_SESSION['swal_error'] = "Gagal: Periode ini masih memiliki data pembayaran tersimpan.";
    } else {
        mysqli_query($koneksi, "DELETE FROM penarikan_arisan WHERE periode_id=$id");
        if (mysqli_query($koneksi, "DELETE FROM periode WHERE id=$id")) {
            $_SESSION['swal_success'] = "Periode berhasil dihapus.";
        } else {
            $_SESSION['swal_error'] = "Gagal menghapus periode.";
        }
    }
    header("Location: periode.php");
    exit;
}

// ACTION: Tambah / Edit Periode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_periode']);
    $mulai = $_POST['tanggal_mulai'];
    $selesai = $_POST['tanggal_selesai'];
    $status = $_POST['status'];

    // Pastikan jika ada yang diset aktif, auto matiin yang lain jika diset aktif
    if ($status === 'aktif') {
        mysqli_query($koneksi, "UPDATE periode SET status='selesai' WHERE status='aktif'");
    }

    if (!empty($_POST['edit_id'])) {
        $edit_id = (int) $_POST['edit_id'];
        $stmt = mysqli_prepare($koneksi, "UPDATE periode SET nama_periode=?, tanggal_mulai=?, tanggal_selesai=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $mulai, $selesai, $status, $edit_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = "Periode berhasil diperbarui.";
        } else {
            $_SESSION['swal_error'] = "Gagal memperbarui periode.";
        }
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $nama, $mulai, $selesai, $status);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = "Periode baru berhasil ditambahkan.";
        } else {
            $_SESSION['swal_error'] = "Gagal menambahkan periode.";
        }
    }
    header("Location: periode.php");
    exit;
}

$pageTitle = 'Kelola Periode Arisan – GBN Arisan';
$activeMenu = 'periode';
$rootPath = '';
$assetBase = '';
include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-calendar3"></i> Kelola Periode Arisan</h1>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="bi bi-plus-lg"></i> Tambah Periode
        </button>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4"
        style="background-color: #e0f8f8; border: none; border-left: 4px solid #00cccc; color: #1e5c5c;">
        <div>
            <i class="bi bi-info-circle-fill fs-4"></i>
        </div>
        <div>
            <div class="fw-bold mb-1">Sistem Otomatis:</div>
            <div style="font-size: 14px; opacity: 0.9;">
                Periode yang sudah melewati tanggal selesai akan ditutup dan periode baru akan otomatis dibuatkan
                (siklus 2 minggu). Anda dapat menambahkan, mengedit, atau menghapus data secara manual jika terdapat
                kekeliruan.
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body-tight">
            <div class="table-container" style="border:none">
                <table class="data-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-muted border-bottom-0">NO</th>
                            <th class="py-3 px-4 text-muted border-bottom-0">NAMA PERIODE</th>
                            <th class="py-3 px-4 text-muted border-bottom-0">TANGGAL MULAI</th>
                            <th class="py-3 px-4 text-muted border-bottom-0">TANGGAL SELESAI</th>
                            <th class="py-3 px-4 text-muted border-bottom-0">STATUS</th>
                            <th class="py-3 px-4 text-muted border-bottom-0 text-center">TARIK ARISAN</th>
                            <th class="py-3 px-4 text-muted border-bottom-0">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($koneksi, "
            SELECT p.*, (SELECT COUNT(id) FROM penarikan_arisan WHERE periode_id=p.id) as jml_tarik 
            FROM periode p 
            ORDER BY p.id DESC
          ");
                        $no = 1;
                        while ($p = mysqli_fetch_assoc($q)):
                            ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <?= $no++ ?>
                                </td>
                                <td class="px-4 py-3 fw-bold text-dark">
                                    <?= htmlspecialchars($p['nama_periode']) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($p['status'] === 'aktif'): ?>
                                        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-check-lg"></i>
                                            AKTIF</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">SELESAI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                                        <?= $p['jml_tarik'] ?> Orang
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="d-flex gap-2">
                                        <a href="periode_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-success px-3"
                                            style="background-color: #10b981; border: none;">
                                            <i class="bi bi-eye-fill"></i> Detail
                                        </a>
                                        <button class="btn btn-sm btn-warning px-3" style="color: #fff;"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id'] ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger px-3"
                                            onclick="return confirm('Hapus periode ini?')">
                                            <i class="bi bi-trash-fill"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="modalEdit<?= $p['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Periode</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
                                                <div class="form-group mb-3">
                                                    <label class="form-label">Nama Periode</label>
                                                    <input type="text" name="nama_periode" class="form-control"
                                                        value="<?= htmlspecialchars($p['nama_periode']) ?>" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label">Tgl. Mulai</label>
                                                        <input type="date" name="tanggal_mulai" class="form-control"
                                                            value="<?= $p['tanggal_mulai'] ?>" required>
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label">Tgl. Selesai</label>
                                                        <input type="date" name="tanggal_selesai" class="form-control"
                                                            value="<?= $p['tanggal_selesai'] ?>" required>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="aktif" <?= $p['status'] === 'aktif' ? 'selected' : '' ?>
                                                            >Aktif</option>
                                                        <option value="selesai" <?= $p['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning text-white"><i
                                                        class="bi bi-floppy-fill"></i> Simpan Perubahan</button>
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
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-lg"></i> Tambah Periode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Periode</label>
                            <input type="text" name="nama_periode" class="form-control"
                                placeholder="Contoh: Periode Awal 07 Mar 2026" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Tgl. Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Tgl. Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control"
                                    value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="selesai">Selesai</option>
                            </select>
                            <small class="text-muted d-block mt-1">Jika diset 'Aktif', maka periode aktif sebelumnya
                                akan otomatis ditandai sebagai 'Selesai'.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>