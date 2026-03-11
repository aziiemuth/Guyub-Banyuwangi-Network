<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    // Check if we are inside a subdirectory like 'auth' or 'user' to redirect properly
    $path = "auth/login.php";
    if (file_exists("../auth/login.php")) {
        $path = "../auth/login.php";
    } elseif (file_exists("../../auth/login.php")) {
        $path = "../../auth/login.php";
    } elseif (file_exists("login.php")) {
        $path = "login.php";
    }
    header("Location: $path");
    exit;
}

function cek_admin()
{
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $path = "dashboard.php";
        if (file_exists("../dashboard.php")) {
            $path = "../dashboard.php";
        }
        header("Location: $path");
        exit;
    }
}
?>