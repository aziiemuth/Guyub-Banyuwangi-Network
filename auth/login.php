<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include '../config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $p = trim($_POST['password']);

  $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $u);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $d = mysqli_fetch_assoc($res);

  if ($d && password_verify($p, $d['password'])) {
    $_SESSION['user'] = $d;
    header("Location: ../dashboard.php");
    exit;
  } else {
    $error = "Username atau password salah.";
  }
}
?>
<!doctype html>
<html lang="id" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – GBN Arisan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <script>
    (function () {
      var t = localStorage.getItem('gbn_theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <style>
    body {
      display: flex;
      min-height: 100vh;
    }

    .login-panel-left {
      flex: 1;
      background: linear-gradient(145deg, #1e3a8a 0%, #2563eb 50%, #7c3aed 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px;
      color: #fff;
      position: relative;
      overflow: hidden;
    }

    .login-panel-left::before {
      content: '';
      position: absolute;
      width: 380px;
      height: 380px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .06);
      top: -80px;
      right: -80px;
    }

    .login-panel-left::after {
      content: '';
      position: absolute;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .05);
      bottom: -60px;
      left: -60px;
    }

    .login-panel-left .brand-mark {
      width: 72px;
      height: 72px;
      background: rgba(255, 255, 255, .15);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin-bottom: 20px;
      backdrop-filter: blur(6px);
      z-index: 1;
      position: relative;
    }

    .login-panel-left h2 {
      font-size: 26px;
      font-weight: 800;
      margin-bottom: 10px;
      z-index: 1;
      position: relative;
      text-align: center;
    }

    .login-panel-left p {
      font-size: 14px;
      opacity: .8;
      max-width: 280px;
      text-align: center;
      z-index: 1;
      position: relative;
    }

    .login-panel-left .feature-list {
      margin-top: 30px;
      z-index: 1;
      position: relative;
      list-style: none;
      padding: 0;
    }

    .login-panel-left .feature-list li {
      padding: 7px 0;
      font-size: 13.5px;
      opacity: .85;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .login-panel-left .feature-list li i {
      font-size: 15px;
      flex-shrink: 0;
    }

    .login-panel-right {
      width: 420px;
      background: var(--surface);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px 40px;
      position: relative;
      transition: background var(--transition);
    }

    .login-panel-right .theme-btn {
      position: absolute;
      top: 20px;
      right: 20px;
    }

    .login-panel-right h1 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 6px;
    }

    .login-panel-right .subtitle {
      font-size: 13.5px;
      color: var(--text-muted);
      margin-bottom: 28px;
    }

    .login-input-wrapper {
      position: relative;
    }

    .login-input-wrapper .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      color: var(--text-muted);
      pointer-events: none;
    }

    .login-input-wrapper .form-control {
      padding-left: 38px;
    }

    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: var(--text-muted);
    }

    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }

      .login-panel-left {
        padding: 32px 24px;
        min-height: 200px;
        flex: none;
      }

      .login-panel-left h2 {
        font-size: 20px;
      }

      .login-panel-left .feature-list {
        display: none;
      }

      .login-panel-right {
        width: 100%;
        padding: 32px 24px;
      }
    }
  </style>
</head>

<body>

  <!-- LEFT PANEL -->
  <div class="login-panel-left">
    <div class="brand-mark">
      <i class="bi bi-journal-bookmark-fill" style="color:#fff"></i>
    </div>
    <h2>GUYUB BANYUWANGI<br>NETWORK</h2>
    <p>Sistem manajemen arisan warga yang aman, transparan, dan mudah digunakan.</p>
    <ul class="feature-list">
      <li><i class="bi bi-check-circle-fill" style="color:#4ade80"></i> Kelola pembayaran anggota</li>
      <li><i class="bi bi-check-circle-fill" style="color:#4ade80"></i> Validasi bukti transfer</li>
      <li><i class="bi bi-check-circle-fill" style="color:#4ade80"></i> Laporan dan export Excel</li>
      <li><i class="bi bi-check-circle-fill" style="color:#4ade80"></i> Kirim notifikasi via WhatsApp</li>
    </ul>
  </div>

  <!-- RIGHT PANEL -->
  <div class="login-panel-right">

    <button class="btn-icon theme-btn" onclick="toggleTheme()" title="Toggle Dark/Light" aria-label="Toggle theme">
      <span id="themeIcon"><i class="bi bi-moon-fill"></i></span>
    </button>

    <h1>Selamat Datang</h1>
    <p class="subtitle">Masuk ke akun Anda untuk melanjutkan.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="login-input-wrapper">
          <i class="bi bi-person input-icon"></i>
          <input id="username" type="text" name="username" class="form-control" placeholder="Masukkan username" required
            autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="login-input-wrapper">
          <i class="bi bi-lock input-icon"></i>
          <input id="password" type="password" name="password" class="form-control" placeholder="Masukkan password"
            required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block mt-2" style="padding:11px;">
        <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
      </button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Guyub Banyuwangi Network. All rights reserved.
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleTheme() {
      var html = document.documentElement;
      var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('gbn_theme', next);
      document.getElementById('themeIcon').innerHTML = next === 'dark'
        ? '<i class="bi bi-sun-fill"></i>'
        : '<i class="bi bi-moon-fill"></i>';
    }
    (function () {
      var t = localStorage.getItem('gbn_theme') || 'light';
      document.getElementById('themeIcon').innerHTML = t === 'dark'
        ? '<i class="bi bi-sun-fill"></i>'
        : '<i class="bi bi-moon-fill"></i>';
    })();
  </script>
</body>

</html>