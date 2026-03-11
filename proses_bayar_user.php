<?php
include 'auth/auth_check.php';
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anggota_id = $_POST['anggota_id'];
    $periode_id = $_POST['periode_id'];
    $metode = $_POST['metode'];
    $tanggal = date('Y-m-d');
    $inv = 'INV' . time();
    $nominal = $_POST['nominal'] ?? 0;

    // VALIDASI PERIODE AKTIF
    if (empty($periode_id) || $periode_id == 0) {
        $_SESSION['swal_error'] = 'Gagal: Tidak ada periode arisan yang aktif saat ini. Silakan hubungi Admin.';
        header("Location: dashboard.php");
        exit;
    }

    // CEK DOUBLE UPLOAD / TRANSAKSI
    $cek_double = mysqli_prepare($koneksi, "SELECT id, is_valid, bukti_bayar FROM pembayaran WHERE anggota_id = ? AND periode_id = ?");
    mysqli_stmt_bind_param($cek_double, "ii", $anggota_id, $periode_id);
    mysqli_stmt_execute($cek_double);
    $res_double = mysqli_stmt_get_result($cek_double);
    $existing = mysqli_fetch_assoc($res_double);

    if ($existing) {
        if ($existing['is_valid'] == 1) {
            $_SESSION['swal_error'] = 'Anda sudah tercatat lunas untuk periode ini.';
            header("Location: dashboard.php");
            exit;
        } else if (!empty($existing['bukti_bayar'])) {
            $_SESSION['swal_error'] = 'Anda sudah mengunggah bukti untuk periode ini. Menunggu verifikasi admin.';
            header("Location: dashboard.php");
            exit;
        }
    }

    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['swal_error'] = 'Gagal mengunggah file. Silakan coba lagi.';
        header("Location: dashboard.php");
        exit;
    }

    $file_tmp = $_FILES['bukti_bayar']['tmp_name'];
    $file_name = $_FILES['bukti_bayar']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];

    if ($_FILES['bukti_bayar']['size'] > 2 * 1024 * 1024) {
        $_SESSION['swal_error'] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        header("Location: dashboard.php");
        exit;
    }

    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['swal_error'] = 'Format file tidak diizinkan. Gunakan JPG, JPEG, atau PNG.';
        header("Location: dashboard.php");
        exit;
    }

    $new_file_name = $inv . '.' . $file_ext;
    $target_dir = 'uploads/bukti/';

    // Buat folder jika belum ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_path = $target_dir . $new_file_name;

    if (move_uploaded_file($file_tmp, $target_path)) {
        if ($existing) {
            // Update existing pending invoice with bukti bayar
            $stmt = mysqli_prepare($koneksi, "UPDATE pembayaran SET nominal=?, metode=?, tanggal=?, bukti_bayar=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "isssi", $nominal, $metode, $tanggal, $new_file_name, $existing['id']);
        } else {
            // Simpan ke database dengan is_valid = 0 (menunggu verifikasi)
            $stmt = mysqli_prepare($koneksi, "INSERT INTO pembayaran (anggota_id, periode_id, nominal, metode, tanggal, invoice, bukti_bayar, is_valid) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, "iiissss", $anggota_id, $periode_id, $nominal, $metode, $tanggal, $inv, $new_file_name);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['swal_success'] = 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi Admin.';
        } else {
            $_SESSION['swal_error'] = 'Gagal menyimpan data ke database.';
        }
    } else {
        $_SESSION['swal_error'] = 'Gagal memindahkan file ke direktori tujuan.';
    }

    header("Location: dashboard.php");
    exit;
}
?>