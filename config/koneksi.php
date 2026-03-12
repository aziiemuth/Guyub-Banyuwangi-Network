<?php
// Koneksi database TANPA session_start
$koneksi = mysqli_connect("localhost", "root", "", "arisan");
if (!$koneksi) {
    die("Koneksi database gagal");
}
?>