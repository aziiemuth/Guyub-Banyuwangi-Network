<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Hapus pembayaran dan penarikan terkait terlebih dahulu
    $stmt1 = mysqli_prepare($koneksi, "DELETE FROM pembayaran WHERE anggota_id=?");
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM penarikan_arisan WHERE anggota_id=?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);

    $stmt3 = mysqli_prepare($koneksi, "DELETE FROM anggota WHERE id=?");
    mysqli_stmt_bind_param($stmt3, "i", $id);

    if (mysqli_stmt_execute($stmt3)) {
        $_SESSION['swal_success'] = "Data anggota berhasil dihapus.";
    } else {
        $_SESSION['swal_error'] = "Gagal menghapus anggota.";
    }
}

header("Location: anggota.php");
exit;
?>