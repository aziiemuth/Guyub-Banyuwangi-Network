<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$force_new = (int) ($_POST['force_new'] ?? $_GET['force_new'] ?? 0);
$periode_id = (int) ($_POST['periode_id'] ?? $_GET['id'] ?? 0);

$tgl_mulai = $_POST['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_POST['tgl_selesai'] ?? date('Y-m-d', strtotime('+14 days'));

// Validasi format tanggal
if (!strtotime($tgl_mulai))
    $tgl_mulai = date('Y-m-d');
if (!strtotime($tgl_selesai))
    $tgl_selesai = date('Y-m-d', strtotime('+14 days'));

$nama_periode = "Periode " . date('d M Y', strtotime($tgl_mulai)) . " - " . date('d M Y', strtotime($tgl_selesai));

if ($force_new == 1) {
    // Tidak ada periode aktif — buat yang baru
    mysqli_query($koneksi, "UPDATE periode SET status='selesai' WHERE status='aktif'");

    $stmt = mysqli_prepare($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, 'aktif')");
    mysqli_stmt_bind_param($stmt, "sss", $nama_periode, $tgl_mulai, $tgl_selesai);
    mysqli_stmt_execute($stmt);

    $_SESSION['swal_success'] = "Periode baru berhasil dibuat: $nama_periode";
} elseif ($periode_id > 0) {
    // Selesaikan periode aktif
    $stmt1 = mysqli_prepare($koneksi, "UPDATE periode SET status='selesai' WHERE id=?");
    mysqli_stmt_bind_param($stmt1, "i", $periode_id);
    mysqli_stmt_execute($stmt1);

    // Reset status semua anggota ke BELUM
    mysqli_query($koneksi, "UPDATE anggota SET status='BELUM'");

    // Buat periode baru
    $stmt2 = mysqli_prepare($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, 'aktif')");
    mysqli_stmt_bind_param($stmt2, "sss", $nama_periode, $tgl_mulai, $tgl_selesai);
    mysqli_stmt_execute($stmt2);

    $_SESSION['swal_success'] = "Periode ditutup dan periode baru dibuat: $nama_periode";
}

header("Location: dashboard.php");
exit;
?>