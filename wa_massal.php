<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$pageTitle = 'Broadcast WA – GBN Arisan';
$activeMenu = 'wa_massal';
$rootPath = '';
$assetBase = '';

// Cek periode aktif
$cek_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
$periode_aktif = mysqli_fetch_assoc($cek_periode);
$periode_id = $periode_aktif['id'] ?? 0;

// Cari siapa yang narik di periode tersebut
$tarikan = "-";
if ($periode_id) {
    $q_tarik = mysqli_query($koneksi, "
        SELECT a.nama 
        FROM penarikan_arisan pa 
        JOIN anggota a ON a.id = pa.anggota_id 
        WHERE pa.periode_id = '$periode_id'
    ");
    $nama_tarikan = [];
    while ($tarik = mysqli_fetch_assoc($q_tarik)) {
        $nama_tarikan[] = $tarik['nama'];
    }
    if (count($nama_tarikan) > 0) {
        $tarikan = implode(", ", $nama_tarikan);
    }
}

$q = mysqli_query($koneksi, "SELECT * FROM anggota WHERE status='BELUM'");

include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-whatsapp"></i> Broadcast Pengingat Arisan
        </div>
        <div class="card-body">
            <p>Klik tombol di bawah ini untuk mengirim pesan pengingat ke seluruh anggota yang <strong>BELUM
                    LUNAS</strong> secara satu per satu.</p>

            <div class="list-group mb-3">
                <?php
                $count = 0;
                $admin_phone = "6281230709810";

                while ($a = mysqli_fetch_assoc($q)) {
                    $count++;
                    $nohp = preg_replace('/^0/', '62', $a['no_hp']);

                    $pesan_konfirmasi = urlencode("Halo Admin, saya {$a['nama']} ingin konfirmasi pembayaran arisan.");
                    $link_bayar = "https://wa.me/{$admin_phone}?text={$pesan_konfirmasi}";

                    $pesan = "Halo *{$a['nama']}*, ini adalah pengingat pembayaran arisan untuk periode ini.\n\n";
                    $pesan .= "*Tarikan Arisan Periode Ini jatuh kepada:*\n{$tarikan}\n\n";
                    $pesan .= "Mohon untuk segera melakukan pembayaran. Anda dapat klik link di bawah ini untuk melakukan *Konfirmasi Pembayaran* langsung ke WhatsApp Admin:\n";
                    $pesan .= $link_bayar . "\n\nTerima kasih atas partisipasinya.";
                    ?>
                    <a href="https://wa.me/<?= $nohp ?>?text=<?= urlencode($pesan) ?>" target="_blank"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <i class="bi bi-send-fill text-primary me-2"></i> <?= htmlspecialchars($a['nama']) ?>
                        </div>
                        <span class="badge bg-secondary"><?= htmlspecialchars($a['no_hp']) ?></span>
                    </a>
                <?php } ?>
            </div>

            <?php if ($count == 0): ?>
                <div class="alert alert-success mt-3 mb-0"><i class="bi bi-check-circle-fill"></i> Semua anggota sudah lunas
                    untuk periode ini!</div>
            <?php else: ?>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> Terdapat
                    <strong><?= $count ?></strong> anggota yang belum lunas.</div>
            <?php endif; ?>

            <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali ke
                Dashboard</a>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>