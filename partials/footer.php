<?php // partials/footer.php ?>
</div><!-- .page-content -->
</div><!-- .main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // ─── THEME TOGGLE ───
  function toggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('gbn_theme', next);
    updateThemeIcon(next);
  }

  function updateThemeIcon(theme) {
    var icon = document.getElementById('themeIcon');
    if (icon) icon.innerHTML = theme === 'dark'
      ? '<i class="bi bi-sun-fill"></i>'
      : '<i class="bi bi-moon-fill"></i>';
  }

  // Init icon on load
  (function () {
    var t = localStorage.getItem('gbn_theme') || 'light';
    updateThemeIcon(t);
  })();

  // ─── SIDEBAR ───
  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }

  // Close sidebar on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  // ─── GLOBAL CONFIRM DIALOG (SweetAlert2) ───
  /**
   * gbnConfirm(url, pesan, tipe)
   * tipe: 'danger' | 'warning' | 'success'
   * url: tujuan redirect jika dikonfirmasi (null = submit form)
   * el: elemen form/submit jika url = null
   */
  function gbnConfirm(url, pesan, tipe, judul) {
    tipe = tipe || 'warning';
    judul = judul || 'Konfirmasi';
    var iconMap = { danger: 'warning', warning: 'question', success: 'info' };
    var btnColorMap = { danger: '#dc2626', warning: '#d97706', success: '#059669' };
    Swal.fire({
      title: judul,
      text: pesan,
      icon: iconMap[tipe] || 'warning',
      showCancelButton: true,
      confirmButtonColor: btnColorMap[tipe] || '#d97706',
      cancelButtonColor: '#6b7280',
      confirmButtonText: '<i class="bi bi-check-lg"></i> Ya, Lanjutkan',
      cancelButtonText: '<i class="bi bi-x-lg"></i> Batal',
      reverseButtons: true,
      focusCancel: true,
      allowOutsideClick: false,
      allowEscapeKey: true,
      customClass: { popup: 'swal-gbn-popup' }
    }).then(function (result) {
      if (result.isConfirmed && url) {
        window.location.href = url;
      }
    });
    return false;
  }

  function gbnConfirmForm(formId, pesan, tipe, judul) {
    tipe = tipe || 'warning';
    judul = judul || 'Konfirmasi';
    var iconMap = { danger: 'warning', warning: 'question', success: 'info' };
    var btnColorMap = { danger: '#dc2626', warning: '#d97706', success: '#059669' };
    Swal.fire({
      title: judul,
      text: pesan,
      icon: iconMap[tipe] || 'warning',
      showCancelButton: true,
      confirmButtonColor: btnColorMap[tipe] || '#d97706',
      cancelButtonColor: '#6b7280',
      confirmButtonText: '<i class="bi bi-check-lg"></i> Ya, Lanjutkan',
      cancelButtonText: '<i class="bi bi-x-lg"></i> Batal',
      reverseButtons: true,
      focusCancel: true,
      allowOutsideClick: false,
      allowEscapeKey: true,
      customClass: { popup: 'swal-gbn-popup' }
    }).then(function (result) {
      if (result.isConfirmed) {
        document.getElementById(formId).submit();
      }
    });
    return false;
  }

  // ─── SWEETALERT TOAST ───
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  });

  <?php if (isset($_SESSION['swal_success'])): ?>
    Toast.fire({
      icon: 'success',
      title: '<?= htmlspecialchars($_SESSION['swal_success']) ?>'
    });
    <?php unset($_SESSION['swal_success']); endif; ?>

  <?php if (isset($_SESSION['swal_error'])): ?>
    Toast.fire({
      icon: 'error',
      title: '<?= htmlspecialchars($_SESSION['swal_error']) ?>'
    });
    <?php unset($_SESSION['swal_error']); endif; ?>
</script>
</body>

</html>