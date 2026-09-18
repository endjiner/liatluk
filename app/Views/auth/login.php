<!DOCTYPE html>
<html lang="id" class="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — BBPOM di Pangkal Pinang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.css') ?>">
  <script src="<?= base_url('assets/js/icons.js') ?>"></script>
  <script>
    (function() {
      const saved = localStorage.getItem('theme') || 'light';
      if (saved === 'dark') document.documentElement.classList.add('dark');
    })();
  </script>
</head>
<body class="min-h-screen bg-primary-900 flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <!-- Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lift overflow-hidden">
      <!-- Header -->
      <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100 dark:border-slate-700">
        <img src="<?= base_url('assets/images/logo_bpom.png') ?>" alt="BBPOM di Pangkal Pinang"
             class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-white p-2 shadow-soft ring-1 ring-slate-200">
        <h1 class="text-lg font-bold text-slate-800 dark:text-slate-100">
          Sistem Pengelolaan Keuangan Internal
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Balai Besar POM di Pangkal Pinang
        </p>
      </div>

      <!-- Body -->
      <div class="px-8 py-6">
        <div class="text-center mb-5">
          <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Login Administrator</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Masuk untuk mengelola data keuangan</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm dark:bg-red-900/30 dark:border-red-800 dark:text-red-300">
          <?= iconsax('info-circle', 'w-4 h-4 shrink-0') ?>
          <span><?= session()->getFlashdata('error') ?></span>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
        <div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300">
          <?= iconsax('tick-circle', 'w-4 h-4 shrink-0') ?>
          <span><?= session()->getFlashdata('success') ?></span>
        </div>
        <?php endif; ?>

        <form action="<?= base_url('login') ?>" method="post" class="space-y-4">
          <?= csrf_field() ?>
          <div>
            <label class="form-label" for="username">Username</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <?= iconsax('user', 'w-4 h-4') ?>
              </span>
              <input type="text" name="username" id="username" required autofocus
                     class="form-control pl-9" placeholder="Nama pengguna">
            </div>
          </div>
          <div>
            <label class="form-label" for="password">Kata Sandi</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <?= iconsax('lock-1', 'w-4 h-4') ?>
              </span>
              <input type="password" name="password" id="password" required
                     class="form-control pl-9 pr-10" placeholder="Kata sandi">
              <button type="button" onclick="togglePw()" tabindex="-1"
                      class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                      title="Tampilkan/Sembunyikan">
                <?= iconsax('eye', 'w-4 h-4', 'pw-icon') ?>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full mt-6">
            <?= iconsax('login', '') ?> Masuk
          </button>
        </form>

        <div class="mt-5 text-center">
          <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition">
            <?= iconsax('arrow-left', 'w-3.5 h-3.5') ?> Kembali ke Dashboard Publik
          </a>
        </div>
      </div>
    </div>

    <p class="text-center text-xs text-slate-300 mt-4">
      &copy; <?= date('Y') ?> BBPOM di Pangkal Pinang. Akses hanya untuk admin resmi.
    </p>
  </div>

  <script>
    function togglePw() {
      const input = document.getElementById('password');
      const icon = document.getElementById('pw-icon');
      const isPw = input.type === 'password';
      input.type = isPw ? 'text' : 'password';
      icon.outerHTML = iconsax(isPw ? 'eye-off' : 'eye', icon.getAttribute('class'), 'pw-icon');
    }
  </script>
</body>
</html>
