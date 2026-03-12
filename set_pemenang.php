<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periode_id = (int) ($_POST['periode_id'] ?? 0);
    $anggota_id = (int) ($_POST['anggota_id'] ?? 0);
    $tanggal_tarik = date('Y-m-d');

    if ($periode_id && $anggota_id) {
        // Cek belum ada pemenang di periode ini
        $cek = mysqli_prepare($koneksi, "SELECT id FROM penarikan_arisan WHERE periode_id=?");
        mysqli_stmt_bind_param($cek, "i", $periode_id);
        mysqli_stmt_execute($cek);
        $res = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($res) == 0) {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO penarikan_arisan (periode_id, anggota_id, tanggal_tarik) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iis", $periode_id, $anggota_id, $tanggal_tarik);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['swal_success'] = 'Pemenang arisan berhasil ditetapkan.';
            } else {
                $_SESSION['swal_error'] = 'Gagal menetapkan pemenang.';
            }
        } else {
            $_SESSION['swal_error'] = 'Sudah ada pemenang di periode ini.';
        }
    }
}

header("Location: dashboard.php");
exit;
?>