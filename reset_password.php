<?php
include 'auth/auth_check.php';
cek_admin();
include 'config/koneksi.php';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) $_POST['user_id'];
    $new_pass = $_POST['password'];

    if (strlen($new_pass) < 6) {
        $msg = "Password minimal 6 karakter";
        $msg_type = "danger";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Password berhasil di-reset";
            $msg_type = "success";
        } else {
            $msg = "Gagal mereset password.";
            $msg_type = "danger";
        }
    }
}

// Ambil list user
$users = mysqli_query($koneksi, "SELECT id, username, role FROM users ORDER BY username");

$pageTitle = 'Reset Password – GBN Arisan';
$activeMenu = 'users';
$rootPath = '';
$assetBase = '';
include 'partials/header.php';
?>

<body class="app-wrapper">
    <?php include 'partials/navbar.php'; ?>

    <div class="page-header">
        <h1><i class="bi bi-shield-lock-fill"></i> Reset Password User</h1>
        <p>Reset password untuk akun pengguna</p>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:420px">
        <div class="card-header">Form Reset Password</div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select" required>
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <button class="btn btn-danger btn-block" onclick="return confirm('Reset password untuk user ini?')">
                    <i class="bi bi-arrow-clockwise"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <a href="dashboard.php" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali ke
        Dashboard</a>

    <?php include 'partials/footer.php'; ?>