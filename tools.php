<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

// ACTION: Hapus Semua Data (Kecuali Admin/Users)
if (isset($_POST['reset_all_data'])) {
    if ($_POST['konfirmasi'] === 'KONFIRMASI RESET') {
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0;");
        mysqli_query($koneksi, "TRUNCATE TABLE pembayaran;");
        mysqli_query($koneksi, "TRUNCATE TABLE penarikan_arisan;");
        mysqli_query($koneksi, "TRUNCATE TABLE periode;");
        mysqli_query($koneksi, "UPDATE users SET anggota_id = NULL;");
        mysqli_query($koneksi, "TRUNCATE TABLE anggota;");
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1;");

        $_SESSION['swal_success'] = "Seluruh data transaksi dan anggota berhasil dihapus total. Akun pengguna Anda tetap aman.";
    } else {
        $_SESSION['swal_error'] = "Teks konfirmasi salah. Reset data dibatalkan.";
    }
    header("Location: tools.php");
    exit;
}

// ACTION: Generate Dummy Data
if (isset($_POST['generate_dummy'])) {
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0;");
    mysqli_query($koneksi, "TRUNCATE TABLE pembayaran;");
    mysqli_query($koneksi, "TRUNCATE TABLE penarikan_arisan;");
    mysqli_query($koneksi, "TRUNCATE TABLE periode;");
    mysqli_query($koneksi, "UPDATE users SET anggota_id = NULL;");
    mysqli_query($koneksi, "TRUNCATE TABLE anggota;");

    // 1. Generate Anggota (50 orang)
    $nama_list = ["Budi", "Susi", "Andi", "Rina", "Joko", "Santi", "Eko", "Maya", "Dedi", "Lia", "Agus", "Dewi", "Fajar", "Indah", "Heri", "Ani", "Gani", "Sari", "Dani", "Yuni", "Bambang", "Sri", "Rudi", "Tanti", "Wawan", "Novi", "Anton", "Rika", "Surya", "Tina", "Zaki", "Umi", "Taufik", "Ina", "Bagas", "Dina", "Hendra", "Siska", "Yoga", "Ayu", "Dimas", "Mira", "Rizky", "Putri", "Adit", "Vina", "Aris", "Wati", "Galih", "Lulu"];

    $anggota_ids = [];
    $iter = 1;
    foreach ($nama_list as $n) {
        $hp = "0812" . str_pad($iter, 2, '0', STR_PAD_LEFT) . rand(100000, 999999);
        $alamat = "Jl. Contoh No. " . rand(1, 100);
        mysqli_query($koneksi, "INSERT INTO anggota (nama, no_hp, alamat, status) VALUES ('$n', '$hp', '$alamat', 'BELUM')");
        $anggota_ids[] = mysqli_insert_id($koneksi);
        $iter++;
    }

    // 2. Generate Periode (2 Selesai, 1 Aktif)
    $tgl_1 = date('Y-m-d', strtotime('-4 weeks'));
    $tgl_1_end = date('Y-m-d', strtotime('-2 weeks -1 day'));
    mysqli_query($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES ('Periode Januari', '$tgl_1', '$tgl_1_end', 'selesai')");
    $per_1 = mysqli_insert_id($koneksi);

    $tgl_2 = date('Y-m-d', strtotime('-2 weeks'));
    $tgl_2_end = date('Y-m-d', strtotime('-1 day'));
    mysqli_query($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES ('Periode Februari', '$tgl_2', '$tgl_2_end', 'selesai')");
    $per_2 = mysqli_insert_id($koneksi);

    $tgl_3 = date('Y-m-d');
    $tgl_3_end = date('Y-m-d', strtotime('+2 weeks'));
    mysqli_query($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES ('Periode Maret (Aktif)', '$tgl_3', '$tgl_3_end', 'aktif')");
    $per_3 = mysqli_insert_id($koneksi);

    // 3. Generate Payments with Varied Scenarios
    foreach ($anggota_ids as $index => $aid) {
        $unique_prefix = str_pad($aid, 3, '0', STR_PAD_LEFT) . '-' . rand(100, 999);
        if ($index <= 5)
            continue; // 0-5: Tidak bayar (3 Hutang)

        if ($index <= 15) { // 6-15: Hanya bayar Periode 1 (2 Hutang)
            $inv = 'INV-P1-' . $unique_prefix;
            mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid) VALUES ('$aid', '$per_1', 100000, 'CASH', '$tgl_1', '$inv', 1)");
            continue;
        }

        // Sisanya: Bayar normal Periode 1 & 2
        $inv1 = 'INV-P1-' . $unique_prefix;
        $inv2 = 'INV-P2-' . $unique_prefix;
        mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid) VALUES ('$aid', '$per_1', 100000, 'CASH', '$tgl_1', '$inv1', 1)");
        mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid) VALUES ('$aid', '$per_2', 100000, 'TRANSFER', '$tgl_2', '$inv2', 1)");

        if ($index > 30 && $index <= 38) { // Lunas via Admin
            $inv3 = 'INV-P3-' . $unique_prefix;
            mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid) VALUES ('$aid', '$per_3', 100000, 'CASH', '$tgl_3', '$inv3', 1)");
            mysqli_query($koneksi, "UPDATE anggota SET status='LUNAS' WHERE id='$aid'");
        } elseif ($index > 38 && $index <= 45) { // Pending via User
            $inv3 = 'INV-PEN-' . $unique_prefix;
            mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid, bukti_bayar) VALUES ('$aid', '$per_3', 100000, 'TRANSFER', '$tgl_3', '$inv3', 0, 'dummy_proof.jpg')");
        } elseif ($index > 45) { // Pending via User (acak)
            $inv3 = 'INV-PEN2-' . $unique_prefix;
            mysqli_query($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid, bukti_bayar) VALUES ('$aid', '$per_3', 150000, 'TRANSFER', '$tgl_3', '$inv3', 0, 'dummy_proof.jpg')");
        }
    }

    // 4. Generate Penarikan
    $rand_ang1 = $anggota_ids[array_rand($anggota_ids)];
    mysqli_query($koneksi, "INSERT INTO penarikan_arisan (periode_id, anggota_id, tanggal_tarik) VALUES ('$per_1', '$rand_ang1', '$tgl_1')");

    do {
        $rand_ang2 = $anggota_ids[array_rand($anggota_ids)];
    } while ($rand_ang2 == $rand_ang1);
    mysqli_query($koneksi, "INSERT INTO penarikan_arisan (periode_id, anggota_id, tanggal_tarik) VALUES ('$per_2', '$rand_ang2', '$tgl_2')");

    do {
        $rand_ang3 = $anggota_ids[array_rand($anggota_ids)];
    } while ($rand_ang3 == $rand_ang1 || $rand_ang3 == $rand_ang2);
    mysqli_query($koneksi, "INSERT INTO penarikan_arisan (periode_id, anggota_id, tanggal_tarik) VALUES ('$per_3', '$rand_ang3', '$tgl_3')");

    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1;");
    $_SESSION['swal_success'] = "Data dummy berhasil digenerate! 50 Anggota, 3 Periode, data Hutang, Lunas, dan Pending tanpa duplikat/nominal 0.";
    header("Location: dashboard.php");
    exit;
}

$pageTitle = 'Tools – GBN Arisan';
$activeMenu = 'tools';
$rootPath = '';
$assetBase = '';

include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header">
        <h1><i class="bi bi-wrench-adjustable-circle-fill"></i> Data Tools & Maintenance</h1>
        <p>Generate dummy data atau reset database untuk keperluan testing</p>
    </div>

    <div class="grid grid-2 mb-3">
        <!-- Generate Dummy -->
        <div class="card">
            <div class="card-header"><i class="bi bi-database-fill-gear"></i> Generate Dummy Data</div>
            <div class="card-body">
                <p class="text-muted">Fitur ini akan memasukkan <strong>50 Anggota</strong> baru, <strong>3
                        Periode</strong> (Januari, Februari, Maret), Pemenang Arisan, serta berbagai riwayat transaksi
                    (Lunas, Pending, dan Hutang) untuk kebutuhan simulasi sistem.</p>
                <form method="post">
                    <button type="submit" name="generate_dummy" class="btn btn-primary"
                        onclick="return confirm('Generate data dummy sekarang? Semua data lama akan diganti.')">
                        <i class="bi bi-database-fill-add"></i> Generate Demo Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card" style="border-left: 4px solid var(--danger, #dc3545)">
            <div class="card-header text-danger"><i class="bi bi-exclamation-triangle-fill"></i> DANGER ZONE: Reset All
                Data</div>
            <div class="card-body">
                <p class="text-muted">Aksi ini akan menghapus <strong>SELURUH Data Anggota, Data Transaksi Pembayaran,
                        Histori Tarikan Arisan, dan Periode</strong> secara PERMANEN.</p>
                <p class="text-danger small fw-bold">Peringatan: Aksi ini tidak bisa dibatalkan! Akun User/Admin (Login)
                    TIDAK akan dihapus.</p>

                <form method="post" id="formResetData" action="tools.php">
                    <div class="mb-3 mt-3">
                        <label class="form-label">Ketik <strong class="user-select-none">KONFIRMASI RESET</strong> untuk
                            melanjutkan:</label>
                        <input type="text" name="konfirmasi" id="inputKonfirmasi" class="form-control"
                            autocomplete="off" required>
                    </div>
                    <button type="button" class="btn btn-danger" onclick="confirmReset()"><i
                            class="bi bi-trash3-fill"></i> Kosongkan Database Sekarang</button>
                    <button type="submit" name="reset_all_data" id="btnSubmitReset" class="d-none"></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmReset() {
            const input = document.getElementById('inputKonfirmasi').value;
            if (input !== 'KONFIRMASI RESET') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    text: 'Teks konfirmasi yang Anda ketik salah!'
                });
                return;
            }

            Swal.fire({
                title: 'Anda Sangat Yakin?',
                text: "Semua data akan hangus permanen dari sistem!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('btnSubmitReset').click();
                }
            })
        }
    </script>

    <?php include 'partials/footer.php'; ?>