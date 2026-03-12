<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$aksi = $_GET['aksi'] ?? '';

if ($aksi === 'tambah') {
    $nama_kategori = trim($_POST['nama_kategori']);
    if (!empty($nama_kategori)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nama_kategori);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = "Kategori berhasil ditambahkan.";
        } else {
            $_SESSION['swal_error'] = "Gagal menambahkan kategori.";
        }
    }
} elseif ($aksi === 'edit') {
    $id = (int) $_POST['id'];
    $nama_kategori = trim($_POST['nama_kategori']);
    if (!empty($nama_kategori)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $nama_kategori, $id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = "Kategori berhasil diperbarui.";
        } else {
            $_SESSION['swal_error'] = "Gagal memperbarui kategori.";
        }
    }
} elseif ($aksi === 'hapus') {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM kategori WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_success'] = "Kategori berhasil dihapus.";
    } else {
        $_SESSION['swal_error'] = "Gagal menghapus kategori. Mungkin kategori ini masih digunakan.";
    }
}

header("Location: kategori.php");
exit;
