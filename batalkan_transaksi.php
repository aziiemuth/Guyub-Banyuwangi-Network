<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if (isset($_GET['id']) && isset($_GET['anggota'])) {
    $id = $_GET['id'];
    $anggota = $_GET['anggota'];

    // Hapus pembayaran dengan Prepared Statement
    $stmt = mysqli_prepare($koneksi, "DELETE FROM pembayaran WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        // Cek periode aktif
        $per_q = mysqli_query($koneksi, "SELECT id FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
        $per_aktif = mysqli_fetch_assoc($per_q);
        $per_id = $per_aktif ? $per_aktif['id'] : 0;

        // Cek apakah ada pembayaran lain untuk anggota ini di periode yang aktif
        $c_stmt = mysqli_prepare($koneksi, "SELECT id FROM pembayaran WHERE anggota_id=? AND periode_id=? AND is_valid=1");
        mysqli_stmt_bind_param($c_stmt, "ii", $anggota, $per_id);
        mysqli_stmt_execute($c_stmt);
        $sisa_trx = mysqli_stmt_get_result($c_stmt);

        // Jika tidak ada transaksi lain di periode aktif, revert status menjadi BELUM
        if (mysqli_num_rows($sisa_trx) == 0) {
            $stmt2 = mysqli_prepare($koneksi, "UPDATE anggota SET status='BELUM' WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "i", $anggota);
            mysqli_stmt_execute($stmt2);
        }

        $_SESSION['swal_success'] = 'Transaksi berhasil dibatalkan.';
    } else {
        $_SESSION['swal_error'] = 'Gagal membatalkan transaksi.';
    }
}

header("Location: dashboard.php");
exit;
?>