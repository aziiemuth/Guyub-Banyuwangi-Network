<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['anggota_id'];
    $periode_id = $_POST['periode_id'];
    $nominal = $_POST['nominal'];
    $metode = $_POST['metode'];
    $tanggal = date('Y-m-d');
    $inv = 'INV' . time();
    $kirim_wa = isset($_POST['kirim_wa']) ? $_POST['kirim_wa'] : 0;

    if ($periode_id == 0) {
        $_SESSION['swal_error'] = 'Tidak ada periode aktif. Harap buat periode baru terlebih dahulu.';
        header("Location: dashboard.php");
        exit;
    }

    // CEK DOUBLE TRANSAKSI
    $cek_double = mysqli_prepare($koneksi, "SELECT id FROM pembayaran WHERE anggota_id = ? AND periode_id = ?");
    mysqli_stmt_bind_param($cek_double, "ii", $id, $periode_id);
    mysqli_stmt_execute($cek_double);
    if (mysqli_num_rows(mysqli_stmt_get_result($cek_double)) > 0) {
        $_SESSION['swal_error'] = 'Anggota ini sudah memiliki catatan transaksi (Lunas/Pending) pada periode tersebut.';
        header("Location: dashboard.php");
        exit;
    }

    // Simpan pembayaran dengan Prepared Statement (is_valid = 0 -> pending)
    $stmt = mysqli_prepare($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, is_valid) VALUES (?, ?, ?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "iiisss", $id, $periode_id, $nominal, $metode, $tanggal, $inv);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['swal_success'] = 'Pembayaran berhasil disimpan (menunggu validasi).';

        if ($kirim_wa) {
            header("Location: wa_kirim.php?inv=$inv");
            exit;
        } else {
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $_SESSION['swal_error'] = 'Gagal menyimpan pembayaran.';
        header("Location: dashboard.php");
        exit;
    }
}
?>