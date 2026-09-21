<!DOCTYPE html>
<html lang="id" class="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Keuangan Internal — BBPOM di Pangkal Pinang' ?></title>
  <meta name="description" content="Transparansi Pengelolaan Keuangan Internal Balai Besar POM di Pangkal Pinang">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/tailwind.css') ?: '1' ?>">
  <script>
    (function() {
      const saved = localStorage.getItem('theme') || 'light';
      if (saved === 'dark') document.documentElement.classList.add('dark');
    })();
  </script>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-900">

<!-- Navbar -->
<header class="sticky top-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800 shadow-soft">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">
    <div class="flex items-center justify-between h-16 gap-3">
      <a href="<?= base_url('/') ?>" class="flex items-center gap-3 min-w-0">
        <img src="<?= base_url('assets/images/logo_bpom.png') ?>" alt="BBPOM di Pangkal Pinang"
             class="w-11 h-11 rounded-lg bg-white p-1 shadow-sm shrink-0">
        <div class="min-w-0">
          <div class="text-sm font-semibold text-primary-700 dark:text-primary-300 leading-tight">
            Keuangan Internal BBPOM
          </div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate">
            Balai Besar POM di Pangkal Pinang
          </div>
        </div>
      </a>

      <div class="flex items-center gap-2">
        <button onclick="toggleTheme()" title="Ganti Tema"
                class="p-2 rounded-lg text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
          <?= iconsax('sun-1', 'w-5 h-5 hidden dark:inline') ?>
          <?= iconsax('moon', 'w-5 h-5 dark:hidden') ?>
        </button>
        <a href="<?= base_url('login') ?>"
           class="flex items-center gap-1.5 pl-2.5 pr-3 sm:pl-3 sm:pr-3.5 py-2 rounded-lg text-sm font-medium text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/40 hover:bg-primary-100 dark:hover:bg-primary-900/70 border border-primary-100 dark:border-primary-800 transition">
          <?= iconsax('login', 'w-4 h-4') ?> Masuk
        </a>
      </div>
    </div>
  </div>
</header>

<!-- Content -->
<main class="flex-1">
  <?= $this->renderSection('content') ?>
</main>

<!-- Footer -->
<footer class="bg-primary-900 text-slate-300 mt-8">
  <div class="max-w-7xl mx-auto px-4 lg:px-6 py-6">
    <div class="flex items-center gap-3 text-xs">
      <img src="<?= base_url('assets/images/logo_bpom.png') ?>" alt="BBPOM di Pangkal Pinang" class="w-10 h-10 rounded-lg bg-white p-1 shrink-0">
      <div>
        <div class="text-sm font-semibold text-white">BBPOM di Pangkal Pinang</div>
        <div>&copy; <?= date('Y') ?> Semua hak dilindungi.</div>
      </div>
    </div>
  </div>
</footer>

<!-- Scroll to top/bottom -->
<button id="scroll-fab" onclick="scrollFabClick()" title="Scroll" class="scroll-fab bg-primary-600 text-white hover:bg-primary-700 hidden">
  <?= iconsax('arrow-down', 'w-5 h-5', 'scroll-fab-icon') ?>
</button>

<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script src="<?= base_url('assets/js/icons.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/icons.js') ?: '1' ?>"></script>
<script src="<?= base_url('assets/js/pagination.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/pagination.js') ?: '1' ?>"></script>
<script>
function toggleTheme() {
  const dark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', dark ? 'dark' : 'light');
  if (typeof updateChartColors === 'function') updateChartColors(dark ? 'dark' : 'light');
}

/* ── Scroll to top/bottom ── */
(function() {
  const fab = document.getElementById('scroll-fab');
  if (!fab) return;
  const icon = document.getElementById('scroll-fab-icon');
  let lastAtTop = null;
  let ticking = false;

  function updateFab() {
    const scrollable = document.documentElement.scrollHeight > window.innerHeight + 300;
    if (!scrollable) { fab.classList.add('hidden'); ticking = false; return; }
    fab.classList.remove('hidden');
    const atTop = window.scrollY < 150;
    if (atTop !== lastAtTop) {
      lastAtTop = atTop;
      icon.innerHTML = ICONSAX_PATHS[atTop ? 'arrow-down' : 'arrow-up'];
    }
    ticking = false;
  }
  function onScroll() {
    if (!ticking) { requestAnimationFrame(updateFab); ticking = true; }
  }
  window.scrollFabClick = function() {
    if (window.scrollY < 150) {
      window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };
  document.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  document.addEventListener('DOMContentLoaded', updateFab);
})();
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
