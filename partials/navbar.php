<?php
// partials/navbar.php
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$currentUser = $_SESSION['user']['username'] ?? 'User';

$menuItems = [
  ['slug' => 'dashboard', 'icon' => 'bi-house-door-fill', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
];
if ($isAdmin) {
  $menuItems[] = ['slug' => 'anggota', 'icon' => 'bi-people-fill', 'label' => 'Kelola Anggota', 'href' => 'anggota.php'];
  $menuItems[] = ['slug' => 'periode', 'icon' => 'bi-calendar3', 'label' => 'Kelola Periode', 'href' => 'periode.php'];
  $menuItems[] = ['slug' => 'users', 'icon' => 'bi-person-badge-fill', 'label' => 'User Management', 'href' => 'user/users.php'];
  $menuItems[] = ['slug' => 'wa_massal', 'icon' => 'bi-whatsapp', 'label' => 'Broadcast WA', 'href' => 'wa_massal.php'];
  $menuItems[] = ['slug' => 'export', 'icon' => 'bi-file-earmark-excel-fill', 'label' => 'Export Excel', 'href' => 'export_lunas_excel.php'];
  $menuItems[] = ['slug' => 'tools', 'icon' => 'bi-wrench-adjustable-circle-fill', 'label' => 'Tools', 'href' => 'tools.php'];
}
?>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <i class="bi bi-journal-bookmark-fill" style="color:#fff;font-size:20px"></i>
    </div>
    <div class="brand-name">GUYUB BANYUWANGI</div>
    <div class="brand-sub">Sistem Manajemen Arisan</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <?php foreach ($menuItems as $item): ?>
      <a href="<?= $rootPath ?? '' ?><?= $item['href'] ?>"
        class="<?= ($activeMenu ?? '') === $item['slug'] ? 'active' : '' ?>">
        <span class="nav-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
        <?= $item['label'] ?>
      </a>
    <?php endforeach; ?>

    <div class="nav-label" style="margin-top:16px;">Akun</div>
    <a href="#" onclick="return gbnConfirm('<?= $rootPath ?? '' ?>auth/logout.php', 'Anda akan keluar dari sistem. Lanjutkan?', 'warning', 'Logout?')">
      <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
      Logout
    </a>
  </nav>

  <!-- User info bottom -->
  <div style="padding:14px 18px;border-top:1px solid rgba(255,255,255,.08);">
    <div style="display:flex;align-items:center;gap:10px;">
      <div
        style="width:32px;height:32px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;font-weight:700;flex-shrink:0;">
        <?= strtoupper(substr($currentUser, 0, 1)) ?>
      </div>
      <div>
        <div style="font-size:12.5px;font-weight:600;color:#fff;"><?= htmlspecialchars($currentUser) ?></div>
        <div style="font-size:11px;color:var(--sidebar-text);"><?= $isAdmin ? 'Administrator' : 'Anggota' ?></div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-content">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="flex-gap">
      <button class="btn-icon btn-hamburger" id="hamburgerBtn" onclick="openSidebar()" aria-label="Menu">
        <i class="bi bi-list" style="font-size:20px"></i>
      </button>
      <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </div>

    <div class="topbar-actions">
      <!-- Dark/Light Toggle -->
      <button class="btn-icon" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode"
        aria-label="Toggle theme">
        <span id="themeIcon"><i class="bi bi-moon-fill"></i></span>
      </button>
    </div>
  </header>

  <div class="page-content">