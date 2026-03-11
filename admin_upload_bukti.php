<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pembayaran_id = (int) $_POST['pembayaran_id'];

    // Get transaction
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pembayaran WHERE id = ? AND is_valid = 0");
    mysqli_stmt_bind_param($stmt, "i", $pembayaran_id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        $_SESSION['swal_error'] = 'Transaksi tidak ditemukan atau sudah divalidasi.';
        header("Location: dashboard.php?show=pending");
        exit;
    }

    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['swal_error'] = 'Gagal mengunggah file. Silakan coba lagi.';
        header("Location: dashboard.php?show=pending");
        exit;
    }

    $file_tmp = $_FILES['bukti']['tmp_name'];
    $file_name = $_FILES['bukti']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];

    if ($_FILES['bukti']['size'] > 2 * 1024 * 1024) {
        $_SESSION['swal_error'] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        header("Location: dashboard.php?show=pending");
        exit;
    }

    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['swal_error'] = 'Format file tidak diizinkan. Gunakan JPG, JPEG, atau PNG.';
        header("Location: dashboard.php?show=pending");
        exit;
    }

    // Gunakan invoice sebagai nama jika ada
    $inv = $p['invoice'] ? $p['invoice'] : 'INV' . time();
    $new_file_name = $inv . '_admin.' . $file_ext;
    $target_dir = 'uploads/bukti/';

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_path = $target_dir . $new_file_name;

    if (move_uploaded_file($file_tmp, $target_path)) {
        // Update database: set is_valid = 1
        $stmt2 = mysqli_prepare($koneksi, "UPDATE pembayaran SET bukti_bayar=?, is_valid=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "si", $new_file_name, $pembayaran_id);

        if (mysqli_stmt_execute($stmt2)) {
            // Cek apakah pembayaran di periode aktif
            $anggota_id = $p['anggota_id'];
            $periode_id_pembayaran = $p['periode_id'];

            $cek_per = mysqli_query($koneksi, "SELECT id FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
            $per_aktif = mysqli_fetch_assoc($cek_per);

            if ($per_aktif && $periode_id_pembayaran == $per_aktif['id']) {
                $stmt3 = mysqli_prepare($koneksi, "UPDATE anggota SET status = 'LUNAS' WHERE id = ?");
                mysqli_stmt_bind_param($stmt3, "i", $anggota_id);
                mysqli_stmt_execute($stmt3);
            }

            $_SESSION['swal_success'] = 'Bukti berhasil diunggah dan pembayaran divalidasi.';
            header("Location: wa_kirim.php?inv=" . $p['invoice']);
            exit;
        } else {
            $_SESSION['swal_error'] = 'Gagal memvalidasi data ke database.';
        }
    } else {
        $_SESSION['swal_error'] = 'Gagal memindahkan file ke direktori tujuan.';
    }

    header("Location: dashboard.php?show=pending");
    exit;
}
?>