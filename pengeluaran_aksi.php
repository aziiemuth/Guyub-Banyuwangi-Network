<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$aksi = $_GET['aksi'] ?? '';

if ($aksi === 'tambah') {
    $tanggal = $_POST['tanggal'];
    $kategori_id = (int) $_POST['kategori_id'];
    $nominal = (int) $_POST['nominal'];
    $keterangan = trim($_POST['keterangan']);

    if (!empty($tanggal) && $nominal > 0 && !empty($keterangan)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengeluaran (tanggal, kategori_id, nominal, keterangan) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siis", $tanggal, $kategori_id, $nominal, $keterangan);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = "Pengeluaran berhasil dicatat.";
        } else {
            $_SESSION['swal_error'] = "Gagal mencatat pengeluaran.";
        }
    }
} elseif ($aksi === 'hapus') {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM pengeluaran WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_success'] = "Data pengeluaran berhasil dihapus.";
    } else {
        $_SESSION['swal_error'] = "Gagal menghapus data pengeluaran.";
    }
}

header("Location: keuangan.php");
exit;
