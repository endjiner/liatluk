<!DOCTYPE html>
<html lang="id" class="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Admin — BBPOM di Pangkal Pinang' ?></title>
  <meta name="description" content="Panel Admin Sistem Pengelolaan Keuangan Internal BBPOM di Pangkal Pinang">
  <?= csrf_meta() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.css') ?>">
  <script>
    // Set theme early to avoid flash
    (function() {
      const saved = localStorage.getItem('theme') || 'light';
      if (saved === 'dark') document.documentElement.classList.add('dark');
    })();
  </script>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-900">

<div class="min-h-screen flex flex-col">

  <?php
    $isSuperAdminLayout = session()->get('admin_role') === 'super_admin';
    $navLinks = [
      ['url' => 'admin',           'label' => 'Dashboard',        'icon' => 'category2', 'match' => ['admin', 'admin/dashboard']],
      ['url' => 'admin/laporan',   'label' => 'Laporan',          'icon' => 'document-text', 'match' => ['laporan']],
    ];
    $navLinks = array_values(array_filter($navLinks, fn($nl) => empty($nl['superadmin']) || $isSuperAdminLayout));
    $isNavActive = function ($nl) {
      foreach ($nl['match'] as $m) { if (str_contains(current_url(), $m)) return true; }
      return false;
    };
  ?>

  <!-- ═══════════ TOP NAVBAR ═══════════ -->
  <div class="sticky top-0 z-30 px-3 pt-3 lg:px-6 lg:pt-4">
    <header class="max-w-7xl mx-auto bg-white dark:bg-slate-800 rounded-2xl shadow-lift border border-slate-200/60 dark:border-slate-700/60">
      <div class="flex items-center justify-between h-16 px-3 lg:px-5 gap-2">
        <!-- Brand -->
        <a href="<?= base_url('admin') ?>" class="flex items-center gap-2.5 shrink-0 min-w-0">
          <img src="<?= base_url('assets/images/logo_bpom.png') ?>" alt="BBPOM di Pangkal Pinang"
               class="w-9 h-9 shrink-0 rounded-lg bg-white p-1 ring-1 ring-slate-200 dark:ring-slate-600">
          <div class="hidden sm:block min-w-0">
            <div class="text-sm font-bold leading-tight text-slate-800 dark:text-slate-100 truncate">Keuangan Internal</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight truncate">BBPOM Pangkal Pinang</div>
          </div>
        </a>

        <!-- Center nav (desktop) -->
        <nav class="hidden lg:flex items-center gap-1">
          <?php foreach ($navLinks as $nl): $isActive = $isNavActive($nl); ?>
          <a href="<?= base_url($nl['url']) ?>"
             class="flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium whitespace-nowrap transition <?= $isActive ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
            <?= iconsax($nl['icon'], 'w-4 h-4 shrink-0') ?> <?= $nl['label'] ?>
          </a>
          <?php endforeach; ?>
        </nav>

        <!-- Right actions -->
        <div class="flex items-center gap-1 shrink-0">
          <!-- Theme -->
          <button onclick="toggleTheme()" title="Ganti Tema"
                  class="p-2 rounded-lg text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <?= iconsax('sun-1', 'w-5 h-5 hidden dark:inline', 'theme-icon-sun') ?>
            <?= iconsax('moon', 'w-5 h-5 dark:hidden', 'theme-icon-moon') ?>
          </button>


          <!-- User Menu -->
          <div class="relative" id="user-menu-wrapper">
            <button onclick="toggleUserMenu()" title="Menu Pengguna"
                    class="flex items-center gap-2 pl-1.5 pr-1.5 sm:pr-3 py-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition">
              <div class="w-8 h-8 shrink-0 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-semibold">
                <?= strtoupper(substr(session()->get('admin_username') ?? 'A', 0, 1)) ?>
              </div>
              <span class="hidden sm:inline text-sm font-medium text-slate-700 dark:text-slate-200 max-w-[120px] truncate"><?= esc(session()->get('admin_username') ?? 'Administrator') ?></span>
              <?= iconsax('arrow-down-2', 'hidden sm:inline w-3.5 h-3.5 text-slate-400') ?>
            </button>
            <div id="user-menu-dropdown" class="hidden absolute right-0 top-full mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-lift border border-slate-200 dark:border-slate-700 overflow-hidden">
              <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-semibold">
                  <?= strtoupper(substr(session()->get('admin_username') ?? 'A', 0, 1)) ?>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-semibold truncate"><?= esc(session()->get('admin_username') ?? 'Administrator') ?></div>
                  <div class="text-[11px] text-slate-500 dark:text-slate-400">
                    <?= $isSuperAdminLayout ? 'Super Admin' : 'Admin' ?>
                  </div>
                </div>
              </div>
              <button type="button" onclick="openModal('modal-ganti-password')" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                <?= iconsax('lock-1', 'w-4 h-4') ?> Ganti Password
              </button>
              <?php if ($isSuperAdminLayout): ?>
              <a href="<?= base_url('admin/pengaturan') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                <?= iconsax('setting-2', 'w-4 h-4') ?> Pengaturan
              </a>
              <?php endif; ?>
              <a href="<?= base_url('/') ?>" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                <?= iconsax('global', 'w-4 h-4') ?> Lihat Publik
              </a>
              <button type="button" onclick="confirmLogout(event)" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 border-t border-slate-200 dark:border-slate-700">
                <?= iconsax('logout', 'w-4 h-4') ?> Keluar
              </button>
            </div>
          </div>

          <!-- Mobile nav toggle -->
          <button onclick="toggleMobileNav()" title="Menu"
                  class="lg:hidden p-2 rounded-lg text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <?= iconsax('hamberger-menu', 'w-5 h-5', 'mobile-nav-icon') ?>
          </button>
        </div>
      </div>

      <!-- Mobile nav panel -->
      <nav id="mobile-nav" class="hidden lg:hidden border-t border-slate-200 dark:border-slate-700 p-2 space-y-1">
        <?php foreach ($navLinks as $nl): $isActive = $isNavActive($nl); ?>
        <a href="<?= base_url($nl['url']) ?>"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
          <?= iconsax($nl['icon'], 'w-4 h-4') ?> <?= $nl['label'] ?>
        </a>
        <?php endforeach; ?>
      </nav>
    </header>
  </div>

  <!-- Flash Messages -->
  <?php if (session()->getFlashdata('success')): ?>
  <div class="max-w-7xl w-full mx-auto px-4 lg:px-6 pt-4">
    <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-200">
      <?= iconsax('tick-circle', 'w-5 h-5 shrink-0') ?>
      <span class="text-sm"><?= session()->getFlashdata('success') ?></span>
    </div>
  </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
  <div class="max-w-7xl w-full mx-auto px-4 lg:px-6 pt-4">
    <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-200">
      <?= iconsax('info-circle', 'w-5 h-5 shrink-0') ?>
      <span class="text-sm"><?= session()->getFlashdata('error') ?></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- ═══════════ PAGE CONTENT ═══════════ -->
  <main class="flex-1">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 py-5 lg:py-8">
      <?= $this->renderSection('content') ?>
    </div>
  </main>
</div>

<!-- ═══════════ MODAL: LOGOUT ═══════════ -->
<div id="modal-logout" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-logout')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center">
            <?= iconsax('logout', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Konfirmasi Keluar</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Sesi admin akan berakhir</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          Anda akan keluar dari panel admin. Login kembali diperlukan untuk mengakses sistem.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-logout')">Batal</button>
        <a href="<?= base_url('logout') ?>" class="btn btn-danger">
          <?= iconsax('logout', '') ?> Ya, Keluar
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════ MODAL: GANTI PASSWORD SAYA (kedua role) ═══════════ -->
<div id="modal-ganti-password" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-ganti-password')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <form action="<?= base_url('admin/ganti-password') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 shrink-0 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center">
              <?= iconsax('lock-1', 'w-5 h-5') ?>
            </div>
            <div>
              <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Ganti Password</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Untuk akun Anda sendiri</p>
            </div>
          </div>
        </div>
        <div class="modal-body space-y-4">
          <div>
            <label class="form-label">Password Saat Ini <span class="text-red-500">*</span></label>
            <div class="relative">
              <input type="password" name="current_password" id="gp-current-password" class="form-control pr-10" required autocomplete="current-password">
              <button type="button" onclick="togglePasswordVisibility('gp-current-password', this)" tabindex="-1"
                      class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <?= iconsax('eye', 'w-4 h-4') ?>
              </button>
            </div>
          </div>
          <div>
            <label class="form-label">Password Baru <span class="text-red-500">*</span></label>
            <div class="relative">
              <input type="password" name="new_password" id="gp-new-password" class="form-control pr-10" required minlength="6" autocomplete="new-password">
              <button type="button" onclick="togglePasswordVisibility('gp-new-password', this)" tabindex="-1"
                      class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <?= iconsax('eye', 'w-4 h-4') ?>
              </button>
            </div>
            <p class="form-hint">Minimal 6 karakter.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-ganti-password')">Batal</button>
          <button type="submit" class="btn btn-primary">
            <?= iconsax('save-2', '') ?> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════ MODAL: KONFIRMASI UMUM (dipakai lintas halaman via tampilkanKonfirmasi()) ═══════════ -->
<div id="modal-konfirmasi" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-konfirmasi')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <?= iconsax('warning-2', 'w-5 h-5') ?>
          </div>
          <div><h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Konfirmasi</h3></div>
        </div>
      </div>
      <div class="modal-body"><p class="text-sm text-slate-600 dark:text-slate-300" id="konfirmasi-text">Yakin ingin melanjutkan?</p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-konfirmasi')">Batal</button>
        <button type="button" class="btn btn-primary" id="btn-konfirmasi-ya">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════ DRAWER: INPUT CEPAT ═══════════ -->
<aside id="main-input-drawer" class="hidden fixed inset-0 z-50">
  <div class="modal-backdrop" onclick="closeMainDrawer()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Input Data Baru</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Tambahkan transaksi keuangan</p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeMainDrawer()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <label class="form-label">Jenis Data</label>
          <div class="grid grid-cols-3 gap-2">
            <button type="button" onclick="selectJenisData('pemasukan')" id="jcard-pemasukan"
                    class="jenis-card p-3 rounded-lg border-2 text-xs font-medium flex flex-col items-center gap-1.5 transition">
              <?= iconsax('trend-up', 'w-5 h-5 text-emerald-600') ?>
              Pemasukan
            </button>
            <button type="button" onclick="selectJenisData('pengeluaran')" id="jcard-pengeluaran"
                    class="jenis-card p-3 rounded-lg border-2 text-xs font-medium flex flex-col items-center gap-1.5 transition">
              <?= iconsax('trend-down', 'w-5 h-5 text-red-600') ?>
              Pengeluaran
            </button>
            <button type="button" onclick="handlePilihPerjadin()" id="jcard-perjadin"
                    class="jenis-card p-3 rounded-lg border-2 text-xs font-medium flex flex-col items-center gap-1.5 transition border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-primary-400">
              <?= iconsax('airplane', 'w-5 h-5 text-primary-600') ?>
              Perjalanan Dinas
            </button>
          </div>
        </div>

        <form id="drawer-single-form" onsubmit="handleSingleSubmit(event)">
          <input type="hidden" name="tipe" id="single-jenis-val" value="pemasukan">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
              <label class="form-label">Kategori <span class="text-red-500">*</span></label>
              <select name="kategori" id="drawer-kategori" class="form-control" required>
                <option value="">Pilih kategori...</option>
              </select>
            </div>
          </div>

          <script>
            const KATEGORI_PEMASUKAN = <?= json_encode(\App\Config\Kategori::$pemasukan) ?>;
            const KATEGORI_PENGELUARAN = <?= json_encode(\App\Config\Kategori::$pengeluaran) ?>;
          </script>

          <div class="mt-4" id="field-sumber-wrap">
            <label class="form-label" id="label-sumber">Sumber Dana</label>
            <input type="text" name="sumber" id="input-sumber" class="form-control" placeholder="Nama sumber dana / penerima">
          </div>

          <div class="mt-4">
            <label class="form-label">Nominal <span class="text-red-500">*</span></label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
              <input type="text" inputmode="numeric" name="jumlah" id="drawer-jumlah"
                     class="form-control input-rupiah pl-9" placeholder="0" required oninput="syncDrawerDiterima()">
            </div>
          </div>

          <div class="mt-4" id="field-status-wrap">
            <label class="form-label">Status Dana</label>
            <select name="status_dana" id="drawer-status" class="form-control" onchange="toggleDrawerDiterima(this)">
              <option value="diterima">Sudah Diterima</option>
              <option value="sebagian">Sebagian</option>
              <option value="belum_diterima">Belum Diterima</option>
            </select>
          </div>

          <div class="mt-4 hidden" id="field-diterima-wrap">
            <label class="form-label">Jumlah Diterima</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
              <input type="text" inputmode="numeric" name="jumlah_diterima" id="drawer-jumlah-diterima"
                     class="form-control input-rupiah pl-9" placeholder="0">
            </div>
          </div>

          <div class="mt-4">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" placeholder="Keterangan singkat (opsional)">
          </div>

          <div class="mt-4">
            <label class="form-label">Catatan Internal (Admin Only)</label>
            <input type="text" name="catatan_internal" class="form-control" placeholder="Tidak ditampilkan ke publik">
          </div>

          <div class="mt-4">
            <label class="form-label">Bukti Transaksi (Opsional)</label>
            <label class="block border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition">
              <input type="file" name="bukti" accept="image/*,.pdf" class="hidden">
              <?= iconsax('cloud-add', 'w-6 h-6 mx-auto text-slate-400') ?>
              <div class="text-sm text-slate-600 dark:text-slate-300 mt-1">Klik atau seret file</div>
              <div class="text-xs text-slate-500 mt-0.5">JPG, PNG, PDF · Max 3MB</div>
            </label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeMainDrawer()">Batal</button>
        <button type="button" class="btn btn-primary" id="drawer-submit-btn" onclick="submitMainDrawer()">
          <?= iconsax('check', '') ?> <span id="drawer-submit-text">Simpan Transaksi</span>
        </button>
      </div>
    </div>
  </div>
</aside>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-[100] space-y-2 pointer-events-none"></div>

<!-- Scroll to top/bottom -->
<button id="scroll-fab" onclick="scrollFabClick()" title="Scroll" class="scroll-fab bg-primary-600 text-white hover:bg-primary-700 hidden">
  <?= iconsax('arrow-down', 'w-5 h-5', 'scroll-fab-icon') ?>
</button>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('assets/js/icons.js') ?>"></script>
<script src="<?= base_url('assets/js/pagination.js') ?>"></script>
<script>
const GLOBAL_BASE = '<?= base_url() ?>';
let currentJenisData = 'pemasukan';

/* ── CSRF ──────────────────────────────────────────────────────────────
   Semua form di halaman ini kirim datanya lewat fetch(), bukan submit form
   biasa, jadi token CSRF (yang cuma hidden-input di form) tidak pernah ikut
   terkirim. Daripada mengubah puluhan pemanggilan fetch() satu per satu,
   window.fetch di-patch SEKALI di sini supaya tiap request POST/PUT/DELETE/
   PATCH otomatis dilampiri tokennya.
   Token dibaca dari <meta> (lihat csrf_meta() di <head>), BUKAN dari
   document.cookie — cookie CSRF CI4 di-set HttpOnly (mengikuti default
   Config\Cookie), jadi memang tidak bisa dibaca lewat JS sama sekali.
   Ini juga sebabnya Security::$regenerate sengaja di-set false: nilai di
   <meta> itu statis untuk seumur hidup halaman, jadi token tidak boleh
   berubah di tengah jalan atau fetch kedua dst akan ditolak.

   PENTING: kalau body-nya FormData, token WAJIB ikut sebagai field FormData
   (bukan cuma header). CI4 selalu mencoba "membersihkan" token dari body
   setelah verifikasi (Security::removeTokenInRequest) — kalau tidak
   ketemu sebagai field di $_POST, CI4 mem-parse ULANG raw body pakai
   parse_str() seolah-olah itu application/x-www-form-urlencoded, yang
   MERUSAK body multipart asli (termasuk field lain & file upload apa pun
   yang ikut di form itu). Body kosong (aksi tanpa form, mis. hapus/toggle)
   aman pakai header saja karena CI4 langsung berhenti kalau body-nya "". */
const CSRF_FIELD_NAME  = '<?= esc(csrf_token(), 'js') ?>';
const CSRF_HEADER_NAME = '<?= esc(csrf_header(), 'js') ?>';

function getCsrfToken() {
  return document.querySelector('meta[name="' + CSS.escape(CSRF_HEADER_NAME) + '"]')?.content || '';
}

(function() {
  const originalFetch = window.fetch.bind(window);
  window.fetch = function(input, init) {
    init = init || {};
    const method = (init.method || 'GET').toUpperCase();
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
      const token = getCsrfToken();
      if (init.body instanceof FormData) {
        if (!init.body.has(CSRF_FIELD_NAME)) init.body.append(CSRF_FIELD_NAME, token);
      } else {
        const headers = new Headers(init.headers || {});
        if (!headers.has(CSRF_HEADER_NAME)) headers.set(CSRF_HEADER_NAME, token);
        init.headers = headers;
      }
    }
    return originalFetch(input, init);
  };
})();

/* ── Theme ── */
function toggleTheme() {
  const dark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', dark ? 'dark' : 'light');
  if (typeof updateChartColors === 'function') updateChartColors(dark ? 'dark' : 'light');
}

/* ── Mobile nav ── */
function toggleMobileNav() {
  const nav = document.getElementById('mobile-nav');
  const icon = document.getElementById('mobile-nav-icon');
  const isOpen = !nav.classList.contains('hidden');
  nav.classList.toggle('hidden');
  icon.outerHTML = iconsax(isOpen ? 'hamberger-menu' : 'close-circle', icon.getAttribute('class'), 'mobile-nav-icon');
}

function handlePilihPerjadin() {
  closeMainDrawer();
  if (typeof aktifkanTab === 'function') {
    aktifkanTab('perjadin', true);
  }
  if (typeof bukaModalTrip === 'function') {
    bukaModalTrip();
  } else {
    window.location.href = '<?= base_url('admin?tab=perjadin&action=new') ?>';
  }
}
window.handlePilihPerjadin = handlePilihPerjadin;

/* ── User menu ── */
function toggleUserMenu() {
  document.getElementById('user-menu-dropdown')?.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
  const userDd = document.getElementById('user-menu-dropdown');
  const userWrap = document.getElementById('user-menu-wrapper');
  if (userDd && !userDd.classList.contains('hidden') && userWrap && !userWrap.contains(e.target)) {
    userDd.classList.add('hidden');
  }
});

/* ── Logout modal ── */
function confirmLogout(e) {
  if (e) e.preventDefault();
  openModal('modal-logout');
  return false;
}

/* ── Modal / drawer helpers ── */
function openModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }
function openPopup(id)  { openModal(id); }
function closePopup(id) { closeModal(id); }
window.openModal = openModal;
window.closeModal = closeModal;
window.openPopup = openPopup;
window.closePopup = closePopup;

/* ── Konfirmasi umum (reusable, ganti native confirm() browser) ── */
function tampilkanKonfirmasi(pesan, aksi) {
  document.getElementById('konfirmasi-text').textContent = pesan;
  const btn = document.getElementById('btn-konfirmasi-ya');
  btn.onclick = async function() { closeModal('modal-konfirmasi'); await aksi(); };
  openModal('modal-konfirmasi');
}
window.tampilkanKonfirmasi = tampilkanKonfirmasi;

function openMainDrawer() {
  document.getElementById('main-input-drawer')?.classList.remove('hidden');
}
function closeMainDrawer() {
  document.getElementById('main-input-drawer')?.classList.add('hidden');
}
window.openMainDrawer = openMainDrawer;
window.closeMainDrawer = closeMainDrawer;

/* ── Jenis data selector ── */
function selectJenisData(jenis) {
  currentJenisData = jenis;
  document.querySelectorAll('.jenis-card').forEach(c => {
    c.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/30', 'text-primary-700', 'dark:text-primary-300');
    c.classList.add('border-slate-200', 'dark:border-slate-600', 'text-slate-700', 'dark:text-slate-300');
  });
  const active = document.getElementById('jcard-' + jenis);
  if (active) {
    active.classList.remove('border-slate-200', 'dark:border-slate-600', 'text-slate-700', 'dark:text-slate-300');
    active.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/30', 'text-primary-700', 'dark:text-primary-300');
  }
  document.getElementById('single-jenis-val').value = jenis;

  const labelSumber = document.getElementById('label-sumber');
  const sumberWrap  = document.getElementById('field-sumber-wrap');
  const inputSumber = document.getElementById('input-sumber');
  const statusWrap  = document.getElementById('field-status-wrap');
  const diterimaWrap = document.getElementById('field-diterima-wrap');
  const kategoriSel = document.getElementById('drawer-kategori');

  const daftarKategori = (jenis === 'pengeluaran') ? KATEGORI_PENGELUARAN : KATEGORI_PEMASUKAN;
  kategoriSel.innerHTML = '<option value="">Pilih kategori...</option>' +
    daftarKategori.map(k => `<option value="${k}">${k}</option>`).join('');

  if (jenis === 'pemasukan') {
    sumberWrap.classList.remove('hidden');
    labelSumber.textContent = 'Sumber Dana';
    inputSumber.name = 'sumber';
    statusWrap.classList.remove('hidden');
    toggleDrawerDiterima(document.getElementById('drawer-status'));
  } else if (jenis === 'pengeluaran') {
    sumberWrap.classList.remove('hidden');
    labelSumber.textContent = 'Penerima / Tujuan';
    inputSumber.name = 'tujuan';
    statusWrap.classList.add('hidden');
    diterimaWrap.classList.add('hidden');
  } else {
    sumberWrap.classList.add('hidden');
    inputSumber.name = '';
    statusWrap.classList.add('hidden');
    diterimaWrap.classList.add('hidden');
  }
  document.getElementById('drawer-submit-text').textContent = 'Simpan Transaksi';
}

function toggleDrawerDiterima(sel) {
  const wrap = document.getElementById('field-diterima-wrap');
  if (sel.value === 'sebagian') {
    wrap.classList.remove('hidden');
  } else {
    wrap.classList.add('hidden');
    if (sel.value === 'diterima') syncDrawerDiterima();
  }
}

function syncDrawerDiterima() {
  const status = document.getElementById('drawer-status')?.value;
  if (status && status !== 'sebagian') {
    document.getElementById('drawer-jumlah-diterima').value = document.getElementById('drawer-jumlah').value;
  }
}

function submitMainDrawer() {
  document.getElementById('drawer-single-form').requestSubmit();
}

async function handleSingleSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('drawer-single-form');
  const fd = buildFormData(form);
  const jenis = currentJenisData;
  let url = GLOBAL_BASE + 'admin/keuangan/pemasukan';
  if (jenis === 'pengeluaran') url = GLOBAL_BASE + 'admin/keuangan/pengeluaran';

  const btn = document.getElementById('drawer-submit-btn');
  btn.disabled = true;
  try {
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      showToast(json.message, 'success');
      closeMainDrawer();
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(json.message || 'Gagal menyimpan', 'error');
    }
  } catch(err) {
    showToast('Terjadi kesalahan saat menyimpan', 'error');
  } finally {
    btn.disabled = false;
  }
}

/* ── Toast ── */
function showToast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const styles = {
    success: 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/40 dark:border-emerald-700 dark:text-emerald-200',
    error:   'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/40 dark:border-red-700 dark:text-red-200',
    warning: 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/40 dark:border-amber-700 dark:text-amber-200',
    info:    'bg-primary-50 border-primary-200 text-primary-800 dark:bg-primary-900/40 dark:border-primary-700 dark:text-primary-200',
  };
  const icons = { success: 'tick-circle', error: 'info-circle', info: 'info-circle', warning: 'warning-2' };
  const t = document.createElement('div');
  t.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lift text-sm animate-bounce-in ${styles[type] || styles.info}`;
  t.innerHTML = `${iconsax(icons[type] || 'info-circle', 'w-5 h-5 shrink-0')}<span class="flex-1">${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transition = 'opacity 300ms';
    setTimeout(() => t.remove(), 300);
  }, 4000);
}
window.showToast = showToast;

/* ── Animasi angka KPI "count up" saat dimuat — bikin dashboard kerasa lebih hidup ── */
function animateCountUp(el, duration = 900) {
  const raw = el.textContent;
  const match = raw.match(/[\d.,]+/);
  if (!match) return;
  const target = parseFloat(match[0].replace(/\./g, '').replace(',', '.'));
  if (!isFinite(target)) return;
  const prefix = raw.slice(0, match.index);
  const suffix = raw.slice(match.index + match[0].length);
  const start = performance.now();
  const ease = t => 1 - Math.pow(1 - t, 3);
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const val = Math.round(target * ease(p));
    el.textContent = prefix + val.toLocaleString('id-ID') + suffix;
    if (p < 1) requestAnimationFrame(tick);
    else el.textContent = raw;
  }
  requestAnimationFrame(tick);
}
window.animateCountUp = animateCountUp;
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.kpi-value').forEach(el => animateCountUp(el));
});

/* ── FORMAT RIBUAN (BUG FIXED) ─────────────────────────────────
   BUG SEBELUMNYA: `.value.replace(/\D/g, '')` juga membuang titik decimal
   dari nilai DB DECIMAL(15,2) — "1000000.00" → "100000000" (×100).
   Setelah beberapa save/edit, nominal jadi menggelembung.
   FIX: buang decimal trailing (.00 / ,00) DULU sebelum strip non-digit. */
function formatRibuan(el) {
  let s = (el.value || '').toString().trim();
  s = s.replace(/[.,]\d{1,2}$/, ''); // buang trailing decimal (.00 dsb)
  s = s.replace(/\D/g, '');           // baru strip non-digit lainnya
  el.value = s ? new Intl.NumberFormat('id-ID').format(parseInt(s, 10)) : '';
}
function unformatRibuan(val) {
  let s = (val || '').toString().trim();
  s = s.replace(/[.,]\d{1,2}$/, '');
  return s.replace(/\D/g, '');
}
/** Set nilai input-rupiah dari nilai DB (angka atau string decimal) dengan aman. */
function setRupiahValue(el, raw) {
  if (!el) return;
  const n = Math.round(parseFloat(raw) || 0);
  el.value = n > 0 ? new Intl.NumberFormat('id-ID').format(n) : '';
}
window.formatRibuan = formatRibuan;
window.unformatRibuan = unformatRibuan;
window.setRupiahValue = setRupiahValue;

document.addEventListener('input', function(e) {
  if (e.target.classList && e.target.classList.contains('input-rupiah')) {
    formatRibuan(e.target);
  }
});

function buildFormData(form) {
  const fd = new FormData(form);
  form.querySelectorAll('.input-rupiah').forEach(el => {
    if (el.name) fd.set(el.name, unformatRibuan(el.value));
  });
  return fd;
}
window.buildFormData = buildFormData;

/* ── Password toggle helper (global) ── */
window.togglePasswordVisibility = function(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isPw = input.type === 'password';
  input.type = isPw ? 'text' : 'password';
  const icon = btn.querySelector('svg');
  if (icon) {
    icon.outerHTML = iconsax(isPw ? 'eye-off' : 'eye', icon.getAttribute('class'));
  }
};

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

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  selectJenisData('pemasukan');
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
