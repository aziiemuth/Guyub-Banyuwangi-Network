<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Get payment detail
    $q = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE id = $id");
    $p = mysqli_fetch_assoc($q);

    if ($p) {
        $anggota_id = $p['anggota_id'];
        $periode_id_pembayaran = $p['periode_id'];

        // CEK APAKAH SUDAH ADA TRANSAKSI LAIN YANG VALID (is_valid=1)
        $cek_valid = mysqli_query($koneksi, "SELECT id FROM pembayaran WHERE anggota_id = '$anggota_id' AND periode_id = '$periode_id_pembayaran' AND is_valid = 1 AND id != $id");
        if (mysqli_num_rows($cek_valid) > 0) {
            $_SESSION['swal_error'] = 'Gagal Validasi: Anggota ini sudah memiliki transaksi lain yang sudah valid (Lunas).';
            header("Location: dashboard.php?show=pending");
            exit;
        }

        // Update payment as valid
        mysqli_query($koneksi, "UPDATE pembayaran SET is_valid = 1 WHERE id = $id");

        // Check if this payment is for the currently active period
        $cek_per = mysqli_query($koneksi, "SELECT id FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
        $per_aktif = mysqli_fetch_assoc($cek_per);

        if ($per_aktif && $periode_id_pembayaran == $per_aktif['id']) {
            // Update member status to LUNAS
            mysqli_query($koneksi, "UPDATE anggota SET status = 'LUNAS' WHERE id = $anggota_id");
        }

        $_SESSION['swal_success'] = 'Pembayaran berhasil divalidasi.';

        // Redirect to wa_kirim.php to send LUNAS invoice
        $inv = $p['invoice'];
        header("Location: wa_kirim.php?inv=$inv");
        exit;
    } else {
        $_SESSION['swal_error'] = 'Data pembayaran tidak ditemukan.';
    }
}

header("Location: dashboard.php?show=pending");
exit;
?>