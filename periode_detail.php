<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = mysqli_prepare($koneksi, "SELECT * FROM periode WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$periode = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$periode) {
    header("Location: periode.php");
    exit;
}

// ACTION: Hapus Tarikan
if (isset($_GET['hapus_tarik'])) {
    $tarik_id = (int) $_GET['hapus_tarik'];
    $stmt_del = mysqli_prepare($koneksi, "DELETE FROM penarikan_arisan WHERE id=? AND periode_id=?");
    mysqli_stmt_bind_param($stmt_del, "ii", $tarik_id, $id);
    if (mysqli_stmt_execute($stmt_del)) {
        $_SESSION['swal_success'] = "Data penerima arisan berhasil dihapus.";
    } else {
        $_SESSION['swal_error'] = "Gagal menghapus data.";
    }
    header("Location: periode_detail.php?id=$id");
    exit;
}

// ACTION: Tambah Tarikan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tarikan'])) {
    $anggota_id = (int) $_POST['anggota_id'];
    $tanggal_tarik = date('Y-m-d');

    // Cek duplikasi
    $cek = mysqli_prepare($koneksi, "SELECT id FROM penarikan_arisan WHERE periode_id=? AND anggota_id=?");
    mysqli_stmt_bind_param($cek, "ii", $id, $anggota_id);
    mysqli_stmt_execute($cek);

    if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
        $_SESSION['swal_error'] = "Anggota tersebut sudah ditambahkan sebagai penerima pada periode ini.";
    } else {
        $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO penarikan_arisan (periode_id, anggota_id, tanggal_tarik) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "iis", $id, $anggota_id, $tanggal_tarik);
        if (mysqli_stmt_execute($stmt_ins)) {
            $_SESSION['swal_success'] = "Penerima arisan berhasil ditambahkan.";
        } else {
            $_SESSION['swal_error'] = "Gagal menambahkan penerima.";
        }
    }
    header("Location: periode_detail.php?id=$id");
    exit;
}

$pageTitle = 'Detail Periode – GBN Arisan';
$activeMenu = 'periode';
$rootPath = '';
$assetBase = '';
include 'partials/header.php';

// Ambil data penarikan
$q_tarik = mysqli_query($koneksi, "
    SELECT pa.id, pa.tanggal_tarik, a.nama 
    FROM penarikan_arisan pa 
    JOIN anggota a ON a.id = pa.anggota_id 
    WHERE pa.periode_id = $id
");

// Ambil anggota untuk dropdown tambah (hanya yang belum jadi pemenang di periode ini)
$q_anggota = mysqli_query($koneksi, "
    SELECT id, nama FROM anggota 
    WHERE id NOT IN (SELECT anggota_id FROM penarikan_arisan WHERE periode_id=$id) 
    ORDER BY nama ASC
");
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div style="font-size: 28px; color: #0ea5e9;">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <div>
                <h1 class="mb-1" style="font-size: 22px; font-weight: 700;">Detail
                    <?= htmlspecialchars($periode['nama_periode']) ?>
                </h1>
                <p class="mb-0 text-muted" style="font-size: 14px;">
                    Mulai:
                    <?= date('d M Y', strtotime($periode['tanggal_mulai'])) ?> |
                    Selesai:
                    <?= date('d M Y', strtotime($periode['tanggal_selesai'])) ?> |
                    Status:
                    <span
                        class="badge <?= $periode['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?> px-2 rounded-pill">
                        <?= strtoupper($periode['status']) ?>
                    </span>
                </p>
            </div>
        </div>
        <a href="periode.php" class="btn btn-light border bg-white rounded-pill px-3 shadow-sm"
            style="font-size: 14px; color: #4b5563;">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        <!-- Kiri: Tambah Penerima -->
        <div class="col-md-5">
            <div class="card h-100"
                style="border: 2px solid #3b82f6; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2">Tambah Penerima Arisan</h5>
                    <p class="text-muted small mb-4">Tentukan siapa yang menarik arisan pada periode ini.</p>

                    <form method="post">
                        <input type="hidden" name="tambah_tarikan" value="1">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Pilih Anggota</label>
                            <select name="anggota_id" class="form-select border-0 shadow-sm"
                                style="background-color: #f9fafb;" required>
                                <option value="">-- Pilih Anggota --</option>
                                <?php while ($ang = mysqli_fetch_assoc($q_anggota)): ?>
                                    <option value="<?= $ang['id'] ?>">
                                        <?= htmlspecialchars($ang['nama']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold"
                            style="background-color: #10b981; border: none; border-radius: 8px;">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Penarikan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kanan: Daftar Penerima -->
        <div class="col-md-7">
            <div class="card h-100"
                style="border: 2px solid #0ea5e9; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Daftar Penerima Arisan (
                        <?= htmlspecialchars($periode['nama_periode']) ?>)
                    </h5>

                    <div class="table-responsive">
                        <table class="table text-center align-middle" style="font-size: 14px;">
                            <thead>
                                <tr style="background-color: #f8fafc; color: #64748b;">
                                    <th class="fw-bold py-3 border-bottom-0">NO</th>
                                    <th class="fw-bold py-3 border-bottom-0">NAMA ANGGOTA</th>
                                    <th class="fw-bold py-3 border-bottom-0">TANGGAL DITARIK</th>
                                    <th class="fw-bold py-3 border-bottom-0">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($q_tarik) > 0): ?>
                                    <?php $no = 1;
                                    while ($t = mysqli_fetch_assoc($q_tarik)): ?>
                                        <tr>
                                            <td class="py-3">
                                                <?= $no++ ?>
                                            </td>
                                            <td class="py-3 fw-bold text-dark">
                                                <?= htmlspecialchars($t['nama']) ?>
                                            </td>
                                            <td class="py-3">
                                                <?= date('d M Y', strtotime($t['tanggal_tarik'])) ?>
                                            </td>
                                            <td class="py-3">
                                                <a href="?id=<?= $id ?>&hapus_tarik=<?= $t['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Hapus penerima ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-4 text-muted">Belum ada penerima ditarik pada periode ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>