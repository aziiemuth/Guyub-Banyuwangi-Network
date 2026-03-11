<?php
// partials/header.php
// Usage: include at the top of every page BEFORE <body>
// Variables required: $pageTitle (string)
?>
<!doctype html>
<html lang="id" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle ?? 'GBN Arisan') ?></title>
  <meta name="description" content="Sistem Manajemen Arisan - Guyub Banyuwangi Network">

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- GBN Theme -->
  <link rel="stylesheet" href="<?= $assetBase ?? '' ?>assets/css/theme.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Apply saved theme BEFORE paint (no flash)
    (function () {
      var t = localStorage.getItem('gbn_theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>