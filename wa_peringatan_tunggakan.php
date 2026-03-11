<?php
include 'auth/auth_check.php';
include 'config/koneksi.php';

$anggota_id = isset($_GET['anggota_id']) ? (int) $_GET['anggota_id'] : 0;
$tunggakan = isset($_GET['tunggakan']) ? (int) $_GET['tunggakan'] : 0;
$nominal = isset($_GET['nominal']) ? (int) $_GET['nominal'] : 0;

if (!$anggota_id || !$tunggakan || !$nominal) {
    $_SESSION['swal_error'] = 'Data tidak lengkap.';
    header("Location: dashboard.php");
    exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT nama, no_hp FROM anggota WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $anggota_id);
mysqli_stmt_execute($stmt);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$a) {
    $_SESSION['swal_error'] = 'Anggota tidak ditemukan.';
    header("Location: dashboard.php");
    exit;
}

$pesan = urlencode("
━━━━━━━━━━━━━━━━━━━━
GUYUB BANYUWANGI NETWORK
━━━━━━━━━━━━━━━━━━━━

PEMBERITAHUAN TUNGGAKAN ARISAN

Halo {$a['nama']},
Kami menginformasikan bahwa saat ini Anda memiliki tunggakan pembayaran arisan selama *$tunggakan bulan*.

Total Tagihan: *Rp " . number_format($nominal) . "*

Mohon untuk segera melakukan pelunasan agar kegiatan arisan berjalan lancar. Jika sudah melakukan pembayaran, mohon abaikan pesan ini.

Terima kasih 🙏
");

$nohp = preg_replace('/^0/', '62', $a['no_hp']);
header("Location: https://wa.me/$nohp?text=$pesan");
exit;
