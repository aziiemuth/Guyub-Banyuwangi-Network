<?php
include '../auth/auth_check.php';
cek_admin();
include '../config/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    // Jangan hapus user 'admin' utama
    $stmt = mysqli_prepare($koneksi, "SELECT username FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $check = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($check && $check['username'] !== 'admin') {
        $stmt2 = mysqli_prepare($koneksi, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        if (mysqli_stmt_execute($stmt2)) {
            $_SESSION['swal_success'] = "User berhasil dihapus.";
        } else {
            $_SESSION['swal_error'] = "Gagal menghapus user.";
        }
    } else {
        $_SESSION['swal_error'] = "Tidak dapat menghapus akun admin utama.";
    }
}

header("Location: users.php");
exit;
?>