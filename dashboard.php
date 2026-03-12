<?php
$pageTitle = 'Dashboard – GBN Arisan';
$activeMenu = 'dashboard';
$rootPath = '';
$assetBase = '';

include 'auth/auth_check.php';
include 'config/koneksi.php';

$user = $_SESSION['user'];
$role = $user['role'];

// ═════════════════════════════════════════════════════
// AUTO PERIOD RESET — cek jika tanggal_selesai sudah lewat
// ═════════════════════════════════════════════════════
$cek_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
$periode_aktif = mysqli_fetch_assoc($cek_periode);

if ($periode_aktif && strtotime($periode_aktif['tanggal_selesai']) < strtotime(date('Y-m-d'))) {
  // Periode sudah expired — selesaikan dan buat baru
  mysqli_query($koneksi, "UPDATE periode SET status='selesai' WHERE id=" . $periode_aktif['id']);
  mysqli_query($koneksi, "UPDATE anggota SET status='BELUM'");

  $new_start = date('Y-m-d');
  $new_end = date('Y-m-d', strtotime('+14 days'));
  $new_name = "Periode " . date('d M Y', strtotime($new_start)) . " - " . date('d M Y', strtotime($new_end));
  mysqli_query($koneksi, "INSERT INTO periode (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES ('$new_name', '$new_start', '$new_end', 'aktif')");

  // Refresh
  $cek_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status='aktif' ORDER BY id DESC LIMIT 1");
  $periode_aktif = mysqli_fetch_assoc($cek_periode);
}

$periode_id = $periode_aktif['id'] ?? 0;

// ═════════════════════════════════════════════════════
// QUERIES — period-scoped
// ═════════════════════════════════════════════════════
$total_anggota = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM anggota"))['t'];

// Lunas di periode aktif (is_valid=1)
$q_lunas = mysqli_query($koneksi, "SELECT COUNT(DISTINCT p.anggota_id) t FROM pembayaran p WHERE p.periode_id=$periode_id AND p.is_valid=1");
$total_lunas = mysqli_fetch_assoc($q_lunas)['t'];
$total_belum = $total_anggota - $total_lunas;

// Total Cash & Transfer di periode aktif (is_valid=1)
$q_cash = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) t FROM pembayaran WHERE periode_id=$periode_id AND is_valid=1 AND metode='CASH'");
$total_cash = mysqli_fetch_assoc($q_cash)['t'];
$q_trans = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) t FROM pembayaran WHERE periode_id=$periode_id AND is_valid=1 AND metode='TRANSFER'");
$total_transfer = mysqli_fetch_assoc($q_trans)['t'];

// Pending count
$q_pending_count = mysqli_query($koneksi, "SELECT COUNT(*) t FROM pembayaran WHERE periode_id=$periode_id AND is_valid=0");
$total_pending = mysqli_fetch_assoc($q_pending_count)['t'];

// Pemenang tarikan
$pemenang = null;
if ($periode_id) {
  $q_pmn = mysqli_query($koneksi, "SELECT a.nama FROM penarikan_arisan pa JOIN anggota a ON a.id=pa.anggota_id WHERE pa.periode_id=$periode_id");
  if ($q_pmn && mysqli_num_rows($q_pmn) > 0)
    $pemenang = mysqli_fetch_assoc($q_pmn);
}

// Hutang anggota (belum bayar di periode-periode sebelumnya yang sudah selesai)
$hutang_list = [];
if ($role === 'admin') {
  $all_periode = mysqli_query($koneksi, "SELECT id, nama_periode FROM periode ORDER BY id ASC");
  $periodes = [];
  while ($per = mysqli_fetch_assoc($all_periode))
    $periodes[] = $per;
  $total_periode_all = count($periodes);

  $q_all_anggota = mysqli_query($koneksi, "SELECT id, nama, no_hp FROM anggota ORDER BY nama ASC");
  while ($ag = mysqli_fetch_assoc($q_all_anggota)) {
    $q_bayar = mysqli_query($koneksi, "SELECT COUNT(*) t FROM pembayaran WHERE anggota_id={$ag['id']} AND is_valid=1");
    $total_bayar_ag = mysqli_fetch_assoc($q_bayar)['t'];
    $hutang = max(0, $total_periode_all - $total_bayar_ag);
    if ($hutang > 0) {
      $ag['hutang'] = $hutang;
      $ag['nominal_hutang'] = $hutang * 550000;
      $hutang_list[] = $ag;
    }
  }
}

// ═════════════════════════════════════════════════════
// USER-specific data
// ═════════════════════════════════════════════════════
$anggota_user = null;
$user_total_bayar = 0;
$user_tunggakan = 0;
if ($role === 'user' && !empty($user['anggota_id'])) {
  $aid = (int) $user['anggota_id'];
  $anggota_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM anggota WHERE id=$aid"));

  $total_periode_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM periode"))['t'];
  $user_total_bayar = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM pembayaran WHERE anggota_id=$aid AND is_valid=1"))['t'];
  $user_tunggakan = max(0, $total_periode_count - $user_total_bayar);
}

// ═════════════════════════════════════════════════════
// PAGINATION + SEARCH for transaction table
// ═════════════════════════════════════════════════════
$show = isset($_GET['show']) ? $_GET['show'] : 'lunas';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;
$filter_periode = isset($_GET['filter_periode']) ? (int) $_GET['filter_periode'] : 0;
$filter_tanggal = isset($_GET['filter_tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['filter_tanggal']) : '';

// Build WHERE
if ($show === 'pending') {
  $where = "WHERE p.is_valid=0";
} else {
  $where = "WHERE p.is_valid=1";
  if ($show === 'cash')
    $where .= " AND p.metode='CASH'";
  elseif ($show === 'transfer')
    $where .= " AND p.metode='TRANSFER'";
}
if (!empty($search))
  $where .= " AND a.nama LIKE '%$search%'";
if ($filter_periode > 0)
  $where .= " AND p.periode_id = $filter_periode";
if (!empty($filter_tanggal))
  $where .= " AND p.tanggal = '$filter_tanggal'";

// For user role: only own transactions
if ($role === 'user' && !empty($user['anggota_id'])) {
  $where .= " AND p.anggota_id = " . (int) $user['anggota_id'];
}

// Count total
$q_count = mysqli_query($koneksi, "SELECT COUNT(*) t FROM pembayaran p JOIN anggota a ON a.id=p.anggota_id $where");
$total_rows = mysqli_fetch_assoc($q_count)['t'];
$total_pages = max(1, ceil($total_rows / $per_page));

// Transaction data
$q_data = mysqli_query($koneksi, "
    SELECT p.*, a.nama, a.no_hp, a.alamat, pr.nama_periode
    FROM pembayaran p
    JOIN anggota a ON a.id=p.anggota_id
    LEFT JOIN periode pr ON pr.id=p.periode_id
    $where
    ORDER BY p.id DESC
    LIMIT $per_page OFFSET $offset
");

// All periodes for filter dropdown
$all_periodes = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY id DESC");

// Anggota for Bayar / Set Pemenang dropdowns
$anggota_belum = mysqli_query($koneksi, "SELECT id, nama FROM anggota WHERE status='BELUM' ORDER BY nama ASC");
$anggota_semua = mysqli_query($koneksi, "SELECT id, nama FROM anggota ORDER BY nama ASC");

include 'partials/header.php';
?>

<body class="app-wrapper">
  <?php include 'partials/navbar.php'; ?>

  <!-- PAGE HEADER -->
  <div class="page-header">
    <h1>
      <?= $role === 'admin' ? 'Dashboard Admin' : ('Selamat Datang, ' . htmlspecialchars($anggota_user['nama'] ?? $user['username'])) ?>
    </h1>
    <p><?= date('d F Y') ?> — <?= htmlspecialchars($periode_aktif['nama_periode'] ?? 'Belum ada periode aktif') ?></p>
  </div>

  <!-- PENDING ALERT for admin -->
  <?php if ($role === 'admin' && $total_pending > 0): ?>
    <div class="alert alert-warning mb-2">
      <i class="bi bi-hourglass-split"></i> Terdapat <strong><?= $total_pending ?></strong> pembayaran menunggu validasi.
      <a href="?show=pending" class="alert-link">Lihat Sekarang →</a>
    </div>
  <?php endif; ?>

  <!-- WINNER NOTIFICATION -->
  <?php if ($pemenang): ?>
    <div class="alert alert-success mb-2">
      <i class="bi bi-trophy-fill"></i>
      Pemenang tarikan arisan periode ini:
      <?php if ($role === 'admin'): ?>
        <strong><?= htmlspecialchars($pemenang['nama']) ?></strong>
      <?php else: ?>
        <strong>*** (Nama disembunyikan)</strong>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════ -->
  <!-- USER VIEW -->
  <!-- ═══════════════════════════════════════ -->
  <?php if ($role === 'user'): ?>

    <!-- TUNGGAKAN ALERT -->
    <?php if ($user_tunggakan > 0): ?>
      <div class="alert alert-danger mb-3" style="font-size:14px">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <strong>Peringatan Tunggakan!</strong>
        Anda memiliki tunggakan <strong><?= $user_tunggakan ?> periode</strong>.
        Total tagihan: <strong>Rp <?= number_format($user_tunggakan * 550000) ?></strong>
      </div>
    <?php else: ?>
      <div class="alert alert-success mb-3" style="font-size:14px">
        <i class="bi bi-check-circle-fill"></i>
        <strong>Status: Lunas!</strong> Anda tidak memiliki tunggakan arisan saat ini.
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="responsive-user-grid">
      <!-- LEFT: Period + Stats + Upload -->
      <div>
        <?php if ($periode_aktif): ?>
          <div class="period-card mb-2">
            <div class="period-label"><i class="bi bi-calendar3"></i> Periode Aktif</div>
            <div class="period-name"><?= htmlspecialchars($periode_aktif['nama_periode']) ?></div>
            <div class="period-date">
              <?= date('d M Y', strtotime($periode_aktif['tanggal_mulai'])) ?> &ndash;
              <?= date('d M Y', strtotime($periode_aktif['tanggal_selesai'])) ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="grid grid-2 mb-2">
          <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
              <div class="stat-label">Sudah Bayar</div>
              <div class="stat-value"><?= $user_total_bayar ?> <span style="font-size:14px;font-weight:400">periode</span>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon <?= $user_tunggakan > 0 ? 'red' : 'blue' ?>">
              <i class="bi <?= $user_tunggakan > 0 ? 'bi-hourglass-split' : 'bi-award-fill' ?>"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Tunggakan</div>
              <div class="stat-value" style="color:<?= $user_tunggakan > 0 ? 'var(--danger)' : 'var(--success)' ?>">
                <?= $user_tunggakan ?> <span style="font-size:14px;font-weight:400">periode</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Bukti Form -->
        <div class="card">
          <div class="card-header"><i class="bi bi-upload"></i> Konfirmasi Pembayaran</div>
          <div class="card-body">
            <form action="proses_bayar_user.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="anggota_id" value="<?= (int) ($user['anggota_id'] ?? 0) ?>">
              <input type="hidden" name="periode_id" value="<?= $periode_id ?>">
              <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="metode" class="form-select" required>
                  <option value="TRANSFER">Transfer Bank</option>
                  <option value="CASH">Cash (Dititipkan)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Nominal (Rp)</label>
                <input type="number" name="nominal" class="form-control" placeholder="Contoh: 550000" min="1" required>
              </div>
              <div class="form-group">
                <label class="form-label">Bukti Transfer <span class="text-muted text-sm">(opsional untuk
                    Cash)</span></label>
                <input type="file" name="bukti_bayar" class="form-control" accept="image/*">
                <div class="text-muted text-sm mt-1">Format: JPG, PNG, JPEG. Maks 2MB.</div>
              </div>
              <button type="submit" class="btn btn-success btn-block">
                <i class="bi bi-send-fill"></i> Kirim Konfirmasi Pembayaran
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- RIGHT: Transaction History -->
      <div>
        <div class="card">
          <div class="card-header"><i class="bi bi-clipboard-data-fill"></i> Riwayat Transaksi Saya</div>
          <div class="card-body-tight">
            <div class="table-container" style="border:none;border-radius:0 0 var(--radius) var(--radius)">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q_riwayat = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE anggota_id=" . (int) $user['anggota_id'] . " ORDER BY id DESC LIMIT 20");
                  if ($q_riwayat && mysqli_num_rows($q_riwayat) > 0):
                    while ($r = mysqli_fetch_assoc($q_riwayat)):
                      ?>
                      <tr>
                        <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                        <td><strong>Rp <?= number_format($r['nominal']) ?></strong></td>
                        <td><span
                            class="badge <?= $r['metode'] === 'CASH' ? 'badge-warning' : 'badge-info' ?>"><?= $r['metode'] ?></span>
                        </td>
                        <td>
                          <?php if ($r['is_valid'] == 1): ?>
                            <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Tervalidasi</span>
                          <?php else: ?>
                            <span class="badge badge-warning"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; else: ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted" style="padding:28px">Belum ada riwayat transaksi.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
      @media (max-width: 700px) {
        .responsive-user-grid {
          grid-template-columns: 1fr !important;
        }
      }
    </style>

  <?php endif; // end user view ?>

  <!-- ═══════════════════════════════════════ -->
  <!-- ADMIN VIEW -->
  <!-- ═══════════════════════════════════════ -->
  <?php if ($role === 'admin'): ?>

    <!-- STAT CARDS -->
    <div class="grid grid-4 mb-3">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
          <div class="stat-label">Total Anggota</div>
          <div class="stat-value"><?= $total_anggota ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info">
          <div class="stat-label">Sudah Bayar</div>
          <div class="stat-value"><?= $total_lunas ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-info">
          <div class="stat-label">Belum Bayar</div>
          <div class="stat-value"><?= $total_belum ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info">
          <div class="stat-label">Pending</div>
          <div class="stat-value"><?= $total_pending ?></div>
        </div>
      </div>
    </div>

    <div class="grid grid-2 mb-3">
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
          <div class="stat-label">Total Cash (Periode Ini)</div>
          <div class="stat-value">Rp <?= number_format($total_cash, 0, ',', '.') ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-credit-card-fill"></i></div>
        <div class="stat-info">
          <div class="stat-label">Total Transfer (Periode Ini)</div>
          <div class="stat-value">Rp <?= number_format($total_transfer, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>

    <!-- PERIODE INFO + ACTIONS -->
    <div class="grid grid-2 mb-3">
      <!-- Periode Card -->
      <div class="card">
        <div class="card-header"><i class="bi bi-calendar3"></i> Periode Aktif</div>
        <div class="card-body">
          <?php if ($periode_aktif): ?>
            <h5 class="fw-bold"><?= htmlspecialchars($periode_aktif['nama_periode']) ?></h5>
            <p class="text-muted mb-2">
              <?= date('d M Y', strtotime($periode_aktif['tanggal_mulai'])) ?> &ndash;
              <?= date('d M Y', strtotime($periode_aktif['tanggal_selesai'])) ?>
            </p>
            <a href="periode.php" class="btn btn-warning btn-sm mt-2">
              <i class="bi bi-gear-fill"></i> Kelola Periode
            </a>
          <?php else: ?>
            <p class="text-muted">Tidak ada periode aktif.</p>
            <a href="periode.php" class="btn btn-primary btn-sm mt-2">
              <i class="bi bi-plus-circle"></i> Buat Periode Baru
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Set Pemenang & Proses Bayar -->
      <div class="card">
        <div class="card-header"><i class="bi bi-lightning-fill"></i> Aksi Cepat</div>
        <div class="card-body">
          <!-- Set Pemenang & Manage Periode Link -->
          <?php if ($periode_id && !$pemenang): ?>
            <div class="mb-3 border-bottom pb-3">
              <label class="form-label fw-bold"><i class="bi bi-trophy"></i> Set Pemenang Tarikan</label>
              <div class="text-muted small mb-2">Pemenang ditarik di halaman Kelola Periode.</div>
              <a href="periode_detail.php?id=<?= $periode_id ?>" class="btn btn-success btn-sm">
                <i class="bi bi-gear"></i> Set Pemenang
              </a>
            </div>
          <?php endif; ?>

          <!-- Proses Bayar Admin -->
          <form method="post" action="proses_bayar.php">
            <input type="hidden" name="periode_id" value="<?= $periode_id ?>">
            <label class="form-label fw-bold"><i class="bi bi-wallet2"></i> Proses Bayar (Admin)</label>
            <div class="d-flex gap-2 flex-wrap">
              <select name="anggota_id" class="form-select form-select-sm" required>
                <option value="">Pilih Anggota</option>
                <?php mysqli_data_seek($anggota_belum, 0);
                while ($ag = mysqli_fetch_assoc($anggota_belum)): ?>
                  <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['nama']) ?></option>
                <?php endwhile; ?>
              </select>
              <input type="number" name="nominal" class="form-control form-control-sm" style="width:120px"
                placeholder="Nominal" min="0" value="0">
              <select name="metode" class="form-select form-select-sm" style="width:120px">
                <option value="CASH">Cash</option>
                <option value="TRANSFER">Transfer</option>
              </select>
              <label class="d-flex align-items-center gap-1 small">
                <input type="checkbox" name="kirim_wa" value="1"> WA
              </label>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Bayar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- HUTANG WARNING (Collapsible) -->
    <?php if (count($hutang_list) > 0): ?>
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer"
          data-bs-toggle="collapse" data-bs-target="#hutangCollapse">
          <span><i class="bi bi-exclamation-triangle-fill text-danger"></i> Daftar Anggota Menunggak
            (<?= count($hutang_list) ?> orang)</span>
          <i class="bi bi-chevron-down"></i>
        </div>
        <div class="collapse" id="hutangCollapse">
          <div class="card-body-tight">
            <div class="table-container" style="border:none">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>No. HP</th>
                    <th>Tunggakan</th>
                    <th>Total Tagihan</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($hutang_list as $h): ?>
                    <tr>
                      <td><?= htmlspecialchars($h['nama']) ?></td>
                      <td><?= htmlspecialchars($h['no_hp']) ?></td>
                      <td><span class="badge badge-danger"><?= $h['hutang'] ?> periode</span></td>
                      <td><strong>Rp <?= number_format($h['nominal_hutang']) ?></strong></td>
                      <td>
                        <a href="wa_peringatan_tunggakan.php?anggota_id=<?= $h['id'] ?>&tunggakan=<?= $h['hutang'] ?>&nominal=<?= $h['nominal_hutang'] ?>"
                          class="btn btn-sm btn-outline-success" target="_blank" title="Kirim WA Peringatan">
                          <i class="bi bi-whatsapp"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- FILTER / TABS -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
          <a href="?show=lunas" class="btn btn-sm <?= $show === 'lunas' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-check-circle"></i> Lunas
          </a>
          <a href="?show=cash" class="btn btn-sm <?= $show === 'cash' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="bi bi-cash-stack"></i> Cash
          </a>
          <a href="?show=transfer" class="btn btn-sm <?= $show === 'transfer' ? 'btn-info' : 'btn-outline-info' ?>">
            <i class="bi bi-credit-card"></i> Transfer
          </a>
          <a href="?show=pending" class="btn btn-sm <?= $show === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
            <i class="bi bi-hourglass-split"></i> Pending <?php if ($total_pending > 0): ?><span
                class="badge bg-danger"><?= $total_pending ?></span><?php endif; ?>
          </a>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="export_lunas_excel.php?show=<?= $show ?>&search=<?= urlencode($search) ?>&filter_periode=<?= $filter_periode ?>&filter_tanggal=<?= urlencode($filter_tanggal) ?>"
            class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-down"></i> Export CSV
          </a>
        </div>
      </div>
      <div class="card-body" style="padding:12px">
        <form method="get" class="d-flex gap-2 flex-wrap align-items-end filter-form">
          <input type="hidden" name="show" value="<?= htmlspecialchars($show) ?>">
          <div>
            <label class="form-label small mb-0">Cari Nama</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama..."
              value="<?= htmlspecialchars($search) ?>">
          </div>
          <div>
            <label class="form-label small mb-0">Periode</label>
            <select name="filter_periode" class="form-select form-select-sm">
              <option value="">Semua</option>
              <?php mysqli_data_seek($all_periodes, 0);
              while ($fp = mysqli_fetch_assoc($all_periodes)): ?>
                <option value="<?= $fp['id'] ?>" <?= $filter_periode == $fp['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($fp['nama_periode']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div>
            <label class="form-label small mb-0">Tanggal</label>
            <input type="date" name="filter_tanggal" class="form-control form-control-sm"
              value="<?= htmlspecialchars($filter_tanggal) ?>">
          </div>
          <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
          <a href="?show=<?= $show ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        </form>
      </div>

      <!-- TABLE -->
      <div class="card-body-tight">
        <div class="table-container" style="border:none">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Bukti</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($q_data && mysqli_num_rows($q_data) > 0): ?>
                <?php while ($d = mysqli_fetch_assoc($q_data)): ?>
                  <tr>
                    <td><small class="text-muted"><?= htmlspecialchars($d['invoice'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($d['nama']) ?></td>
                    <td><?= date('d M Y', strtotime($d['tanggal'])) ?></td>
                    <td><strong>Rp <?= number_format($d['nominal'], 0, ',', '.') ?></strong></td>
                    <td>
                      <span class="badge <?= $d['metode'] === 'CASH' ? 'badge-warning' : 'badge-info' ?>">
                        <?= $d['metode'] ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($d['bukti_bayar'])): ?>
                        <a href="uploads/bukti/<?= htmlspecialchars($d['bukti_bayar']) ?>" target="_blank"
                          class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-image"></i> Bukti
                        </a>
                      <?php else: ?>
                        <span class="text-muted small">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <?php if ($show === 'pending'): ?>
                          <a href="validasi_pembayaran.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-success"
                            onclick="return confirm('Validasi pembayaran ini?')">
                            <i class="bi bi-check-lg"></i> Validasi
                          </a>
                          <!-- Admin Upload Bukti -->
                          <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#uploadBuktiModal<?= $d['id'] ?>">
                            <i class="bi bi-upload"></i>
                          </button>
                        <?php endif; ?>
                        <a href="batalkan_transaksi.php?id=<?= $d['id'] ?>&anggota=<?= $d['anggota_id'] ?>"
                          class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan transaksi ini?')">
                          <i class="bi bi-x-lg"></i>
                        </a>
                        <?php if ($d['is_valid'] == 1): ?>
                          <a href="wa_kirim.php?inv=<?= urlencode($d['invoice']) ?>" class="btn btn-sm btn-outline-success"
                            title="Kirim WA">
                            <i class="bi bi-whatsapp"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>

                  <?php if ($show === 'pending'): ?>
                    <!-- Admin Upload Bukti Modal -->
                    <div class="modal fade" id="uploadBuktiModal<?= $d['id'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h6 class="modal-title">Upload Bukti - <?= htmlspecialchars($d['nama']) ?></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <form method="post" action="admin_upload_bukti.php" enctype="multipart/form-data">
                            <div class="modal-body">
                              <input type="hidden" name="pembayaran_id" value="<?= $d['id'] ?>">
                              <input type="file" name="bukti" class="form-control" accept="image/*" required>
                              <small class="text-muted">Upload bukti & otomatis validasi.</small>
                            </div>
                            <div class="modal-footer">
                              <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-upload"></i> Upload &
                                Validasi</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted" style="padding:28px">Tidak
                    ada data.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PAGINATION -->
      <?php if ($total_pages > 1): ?>
        <div class="card-body d-flex justify-content-center">
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link"
                    href="?show=<?= $show ?>&search=<?= urlencode($search) ?>&filter_periode=<?= $filter_periode ?>&filter_tanggal=<?= urlencode($filter_tanggal) ?>&page=<?= $page - 1 ?>">‹</a>
                </li>
              <?php endif; ?>
              <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link"
                    href="?show=<?= $show ?>&search=<?= urlencode($search) ?>&filter_periode=<?= $filter_periode ?>&filter_tanggal=<?= urlencode($filter_tanggal) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link"
                    href="?show=<?= $show ?>&search=<?= urlencode($search) ?>&filter_periode=<?= $filter_periode ?>&filter_tanggal=<?= urlencode($filter_tanggal) ?>&page=<?= $page + 1 ?>">›</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>

  <?php endif; // end admin view ?>

  <?php include 'partials/footer.php'; ?>