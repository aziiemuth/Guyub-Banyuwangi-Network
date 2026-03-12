<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    $stmt = mysqli_prepare($koneksi, "INSERT INTO anggota (nama, no_hp, alamat, status) VALUES (?, ?, ?, 'BELUM')");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $no_hp, $alamat);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_success'] = "Anggota $nama berhasil ditambahkan.";
    } else {
        $_SESSION['swal_error'] = "Gagal menambahkan anggota.";
    }
}

header("Location: anggota.php");
exit;
?>