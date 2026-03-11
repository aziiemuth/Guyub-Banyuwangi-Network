<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$where = "WHERE p.is_valid=1";
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$show = isset($_GET['show']) ? $_GET['show'] : 'lunas';

if ($show == 'cash') {
    $where .= " AND p.metode='CASH'";
} elseif ($show == 'transfer') {
    $where .= " AND p.metode='TRANSFER'";
}

if (!empty($search)) {
    $where .= " AND a.nama LIKE '%$search%'";
}

if (!empty($_GET['filter_periode'])) {
    $f_per = (int) $_GET['filter_periode'];
    $where .= " AND p.periode_id = $f_per";
}
if (!empty($_GET['filter_tanggal'])) {
    $f_tgl = mysqli_real_escape_string($koneksi, $_GET['filter_tanggal']);
    $where .= " AND p.tanggal = '$f_tgl'";
}

// POTONG KAS TETAP
$potong_kas = 1750000;

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=laporan_arisan_" . date('Ymd_His') . ".csv");

$output = fopen("php://output", "w");
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// HEADER TABEL
fputcsv($output, [
    'No. Invoice',
    'Periode',
    'Tanggal',
    'Nama',
    'Alamat',
    'Jenis Transaksi',
    'Nominal'
]);

$total_cash_kotor = 0;
$total_transfer_kotor = 0;
$total_kotor = 0;

$q = mysqli_query($koneksi, "
    SELECT p.invoice, pr.nama_periode, p.tanggal, a.nama, a.alamat, p.metode, p.nominal
    FROM pembayaran p
    JOIN anggota a ON a.id = p.anggota_id
    LEFT JOIN periode pr ON pr.id = p.periode_id
    $where
    ORDER BY p.tanggal ASC, p.id ASC
");

if ($q) {
    while ($d = mysqli_fetch_assoc($q)) {
        fputcsv($output, [
            $d['invoice'],
            $d['nama_periode'],
            date('d-m-Y', strtotime($d['tanggal'])),
            $d['nama'],
            $d['alamat'],
            $d['metode'],
            $d['nominal']
        ]);

        if ($d['metode'] == 'CASH') {
            $total_cash_kotor += $d['nominal'];
        } else {
            $total_transfer_kotor += $d['nominal'];
        }

        $total_kotor += $d['nominal'];
    }
}

// PERHITUNGAN BERSIH
$total_bersih = $total_kotor - $potong_kas;

// BARIS KOSONG
fputcsv($output, []);

// REKAP
fputcsv($output, ['JUMLAH TOTAL CASH', '', '', '', '', '', 'Rp ' . number_format($total_cash_kotor, 0, ',', '.')]);
fputcsv($output, ['JUMLAH TOTAL TRANSFER', '', '', '', '', '', 'Rp ' . number_format($total_transfer_kotor, 0, ',', '.')]);
fputcsv($output, ['TOTAL KESELURUHAN', '', '', '', '', '', 'Rp ' . number_format($total_kotor, 0, ',', '.')]);
fputcsv($output, ['POTONG KAS (Rp 1.750.000)', '', '', '', '', '', '- Rp ' . number_format($potong_kas, 0, ',', '.')]);
fputcsv($output, ['TOTAL BERSIH DITERIMA', '', '', '', '', '', 'Rp ' . number_format($total_bersih, 0, ',', '.')]);

fclose($output);
exit;
?>