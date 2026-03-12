<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

if (!isset($_GET['inv'])) {
    header('Location: dashboard.php');
    exit;
}

$inv = mysqli_real_escape_string($koneksi, $_GET['inv']);

// Dapatkan data pembayaran
$q = mysqli_query($koneksi, "
    SELECT a.nama, a.no_hp, a.alamat, p.nominal, p.metode, p.tanggal, p.periode_id, p.is_valid
    FROM pembayaran p 
    JOIN anggota a ON a.id = p.anggota_id 
    WHERE p.invoice = '$inv'
");
$p = mysqli_fetch_assoc($q);

if (!$p) {
    $_SESSION['swal_error'] = 'Invoice tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

// Cari siapa yang narik di periode tersebut
$tarikan = "-";
if ($p['periode_id']) {
    $q_tarik = mysqli_query($koneksi, "
        SELECT a.nama 
        FROM penarikan_arisan pa 
        JOIN anggota a ON a.id = pa.anggota_id 
        WHERE pa.periode_id = '{$p['periode_id']}'
    ");

    $nama_tarikan = [];
    while ($tarik = mysqli_fetch_assoc($q_tarik)) {
        $nama_tarikan[] = $tarik['nama'];
    }

    if (count($nama_tarikan) > 0) {
        $tarikan = implode(", ", $nama_tarikan);
    }
}

// Format Nominal
$f_nominal = $p['nominal'] > 0 ? "Rp " . number_format($p['nominal'], 0, ',', '.') : "Rp 0";
$f_tanggal = date('d-m-Y', strtotime($p['tanggal']));

$status_text = $p['is_valid'] == 1 ? "LUNAS" : "MENUNGGU PEMBAYARAN (PENDING)";
$action_text = $p['is_valid'] == 1 ? "Telah diterima pembayaran dari:" : "Tagihan Arisan untuk:";

$pesan = urlencode("
-----------------------------------
GUYUB BANYUWANGI NETWORK
-----------------------------------

*INVOICE PEMBAYARAN ARISAN*
Status: *$status_text*

$action_text
Nama   : {$p['nama']}
Alamat : {$p['alamat']}
Sistem : {$p['metode']}
Tanggal: {$f_tanggal}
Nominal: *$f_nominal*

*Tarikan Arisan Periode Ini jatuh kepada:*
{$tarikan}

Terima kasih atas partisipasi dan kerjasamanya.
");

$nohp = preg_replace('/^0/', '62', $p['no_hp']);
$wa_url = "https://wa.me/{$nohp}?text=$pesan";

// Intermediate page
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim WhatsApp - GBN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: var(--surface-alt, #f8f9fa);
        }

        .wa-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
            padding: 30px;
            background: var(--surface, #fff);
        }

        .wa-icon {
            font-size: 60px;
            color: #25D366;
            margin-bottom: 20px;
        }
    </style>
    <script>
        (function () {
            var t = localStorage.getItem('gbn_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>

<body>
    <div class="wa-card">
        <i class="bi bi-whatsapp wa-icon"></i>
        <h4 class="fw-bold mb-3">Kirim Invoice WhatsApp</h4>
        <p class="text-muted mb-4">Invoice untuk <strong><?= htmlspecialchars($p['nama']) ?></strong> sudah siap
            dikirim.</p>

        <button id="btnKirim" class="btn btn-success btn-lg w-100 rounded-pill mb-3">
            <i class="bi bi-send-fill me-2"></i> Kirim Pesan Sekarang
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill w-100">Batal / Kembali ke Dashboard</a>
    </div>

    <script>
        document.getElementById('btnKirim').addEventListener('click', function () {
            window.open("<?= $wa_url ?>", "_blank");
            setTimeout(function () {
                window.location.href = "dashboard.php";
            }, 500);
        });
    </script>
</body>

</html>