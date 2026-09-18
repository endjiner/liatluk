<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<?php
$tahunSekarang = (int)date('Y');
$tahunOpsi = range($tahunSekarang, 2016);
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>

<!-- Hero -->
<section class="bg-primary-900 text-white overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 lg:px-6 py-8 lg:py-10">
    <div class="flex items-center justify-between gap-6">
      <div class="max-w-3xl">
        <h1 class="text-2xl lg:text-3xl font-bold leading-tight">Pengelolaan Keuangan Internal</h1>
        <p class="text-primary-100 mt-2 text-sm lg:text-base">
          Publikasi arus kas Balai Besar POM di Pangkal Pinang secara terbuka.
        </p>
      </div>
      <img src="https://img.icons8.com/clouds/500/wallet.png" alt="" class="hidden md:block w-28 lg:w-36 shrink-0">
    </div>
  </div>
</section>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-6 space-y-6">

  <!-- KPI Cards: 4 kartu sejajar (2 kolom di mobile, 4 kolom di desktop) -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
    <!-- Saldo Kas -->
    <div class="kpi p-3 sm:p-5 bg-primary-50/70 dark:bg-primary-900/20">
      <div class="kpi-label"><img src="<?= icons8('wallet') ?>" alt="" class="w-5 h-5"> Saldo Kas</div>
      <div class="kpi-value text-lg sm:text-2xl text-primary-700 dark:text-primary-300 text-currency">
        Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400">Selisih total pemasukan dan pengeluaran</div>
    </div>

    <!-- Dana Taktis Belum Dibayar -->
    <div class="kpi p-3 sm:p-5 bg-amber-50/70 dark:bg-amber-900/20">
      <div class="kpi-label"><?= iconsax('moneys', 'w-5 h-5 text-amber-600') ?> <span class="truncate">Dana Taktis Belum Dibayar</span></div>
      <div class="kpi-value text-lg sm:text-2xl text-amber-700 dark:text-amber-400 text-currency">
        Rp <?= number_format($danaTaktisBelumDibayar['total'], 0, ',', '.') ?>
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400">
        <?= $danaTaktisBelumDibayar['jumlah'] ?> peserta belum menyetor &middot;
        <a href="#dana-taktis" onclick="scrollKeDanaTaktis(event)" class="text-amber-600 hover:underline cursor-pointer">Lihat detail</a>
      </div>
    </div>

    <!-- Pemasukan with period picker -->
    <div class="kpi p-3 sm:p-5" data-kpi="pemasukan">
      <div class="kpi-label"><img src="<?= icons8('bullish') ?>" alt="" class="w-5 h-5"> <span class="truncate">Total Pemasukan</span></div>
      <div class="kpi-value text-lg sm:text-2xl text-emerald-700 dark:text-emerald-400 text-currency" id="kpi-pemasukan-value">
        Rp <?= number_format($totalPemasukan, 0, ',', '.') ?>
      </div>
      <?= view('public/_partials/kpi_period_picker', ['id' => 'pemasukan']) ?>
    </div>

    <!-- Pengeluaran with period picker -->
    <div class="kpi p-3 sm:p-5" data-kpi="pengeluaran">
      <div class="kpi-label"><img src="<?= icons8('bearish') ?>" alt="" class="w-5 h-5"> <span class="truncate">Total Pengeluaran</span></div>
      <div class="kpi-value text-lg sm:text-2xl text-red-700 dark:text-red-400 text-currency" id="kpi-pengeluaran-value">
        Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?>
      </div>
      <?= view('public/_partials/kpi_period_picker', ['id' => 'pengeluaran']) ?>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tren Keuangan Bulanan</h3>
        <select id="tren-tahun" class="form-control form-control-sm w-24">
          <?php foreach ($tahunOpsi as $t): ?>
          <option value="<?= $t ?>" <?= $t === $tahunSekarang ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="card-body">
        <canvas id="chart-tren" class="chart-canvas"></canvas>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kategori Pengeluaran</h3>
      </div>
      <div class="card-body">
        <div id="chart-kategori-wrap" class="flex flex-col items-center">
          <canvas id="chart-kategori" style="max-height:260px"></canvas>
        </div>
        <div id="chart-kategori-empty" class="hidden flex-col items-center justify-center text-center text-slate-400 dark:text-slate-500 py-10">
          <img src="<?= icons8('pie-chart', '3d-fluency', 96) ?>" alt="" class="w-12 h-12 mb-2 opacity-80">
          <p class="text-sm">Belum ada data pengeluaran.</p>
        </div>
      </div>
    </div>
  </div>



  <!-- Tab: Transaksi Keuangan / Perjalanan Dinas / Dana Taktis -->
  <div id="segment-tabs" class="segment w-full scroll-mt-24">
    <button type="button" id="tab-btn-transaksi" class="segment-btn active flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('transaksi')">
      <?= iconsax('receipt-item', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Transaksi</span>
    </button>
    <button type="button" id="tab-btn-perjadin" class="segment-btn flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('perjadin')">
      <?= iconsax('airplane', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Perjalanan Dinas</span>
    </button>
    <button type="button" id="tab-btn-dana-taktis" class="segment-btn flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('dana-taktis')">
      <?= iconsax('moneys', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Dana Taktis</span>
    </button>
  </div>

  <div id="panel-transaksi">
  <!-- Daftar Transaksi -->
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <?= iconsax('task-square', 'w-4 h-4') ?> Daftar Transaksi
      </h3>
      <span class="text-xs text-slate-500">Total: <span id="txn-total">-</span></span>
    </div>

    <!-- Filter -->
    <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
      <!-- Baris 1: Toggle tipe + search -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Toggle tipe styled -->
        <div class="flex items-center gap-1 p-0.5 bg-slate-200/70 dark:bg-slate-700/50 rounded-lg shrink-0">
          <button type="button" id="toggle-pemasukan"
            onclick="toggleTipeTxn('pemasukan')"
            class="txn-toggle-btn active-pemasukan inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all">
            <?= iconsax('trend-up', 'w-3 h-3') ?> Pemasukan
          </button>
          <button type="button" id="toggle-pengeluaran"
            onclick="toggleTipeTxn('pengeluaran')"
            class="txn-toggle-btn active-pengeluaran inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all">
            <?= iconsax('trend-down', 'w-3 h-3') ?> Pengeluaran
          </button>
        </div>
        <!-- hidden actual checkboxes untuk kompatibilitas JS -->
        <input type="checkbox" id="filter-pemasukan" checked class="hidden">
        <input type="checkbox" id="filter-pengeluaran" checked class="hidden">

        <!-- Search -->
        <div class="relative flex-1 min-w-[200px]">
          <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
            <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
          </span>
          <input type="text" id="filter-search" placeholder="Cari kategori, MAK, nomor ST..."
                 class="form-control form-control-sm pl-8 w-full">
        </div>
      </div>
      <!-- Baris 2: Bulan, Tahun, Per page -->
      <div class="flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex items-center gap-2 flex-wrap">
          <select id="filter-bulan" class="form-control form-control-sm w-auto">
            <option value="">Semua Bulan</option>
            <?php foreach ($namaBulan as $n => $nm): ?>
            <option value="<?= $n ?>"><?= $nm ?></option>
            <?php endforeach; ?>
          </select>
          <select id="filter-tahun" class="form-control form-control-sm w-auto">
            <option value="">Semua Tahun</option>
            <?php foreach ($tahunOpsi as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
          <span>Tampilkan</span>
          <select id="filter-perpage" class="form-control form-control-sm w-auto">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>baris</span>
        </div>
      </div>
    </div>

    <div class="sm:hidden flex items-center gap-1.5 px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">
      <?= iconsax('arrow-swap-horizontal', 'w-3 h-3') ?> Geser tabel untuk melihat kolom lainnya
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th class="w-12 text-center">No</th>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Sumber</th>
            <th>Tipe</th>
            <th class="text-right">Nominal</th>
            <th class="w-16 text-center">Detail</th>
          </tr>
        </thead>
        <tbody id="txn-tbody">
          <tr><td colspan="7" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div id="pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
  </div>
  </div><!-- /panel-transaksi -->

  <div id="panel-perjadin" class="hidden">
    <?php $namaBulanPerjadin = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar Perjalanan Dinas</h3>
        <span class="text-xs text-slate-500">Total: <span id="pd-total-perjadin">-</span></span>
      </div>
      <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
        <!-- Baris 1: Search + Status -->
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative flex-1 min-w-[200px]">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
            </span>
            <input type="text" id="filter-search-perjadin" oninput="jadwalkanMuatDaftarPerjadin()" placeholder="Nama, maksud, no surat tugas..." class="form-control form-control-sm pl-8 w-full">
          </div>
          <input type="hidden" id="filter-status-perjadin" value="">
          <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Perjalanan Dinas">
            <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
              <input type="checkbox" id="pd-pub-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterPub('pd-pub', 'belum')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-amber-500"></span>
              <span>Belum Lunas</span>
            </label>
            <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
              <input type="checkbox" id="pd-pub-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterPub('pd-pub', 'lunas')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-emerald-500"></span>
              <span>Lunas</span>
            </label>
          </div>
        </div>
        <!-- Baris 2: Bulan, Tahun, Per page -->
        <div class="flex flex-wrap items-center justify-between gap-2.5">
          <div class="flex items-center gap-2 flex-wrap">
            <select id="filter-bulan-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Bulan</option>
              <?php for ($b = 1; $b <= 12; $b++): ?>
              <option value="<?= $b ?>"><?= $namaBulanPerjadin[$b] ?></option>
              <?php endfor; ?>
            </select>
            <select id="filter-tahun-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Tahun</option>
              <?php $tahunSaatIni = (int)date('Y'); $daftarTahunPerjadin = $tahunListPerjadin; if (!in_array($tahunSaatIni, $daftarTahunPerjadin)) $daftarTahunPerjadin[] = $tahunSaatIni; rsort($daftarTahunPerjadin); ?>
              <?php foreach ($daftarTahunPerjadin as $th): ?>
              <option value="<?= $th ?>"><?= $th ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
            <span>Tampilkan</span>
            <select id="filter-per-page-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span>baris</span>
          </div>
        </div>
      </div>


      <div class="sm:hidden flex items-center gap-1.5 px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">
        <?= iconsax('arrow-swap-horizontal', 'w-3 h-3') ?> Geser tabel untuk melihat kolom lainnya
      </div>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="w-16 text-center">No. PD</th>
              <th class="min-w-[220px]">Perjalanan Dinas</th>
              <th class="min-w-[150px]">Nama Pelaksana</th>
              <th class="text-right whitespace-nowrap">Uang Harian</th>
              <th class="text-right whitespace-nowrap">Meeting Fullboard</th>
              <th class="text-right whitespace-nowrap">Meeting Fullday</th>
              <th class="text-right whitespace-nowrap">Uang Representasi</th>
              <th class="text-right whitespace-nowrap">Transport Lokal / Taksi</th>
              <th class="text-right whitespace-nowrap">BBM (Jalan Darat)</th>
              <th class="text-right whitespace-nowrap">Tiket Pesawat</th>
              <th class="text-right whitespace-nowrap">Hotel</th>
              <th class="text-right whitespace-nowrap">Total SPJ</th>
              <th class="text-right whitespace-nowrap">Dana Taktis</th>
              <th class="whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody id="pd-tbody-perjadin">
            <tr><td colspan="14" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>

      <div id="pd-pagination-wrap-perjadin" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
    </div>
  </div><!-- /panel-perjadin -->

  <div id="panel-dana-taktis" class="hidden">
    <?php $namaBulanDt = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

    <!-- Summary bar Dana Taktis (dihitung dari data yang sedang tampil) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-3">
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('money', 'w-4 h-4') ?> Total Uang Harian</div>
        <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency" id="dt-sum-uang-harian">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('receipt-2', 'w-4 h-4') ?> Total SPJ</div>
        <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency" id="dt-sum-spj">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('moneys', 'w-4 h-4 text-emerald-600') ?> Dana Taktis</div>
        <div class="kpi-value text-base sm:text-lg text-emerald-700 dark:text-emerald-400 text-currency" id="dt-sum-taktis">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4 bg-amber-50/70 dark:bg-amber-900/20">
        <div class="kpi-label text-[11px]"><?= iconsax('warning-2', 'w-4 h-4 text-amber-600') ?> Belum Dibayar</div>
        <div class="kpi-value text-base sm:text-lg text-amber-700 dark:text-amber-400 text-currency" id="dt-sum-belum">Rp 0</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar Dana Taktis</h3>
        <span class="text-xs text-slate-500">Total: <span id="dt-total-pub">-</span></span>
      </div>
      <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
        <!-- Baris 1: Search dengan autocomplete -->
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative flex-1 min-w-[200px]" id="dt-search-wrap">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
            </span>
            <input type="text" id="dt-filter-search-pub" oninput="dtPubJadwalkanMuat(); dtShowSuggestions()"
              onfocus="dtShowSuggestions()" onblur="setTimeout(dtHideSuggestions, 150)"
              placeholder="Cari nama, MAK, atau no. surat tugas..." class="form-control form-control-sm pl-8 w-full" autocomplete="off">
            <ul id="dt-suggestions" class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden text-xs"></ul>
          </div>
          <input type="hidden" id="dt-filter-status-pub" value="">
          <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Dana Taktis">
            <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
              <input type="checkbox" id="dt-pub-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterPub('dt-pub', 'belum')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-amber-500"></span>
              <span>Belum Lunas</span>
            </label>
            <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
              <input type="checkbox" id="dt-pub-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterPub('dt-pub', 'lunas')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-emerald-500"></span>
              <span>Lunas</span>
            </label>
          </div>
        </div>
        <!-- Baris 2: Bulan, Tahun, Per page -->
        <div class="flex flex-wrap items-center justify-between gap-2.5">
          <div class="flex items-center gap-2 flex-wrap">
            <select id="dt-filter-bulan-pub" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Bulan</option>
              <?php for ($b = 1; $b <= 12; $b++): ?>
              <option value="<?= $b ?>"><?= $namaBulanDt[$b] ?></option>
              <?php endfor; ?>
            </select>
            <select id="dt-filter-tahun-pub" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Tahun</option>
              <?php $tahunSaatIniDtPub = (int)date('Y'); $daftarTahunDtPub = $tahunListPerjadin; if (!in_array($tahunSaatIniDtPub, $daftarTahunDtPub)) $daftarTahunDtPub[] = $tahunSaatIniDtPub; rsort($daftarTahunDtPub); ?>
              <?php foreach ($daftarTahunDtPub as $th): ?>
              <option value="<?= $th ?>"><?= $th ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
            <span>Tampilkan</span>
            <select id="dt-filter-perpage-pub" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span>baris</span>
          </div>
        </div>
      </div>

      <div class="sm:hidden flex items-center gap-1.5 px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">
        <?= iconsax('arrow-swap-horizontal', 'w-3 h-3') ?> Geser tabel untuk melihat kolom lainnya
      </div>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="w-12 text-center">No</th>
              <th class="w-20 text-center">No. PD</th>
              <th>Nama Pegawai</th>
              <th>Perjalanan Dinas</th>
              <th>No. ST / MAK / Tgl</th>
              <th class="text-right">Uang Harian</th>
              <th class="text-right">Total SPJ</th>
              <th class="text-right">Dana Taktis</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="dt-tbody-pub">
            <tr><td colspan="9" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>

      <div id="dt-pagination-wrap-pub" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
    </div>
  </div><!-- /panel-dana-taktis -->

</div>

<!-- ═══════ MODAL: DETAIL TRANSAKSI ═══════ -->
<div id="modal-detail-txn" class="hidden">
  <div class="modal-backdrop" onclick="closeDetailModal()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('document-text', 'w-5 h-5 text-primary-600') ?> Detail Transaksi</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="detail-subtitle-pub"></p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeDetailModal()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm" id="detail-list-pub"></dl>
        <div class="mt-4" id="detail-bukti-pub"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeDetailModal()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: DETAIL PERJALANAN DINAS ═══════ -->
<div id="modal-detail-perjadin-pub" class="hidden">
  <div class="modal-backdrop" onclick="closePerjadinModalPub()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg" style="max-width:48rem">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('airplane', 'w-5 h-5 text-primary-600') ?> Detail Perjalanan Dinas</h3>
          <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap" id="perjadin-pub-subtitle"></div>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closePerjadinModalPub()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body space-y-4" id="perjadin-pub-body" style="max-height:75vh;overflow-y:auto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closePerjadinModalPub()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: DETAIL DANA TAKTIS ═══════ -->
<div id="modal-detail-dt-pub" class="hidden">
  <div class="modal-backdrop" onclick="closeDtModalPub()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('moneys', 'w-5 h-5 text-primary-600') ?> Detail Dana Taktis</h3>
          <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap" id="dt-pub-subtitle"></div>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeDtModalPub()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body" id="dt-pub-body" style="max-height:75vh;overflow-y:auto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeDtModalPub()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE_URL = '<?= base_url() ?>';
const KPI_ENDPOINT_PUB = '<?= base_url('kpi-periode') ?>';

/* ── Chart ── */
let chartTren, chartKat;
function chartColors() {
  const dark = document.documentElement.classList.contains('dark');
  return { text: dark ? '#cbd5e1' : '#475569', grid: dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.2)' };
}
function buildTren(pemasukan, pengeluaran) {
  const colors = chartColors();
  if (chartTren) chartTren.destroy();
  chartTren = new Chart(document.getElementById('chart-tren'), {
    type: 'bar',
    data: {
      labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
      datasets: [
        { label: 'Pemasukan', data: pemasukan, backgroundColor: '#059669', borderRadius: 6 },
        { label: 'Pengeluaran', data: pengeluaran, backgroundColor: '#dc2626', borderRadius: 6 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 11 } } } },
      scales: {
        y: { ticks: { color: colors.text, callback: v => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v) }, grid: { color: colors.grid } },
        x: { ticks: { color: colors.text }, grid: { display: false } }
      }
    }
  });
}
function buildKat() {
  const colors = chartColors();
  const data = <?= $pieData ?>;
  const wrap = document.getElementById('chart-kategori-wrap');
  const canvas = document.getElementById('chart-kategori');
  const empty = document.getElementById('chart-kategori-empty');
  if (!data.length) {
    if (wrap) wrap.classList.add('hidden');
    empty.classList.remove('hidden');
    empty.classList.add('flex');
    if (chartKat) { chartKat.destroy(); chartKat = null; }
    return;
  }
  if (wrap) wrap.classList.remove('hidden');
  empty.classList.add('hidden');
  empty.classList.remove('flex');
  if (chartKat) chartKat.destroy();
  chartKat = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: data.map(d => d.kategori),
      datasets: [{ data: data.map(d => d.total), backgroundColor: ['#1e5fbe','#059669','#dc2626','#d97706','#7c3aed','#0891b2','#65a30d','#c026d3'] }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 11 }, padding: 12, boxWidth: 14 } } }
    }
  });
}

let lastTrenPemasukan = [], lastTrenPengeluaran = [];

async function loadTren(tahun) {
  const res = await fetch(BASE_URL + 'chart-tren?tahun=' + tahun);
  const data = await res.json();
  lastTrenPemasukan = data.pemasukan || [];
  lastTrenPengeluaran = data.pengeluaran || [];
  buildTren(lastTrenPemasukan, lastTrenPengeluaran);
}

document.getElementById('tren-tahun').addEventListener('change', e => loadTren(e.target.value));

// Ganti tema cukup gambar ulang chart dari data yang sudah ada di memori, tidak perlu
// fetch ulang ke server, datanya tidak berubah, yang berubah cuma warnanya.
function updateChartColors() {
  buildKat();
  buildTren(lastTrenPemasukan, lastTrenPengeluaran);
}
window.updateChartColors = updateChartColors;

/* ── KPI Period picker (public) ── */
document.querySelectorAll('[data-kpi]').forEach(card => {
  const kpiId = card.dataset.kpi;
  const valueEl = document.getElementById('kpi-' + kpiId + '-value');
  const tabs = card.querySelectorAll('.kpi-tab');
  const yearSel = card.querySelector('.kpi-year');
  const monthSel = card.querySelector('.kpi-month');
  tabs.forEach(tab => {
    tab.addEventListener('click', async () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const scope = tab.dataset.scope;
      if (yearSel) yearSel.parentElement.classList.toggle('hidden', scope === 'semua');
      if (monthSel) monthSel.parentElement.classList.toggle('hidden', scope !== 'bulan');
      await refreshKpi(kpiId, valueEl, scope, yearSel?.value, monthSel?.value);
    });
  });
  if (yearSel) yearSel.addEventListener('change', () => reload(card, kpiId, valueEl));
  if (monthSel) monthSel.addEventListener('change', () => reload(card, kpiId, valueEl));
});
function reload(card, kpiId, valueEl) {
  const scope = card.querySelector('.kpi-tab.active')?.dataset.scope || 'semua';
  refreshKpi(kpiId, valueEl, scope, card.querySelector('.kpi-year')?.value, card.querySelector('.kpi-month')?.value);
}
async function refreshKpi(kpiId, valueEl, scope, tahun, bulan) {
  valueEl.style.opacity = '0.5';
  try {
    const url = KPI_ENDPOINT_PUB + '?scope=' + scope + '&tahun=' + (tahun || new Date().getFullYear()) + '&bulan=' + (bulan || (new Date().getMonth()+1));
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
      const total = kpiId === 'pemasukan' ? data.total_pemasukan : data.total_pengeluaran;
      valueEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }
  } finally { valueEl.style.opacity = '1'; }
}

/* ── Toggle tipe transaksi (styled button, sync ke hidden checkbox) ── */
const TXN_TOGGLE_ACTIVE = {
  pemasukan:    'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 shadow-sm',
  pengeluaran:  'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 shadow-sm',
};
const TXN_TOGGLE_INACTIVE = 'text-slate-500 dark:text-slate-400 hover:text-slate-700';

function toggleTipeTxn(tipe) {
  const btn = document.getElementById('toggle-' + tipe);
  const cb  = document.getElementById('filter-' + tipe);
  const active = !cb.checked; // toggle
  cb.checked = active;
  btn.className = btn.className
    .replace(TXN_TOGGLE_ACTIVE[tipe], '').replace(TXN_TOGGLE_INACTIVE, '').trim();
  btn.className += ' txn-toggle-btn active-' + tipe + ' inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all ' +
    (active ? TXN_TOGGLE_ACTIVE[tipe] : TXN_TOGGLE_INACTIVE);
  currentPage = null;
  refreshTxn();
}

// Init toggle state on load
document.addEventListener('DOMContentLoaded', () => {
  ['pemasukan','pengeluaran'].forEach(t => {
    const btn = document.getElementById('toggle-' + t);
    if (btn) btn.className = 'txn-toggle-btn active-' + t + ' inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all ' + TXN_TOGGLE_ACTIVE[t];
  });
});

/* ── Transaksi (dengan pagination) ──
   Nomor baris selalu urut dari transaksi paling awal (dihitung backend). currentPage=null
   berarti "biarkan backend pilih default" — yaitu halaman TERAKHIR (transaksi terbaru),
   supaya begitu dibuka tetap yang paling baru yang terlihat duluan. */
let currentPage = null;
let txnRequestSeq = 0;

function refreshTxn() {
  const showP = document.getElementById('filter-pemasukan').checked;
  const showE = document.getElementById('filter-pengeluaran').checked;
  const perPage = document.getElementById('filter-perpage').value;
  const search = document.getElementById('filter-search').value;
  const bulan = document.getElementById('filter-bulan').value;
  const tahun = document.getElementById('filter-tahun').value;

  const params = new URLSearchParams({
    show_pemasukan: showP ? '1' : '0',
    show_pengeluaran: showE ? '1' : '0',
    per_page: perPage,
    search, bulan, tahun,
  });
  if (currentPage) params.set('page', currentPage);

  const tbody = document.getElementById('txn-tbody');
  const seq = ++txnRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="7" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  fetch(BASE_URL + 'transaksi/ajax?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (seq !== txnRequestSeq) return;
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      currentPage = data.page || 1;
      renderTxn(data.data || []);
      document.getElementById('txn-total').textContent = new Intl.NumberFormat('id-ID').format(data.total || 0);
      renderPagination(data.total || 0, parseInt(perPage), currentPage);
    })
    .catch(() => {
      if (seq !== txnRequestSeq) return;
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
    });
}

function renderTxn(rows) {
  const tbody = document.getElementById('txn-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(r => {
    const isP = r.tipe === 'pemasukan';
    const nominal = isP ? (parseFloat(r.jumlah_diterima) || parseFloat(r.jumlah)) : parseFloat(r.jumlah);
    const dateFormatted = new Date(r.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const badge = isP
      ? '<span class="badge badge-success">' + iconsax('trend-up', 'w-3 h-3') + 'Pemasukan</span>'
      : '<span class="badge badge-danger">' + iconsax('trend-down', 'w-3 h-3') + 'Pengeluaran</span>';
    const rowJson = JSON.stringify(r).replace(/'/g, "&#39;");
    return `<tr class="cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors" onclick='showDetailPub(${rowJson})'>
      <td class="text-center text-slate-500">${r.nomor ?? '-'}</td>
      <td class="whitespace-nowrap">${dateFormatted}</td>
      <td class="min-w-[120px] max-w-[200px] break-words whitespace-normal">
        <div class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.kategori || '-')}</div>
        ${r.kode_mak ? `<div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${escapeHtml(r.kode_mak)}</div>` : ''}
      </td>
      <td class="min-w-[120px] max-w-[180px] break-words whitespace-normal">${escapeHtml(r.sumber || r.tujuan || '-')}</td>
      <td>${badge}</td>
      <td class="text-right font-medium text-currency ${isP ? 'text-income' : 'text-expense'}">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(nominal))}</td>
      <td class="text-center" onclick="event.stopPropagation()">
        <button type="button" onclick='event.stopPropagation(); showDetailPub(${rowJson})' class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Detail">
          ${iconsax('eye', 'w-4 h-4')}
        </button>
      </td>
    </tr>`;
  }).join('');
}

function renderPagination(total, perPage, page) {
  const wrap = document.getElementById('pagination-wrap');
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (total === 0) { wrap.innerHTML = ''; return; }

  const from = (page - 1) * perPage + 1;
  const to = Math.min(total, page * perPage);

  let btns = '';
  const pageBtn = (p, label, active = false, disabled = false) => {
    if (disabled) return `<button disabled class="px-3 py-1.5 text-xs rounded-md text-slate-400 cursor-not-allowed">${label}</button>`;
    if (active)   return `<button class="px-3 py-1.5 text-xs rounded-md bg-primary-600 text-white font-medium">${label}</button>`;
    return `<button onclick="gotoPage(${p})" class="px-3 py-1.5 text-xs rounded-md text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">${label}</button>`;
  };

  btns += pageBtn(page - 1, '‹ Sebelumnya', false, page <= 1);

  // Show page numbers with smart truncation
  const pages = getPageNumbers(page, totalPages);
  pages.forEach(p => {
    if (p === '...') {
      btns += '<span class="px-2 text-xs text-slate-400">…</span>';
    } else {
      btns += pageBtn(p, p, p === page);
    }
  });

  btns += pageBtn(page + 1, 'Selanjutnya ›', false, page >= totalPages);

  wrap.innerHTML = `
    <div class="text-xs text-slate-600 dark:text-slate-400">Menampilkan ${from}–${to} dari ${new Intl.NumberFormat('id-ID').format(total)} baris</div>
    <div class="flex items-center gap-1">${btns}</div>
  `;
}

function getPageNumbers(current, total) {
  if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
}

function gotoPage(p) {
  currentPage = p;
  refreshTxn();
  document.getElementById('txn-tbody').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
window.gotoPage = gotoPage;

/* ── Debounced filter changes ── */
let filterTimer;
function scheduleFilterReset() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => { currentPage = null; refreshTxn(); }, 350);
}
['filter-pemasukan','filter-pengeluaran','filter-bulan','filter-tahun','filter-perpage'].forEach(id => {
  document.getElementById(id).addEventListener('change', scheduleFilterReset);
});
document.getElementById('filter-search').addEventListener('input', scheduleFilterReset);

/* ── Detail modal ── */
function fmtRp(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(v) || 0)); }
function fmtDate(s) { if (!s) return '-'; return new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); }
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

function statusBadgePub(s) {
  const map = { diterima: ['badge-success','Diterima'], sebagian: ['badge-warning','Sebagian'], belum_diterima: ['badge-danger','Belum Diterima'] };
  const [cls, label] = map[s] || ['badge-muted', s || '-'];
  return `<span class="badge ${cls}">${label}</span>`;
}
function buktiHTML(url, filename) {
  if (!filename) return '<div class="text-sm text-slate-500">Tidak ada bukti terlampir.</div>';
  const ext = (filename.split('.').pop() || '').toLowerCase();
  if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
    return `<a href="${url}" target="_blank"><img src="${url}" alt="Bukti" class="max-h-64 rounded-lg border border-slate-200 shadow-soft"><div class="text-xs text-primary-600 hover:underline mt-1">Buka di tab baru</div></a>`;
  }
  if (ext === 'pdf') {
    return `<a href="${url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border border-slate-200 hover:border-primary-500 hover:bg-primary-50/50 transition">
              ${iconsax('document-text', 'w-8 h-8 text-red-600')}
              <div><div class="font-medium text-sm">${escapeHtml(filename)}</div><div class="text-xs text-primary-600">Klik untuk buka PDF</div></div>
            </a>`;
  }
  return `<a href="${url}" target="_blank" class="text-primary-600 hover:underline text-sm">${escapeHtml(filename)}</a>`;
}

function showDetailPub(row) {
  const isP = row.tipe === 'pemasukan';
  document.getElementById('detail-subtitle-pub').textContent = isP ? 'Pemasukan' : 'Pengeluaran';
  const items = [
    ['Tanggal', fmtDate(row.tanggal)],
    ['Kategori', row.kategori ? escapeHtml(row.kategori) : '-'],
    ['Nominal', `<span class="font-semibold ${isP ? 'text-income' : 'text-expense'}">${fmtRp(row.jumlah)}</span>`],
  ];
  if (isP) {
    items.push(['Jumlah Diterima', fmtRp(row.jumlah_diterima)]);
    items.push(['Status Dana', statusBadgePub(row.status_dana)]);
    if (row.sumber) items.push(['Sumber', escapeHtml(row.sumber)]);
  } else {
    if (row.tujuan) items.push(['Tujuan / Penerima', escapeHtml(row.tujuan)]);
  }
  if (row.kode_mak) items.push(['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(row.kode_mak)}</span>`]);
  if (row.no_surat_tugas) items.push(['No. Surat Tugas', escapeHtml(row.no_surat_tugas)]);
  if (row.no_spm) items.push(['No. SPM', escapeHtml(row.no_spm)]);
  if (row.keterangan) items.push(['Keterangan', escapeHtml(row.keterangan)]);

  document.getElementById('detail-list-pub').innerHTML = items.map(([k, v]) =>
    `<div>
       <dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt>
       <dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd>
     </div>`
  ).join('');

  const buktiWrap = document.getElementById('detail-bukti-pub');
  if (row.file_bukti) {
    const url = BASE_URL + 'uploads/bukti/' + row.file_bukti;
    buktiWrap.innerHTML = `<div class="pt-4 border-t border-slate-200 dark:border-slate-700">
      <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Bukti Transaksi</div>
      ${buktiHTML(url, row.file_bukti)}
    </div>`;
  } else {
    buktiWrap.innerHTML = '';
  }

  document.getElementById('modal-detail-txn').classList.remove('hidden');
}
window.showDetailPub = showDetailPub;
function closeDetailModal() { document.getElementById('modal-detail-txn').classList.add('hidden'); }
window.closeDetailModal = closeDetailModal;

/* ── Tab: Transaksi Keuangan / Perjalanan Dinas / Dana Taktis ── */
let perjadinSudahDimuat = false;
let danaTaktisSudahDimuat = false;
function aktifkanTab(nama, scroll = false) {
  ['transaksi', 'perjadin', 'dana-taktis'].forEach(n => {
    const p = document.getElementById('panel-' + n);
    const b = document.getElementById('tab-btn-' + n);
    if (p) p.classList.toggle('hidden', n !== nama);
    if (b) b.classList.toggle('active', n === nama);
  });
  if (nama === 'perjadin' && !perjadinSudahDimuat) {
    perjadinSudahDimuat = true;
    muatDaftarTripPerjadin();
  }
  if (nama === 'dana-taktis' && !danaTaktisSudahDimuat) {
    danaTaktisSudahDimuat = true;
    muatDaftarDanaTaktisPub();
  }
  if (scroll) {
    setTimeout(() => {
      const target = document.getElementById('segment-tabs') || document.getElementById('tab-btn-' + nama) || document.getElementById('panel-' + nama);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 50);
  }
}
function scrollKeDanaTaktis(e) {
  if (e) e.preventDefault();
  aktifkanTab('dana-taktis', true);
  if (history.replaceState) {
    history.replaceState(null, '', '#dana-taktis');
  }
}
window.aktifkanTab = aktifkanTab;
window.scrollKeDanaTaktis = scrollKeDanaTaktis;

/* ── Handler Toggle Checkbox Filter Status (Non-default style chip) ── */
function toggleStatusFilterPub(prefix, changedType) {
  const chkBelum = document.getElementById(prefix + '-chk-belum');
  const chkLunas = document.getElementById(prefix + '-chk-lunas');
  if (!chkBelum || !chkLunas) return;

  // Jika kedua checkbox dicoba di-uncheck, jangan biarkan kosong: reset keduanya jadi checked (semua)
  if (!chkBelum.checked && !chkLunas.checked) {
    chkBelum.checked = true;
    chkLunas.checked = true;
  }

  // Sinkronkan class styling .is-checked
  const chipBelum = chkBelum.closest('.status-chip');
  const chipLunas = chkLunas.closest('.status-chip');
  if (chipBelum) chipBelum.classList.toggle('is-checked', chkBelum.checked);
  if (chipLunas) chipLunas.classList.toggle('is-checked', chkLunas.checked);

  // Tentukan parameter status
  let statusVal = '';
  if (chkBelum.checked && !chkLunas.checked) statusVal = 'belum';
  else if (!chkBelum.checked && chkLunas.checked) statusVal = 'lunas';
  else statusVal = ''; // Keduanya checked = Semua Status

  if (prefix === 'dt-pub') {
    const input = document.getElementById('dt-filter-status-pub');
    if (input) input.value = statusVal;
    muatDaftarDanaTaktisPub(1);
  } else if (prefix === 'pd-pub') {
    const input = document.getElementById('filter-status-perjadin');
    if (input) input.value = statusVal;
    muatDaftarTripPerjadin(1);
  }
}
window.toggleStatusFilterPub = toggleStatusFilterPub;

/* ── Perjalanan Dinas (tabel datar 1 baris = 1 peserta, filter & pagination via AJAX) ── */
const rupiahPd = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
let filterDebouncePerjadin = null;
let halamanPerjadinSaatIni = 1;
function jadwalkanMuatDaftarPerjadin() {
  clearTimeout(filterDebouncePerjadin);
  filterDebouncePerjadin = setTimeout(() => muatDaftarTripPerjadin(1), 350);
}
// Nomor urut request — kalau user ketik cepat, respons yang lebih lama (mis. dari huruf
// pertama) bisa balik BELAKANGAN dari respons huruf terakhir dan menimpa hasil yang lebih
// baru dengan yang basi. Cuma respons dari request PALING TERAKHIR yang boleh dirender.
let tripPerjadinRequestSeq = 0;
let perjadinHalamanPub = null;

async function muatDaftarTripPerjadin(page) {
  if (page !== undefined && page !== null) {
    perjadinHalamanPub = page;
  }
  const params = new URLSearchParams();
  const search = document.getElementById('filter-search-perjadin')?.value || '';
  const status = document.getElementById('filter-status-perjadin')?.value || '';
  const tahun  = document.getElementById('filter-tahun-perjadin')?.value || '';
  const bulan  = document.getElementById('filter-bulan-perjadin')?.value || '';
  const perPage = document.getElementById('filter-per-page-perjadin')?.value || '10';

  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (tahun) params.set('tahun', tahun);
  if (bulan) params.set('bulan', bulan);
  params.set('per_page', perPage);
  if (perjadinHalamanPub) params.set('page', perjadinHalamanPub);

  const tbody = document.getElementById('pd-tbody-perjadin');
  const seq = ++tripPerjadinRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="14" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res = await fetch(BASE_URL + 'perjalanan-dinas/ajax?' + params.toString());
    const json = await res.json();
    if (seq !== tripPerjadinRequestSeq) return; // ada request lebih baru yang menyusul, respons ini basi
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    perjadinHalamanPub = json.page;
    renderTripTablePerjadin(json.data);
    renderPaginasiHalaman(document.getElementById('pd-pagination-wrap-perjadin'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'peserta', onPageChange: muatDaftarTripPerjadin,
    });
    document.getElementById('pd-total-perjadin').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== tripPerjadinRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}

function renderTripTablePerjadin(rows) {
  const tbody = document.getElementById('pd-tbody-perjadin');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  // Hitung jumlah peserta per perjalanan dinas untuk merge (rowspan)
  const tripCounts = {};
  rows.forEach(r => {
    tripCounts[r.perjalanan_dinas_id] = (tripCounts[r.perjalanan_dinas_id] || 0) + 1;
  });

  let lastTripId = null;
  let noTrip = 0;
  tbody.innerHTML = rows.map(r => {
    const tripBaru = r.perjalanan_dinas_id !== lastTripId;
    if (tripBaru) { lastTripId = r.perjalanan_dinas_id; noTrip++; }

    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const totalTiket = (r.tiket || []).reduce((s, t) => s + (parseFloat(t.harga_tiket) || 0), 0);
    const totalHotel = r.hotel ? ((parseFloat(r.hotel.total_bill) || 0) + (parseFloat(r.hotel.total_biaya_30persen) || 0)) : 0;
    const taktisVal = parseFloat(r.dana_taktis) || 0;
    const isZeroTaktis = taktisVal <= 0;
    const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
    const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
    const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
    const statusBadge = isZeroTaktis
      ? `<span class="badge badge-success" title="Tanpa tagihan dana taktis">Lunas</span>`
      : (isLunas
        ? `<span class="badge badge-success">Lunas</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
        : (isSebagian
          ? `<span class="badge badge-info" title="Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)}">Sebagian</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
          : '<span class="badge badge-warning">Belum Lunas</span>'));

    const count = tripCounts[r.perjalanan_dinas_id] || 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80 dark:border-amber-700/80 shadow-xs">${escapeHtml(r.no_pd)}</span>`
          : `<span class="text-sm font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(r.no_pd)}</span>`)
      : `<span class="text-xs text-slate-400 font-medium">${noTrip}</span>`;

    const selTrip = tripBaru ? `
      <td rowspan="${count}" class="align-top text-center bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">${nomorPdHtml}</td>
      <td rowspan="${count}" class="align-top min-w-[200px] max-w-[320px] bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">
        <p class="font-medium text-slate-800 dark:text-slate-100 break-words whitespace-normal" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 break-words">${escapeHtml(r.no_surat_tugas || '-')} &middot; ${tgl}</p>
        <p class="text-[11px] font-mono text-slate-600 dark:text-slate-300 mt-0.5 break-words"><span class="font-semibold text-slate-400 dark:text-slate-500">MAK:</span> ${escapeHtml(r.kode_mak || '-')}${r.no_spm ? ` &middot; <span class="font-semibold text-slate-400 dark:text-slate-500">SPM:</span> ${escapeHtml(r.no_spm)}` : ''}</p>
      </td>` : '';

    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    const rowEncoded = encodeURIComponent(JSON.stringify(r));
    return `<tr class="${tripBaru ? 'border-t-2 border-slate-200 dark:border-slate-700' : 'border-t border-slate-100 dark:border-slate-800/80'} ${rowBgClass} cursor-pointer transition-colors" onclick="showPerjadinModalPub('${rowEncoded}')">
      ${selTrip}
      <td class="font-medium text-slate-700 dark:text-slate-200 min-w-[140px] max-w-[220px] break-words whitespace-normal" title="${escapeHtml(r.nama_peserta)}">${escapeHtml(r.nama_peserta)}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_harian) > 0 ? rupiahPd(r.uang_harian) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullboard) > 0 ? rupiahPd(r.meeting_fullboard) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullday) > 0 ? rupiahPd(r.meeting_fullday) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_representasi) > 0 ? rupiahPd(r.uang_representasi) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.transport_lokal) > 0 ? rupiahPd(r.transport_lokal) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.bbm) > 0 ? rupiahPd(r.bbm) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${(r.tiket || []).length > 0 ? ((r.tiket.length) + 'x &middot; ' + rupiahPd(totalTiket)) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${r.hotel ? (escapeHtml(r.hotel.nama_hotel || 'Hotel') + '<br><span class="text-xs">' + rupiahPd(totalHotel) + '</span>') : '-'}</td>
      <td class="text-right text-currency font-semibold whitespace-nowrap">${rupiahPd(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600 whitespace-nowrap">${rupiahPd(r.dana_taktis)}</td>
      <td class="whitespace-nowrap">${statusBadge}</td>
    </tr>`;
  }).join('');
}


/* ── Dana Taktis (tabel datar semua pegawai, filter & pagination via AJAX) ── */
let dtPubFilterDebounce = null;
function dtPubJadwalkanMuat() {
  clearTimeout(dtPubFilterDebounce);
  dtPubFilterDebounce = setTimeout(() => muatDaftarDanaTaktisPub(1), 350);
}

/* Autocomplete suggestions untuk search dana taktis */
let dtSuggestionCache = []; // [{label, value}] — diisi saat data masuk
function dtShowSuggestions() {
  const q = document.getElementById('dt-filter-search-pub').value.trim().toLowerCase();
  const ul = document.getElementById('dt-suggestions');
  if (!q || q.length < 2 || !dtSuggestionCache.length) { ul.classList.add('hidden'); return; }
  const matches = dtSuggestionCache.filter(s => s.toLowerCase().includes(q)).slice(0, 8);
  if (!matches.length) { ul.classList.add('hidden'); return; }
  ul.innerHTML = matches.map(s =>
    `<li class="px-3 py-2 cursor-pointer hover:bg-primary-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
         onmousedown="dtPickSuggestion('${escapeHtml(s).replace(/'/g, "&#39;")}')">${escapeHtml(s)}</li>`
  ).join('');
  ul.classList.remove('hidden');
}
function dtHideSuggestions() { document.getElementById('dt-suggestions').classList.add('hidden'); }
function dtPickSuggestion(val) {
  document.getElementById('dt-filter-search-pub').value = val;
  dtHideSuggestions();
  muatDaftarDanaTaktisPub(1);
}

let dtPubRequestSeq = 0;
let dtHalamanPub = null;

async function muatDaftarDanaTaktisPub(page) {
  if (page !== undefined && page !== null) {
    dtHalamanPub = page;
  }
  const params = new URLSearchParams();
  const search = document.getElementById('dt-filter-search-pub').value;
  const status = document.getElementById('dt-filter-status-pub').value;
  const bulan = document.getElementById('dt-filter-bulan-pub').value;
  const tahun = document.getElementById('dt-filter-tahun-pub').value;
  const perPage = document.getElementById('dt-filter-perpage-pub').value;
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (bulan) params.set('bulan', bulan);
  if (tahun) params.set('tahun', tahun);
  params.set('per_page', perPage);
  if (dtHalamanPub) params.set('page', dtHalamanPub);

  const tbody = document.getElementById('dt-tbody-pub');
  const seq = ++dtPubRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="9" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res = await fetch(BASE_URL + 'perjalanan-dinas/dana-taktis/ajax?' + params.toString());
    const json = await res.json();
    if (seq !== dtPubRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    dtHalamanPub = json.page;
    renderDanaTaktisTablePub(json.data, json.page, json.per_page);
    dtUpdateSummary(json.summary, json.data);
    renderPaginasiHalaman(document.getElementById('dt-pagination-wrap-pub'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'baris', onPageChange: muatDaftarDanaTaktisPub,
    });
    document.getElementById('dt-total-pub').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== dtPubRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}

function dtUpdateSummary(summary, rows) {
  if (summary) {
    document.getElementById('dt-sum-uang-harian').textContent = fmtRp(summary.sum_uang_harian);
    document.getElementById('dt-sum-spj').textContent         = fmtRp(summary.sum_total_spj);
    document.getElementById('dt-sum-taktis').textContent      = fmtRp(summary.sum_dana_taktis);
    document.getElementById('dt-sum-belum').textContent       = fmtRp(summary.sum_belum_dibayar);
    return;
  }
  let sumUH = 0, sumSpj = 0, sumTaktis = 0, sumBelum = 0;
  (rows || []).forEach(r => {
    sumUH     += parseFloat(r.uang_harian)  || 0;
    sumSpj    += parseFloat(r.total_spj)    || 0;
    sumTaktis += parseFloat(r.dana_taktis)  || 0;
    const isLunas = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
    if (!isLunas) {
      const setor = parseFloat(r.jumlah_disetor) || 0;
      sumBelum += (parseFloat(r.dana_taktis) || 0) - setor;
    }
  });
  document.getElementById('dt-sum-uang-harian').textContent = fmtRp(sumUH);
  document.getElementById('dt-sum-spj').textContent = fmtRp(sumSpj);
  document.getElementById('dt-sum-taktis').textContent = fmtRp(sumTaktis);
  document.getElementById('dt-sum-belum').textContent = fmtRp(sumBelum);
}

function renderDanaTaktisTablePub(rows, page, perPage) {
  // Update suggestion cache dari nama, no. surat tugas & MAK
  const namaSet = new Set(dtSuggestionCache);
  rows.forEach(r => {
    if (r.nama_peserta)   namaSet.add(r.nama_peserta);
    if (r.no_surat_tugas) namaSet.add(r.no_surat_tugas);
    if (r.kode_mak)       namaSet.add(r.kode_mak);
  });
  dtSuggestionCache = [...namaSet];

  const tbody = document.getElementById('dt-tbody-pub');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  const curPage = page || dtHalamanPub || 1;
  const curPerPage = perPage || 10;
  tbody.innerHTML = rows.map((r, idx) => {
    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const isLunas = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
    const isSebagian = r.status_lunas === 'sebagian';
    const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
    const statusBadge = isLunas
      ? `<span class="badge badge-success">Lunas</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
      : isSebagian
      ? `<span class="badge badge-info" title="Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)}">Sebagian</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
      : '<span class="badge badge-warning">Belum Lunas</span>';
    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    const rowNum = (curPage - 1) * curPerPage + idx + 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80">${escapeHtml(r.no_pd)}</span>`
          : `<span class="text-xs font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(r.no_pd)}</span>`)
      : '<span class="text-xs text-slate-400">-</span>';

    const rowEncoded = encodeURIComponent(JSON.stringify(r));
    return `<tr class="${rowBgClass} cursor-pointer transition-colors" onclick="showDtModalPub('${rowEncoded}')">
      <td class="text-center text-xs text-slate-500">${rowNum}</td>
      <td class="text-center font-medium">${nomorPdHtml}</td>
      <td class="font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap">${escapeHtml(r.nama_peserta)}</td>
      <td class="min-w-[180px] max-w-[300px] break-words whitespace-normal" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</td>
      <td class="text-xs">
        <div class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_surat_tugas || '-')}</div>
        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${escapeHtml(r.kode_mak || '-')}</div>
        <div class="text-[11px] text-slate-400">${tgl}</div>
      </td>
      <td class="text-right text-currency">${fmtRp(r.uang_harian)}</td>
      <td class="text-right text-currency">${fmtRp(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600">${fmtRp(r.dana_taktis)}</td>
      <td>${statusBadge}</td>
    </tr>`;
  }).join('');
}

/* ── Modal Detail Perjalanan Dinas (Public) ── */
function showPerjadinModalPub(rowOrEncoded) {
  const r = typeof rowOrEncoded === 'string' ? JSON.parse(decodeURIComponent(rowOrEncoded)) : rowOrEncoded;
  const isUp = r.no_pd && r.no_pd.includes('UP');
  const noPdBadge = r.no_pd 
    ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${isUp ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}">No. PD: ${escapeHtml(r.no_pd)}</span>`
    : '';
  
  document.getElementById('perjadin-pub-subtitle').innerHTML = `${noPdBadge} <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.nama_peserta || '')}</span>`;

  const taktisVal = parseFloat(r.dana_taktis) || 0;
  const isZeroTaktis = taktisVal <= 0;
  const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
  const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
  const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const statusBadge = isZeroTaktis
    ? `<span class="badge badge-success">Lunas (Tanpa Taktis)</span>`
    : (isLunas
      ? `<span class="badge badge-success">Lunas</span>${tglLunas ? ` <span class="text-xs text-slate-500">pada ${tglLunas}</span>` : ''}`
      : (isSebagian
        ? `<span class="badge badge-info">Sebagian (Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)})</span>`
        : '<span class="badge badge-warning">Belum Lunas</span>'));

  const tglST = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';

  // Tiket pesawat details
  let tiketHtml = '<span class="text-slate-400 italic">Tidak ada tiket pesawat</span>';
  if (r.tiket && r.tiket.length > 0) {
    tiketHtml = `<div class="space-y-2 mt-1">` + r.tiket.map(t => `
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs">
        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-200">
          <span>${escapeHtml(t.maskapai || 'Pesawat')} &middot; ${escapeHtml(t.nomor_penerbangan || '-')} (${escapeHtml(t.tipe || '-')})</span>
          <span class="text-emerald-600 font-semibold">${fmtRp(t.harga_tiket)}</span>
        </div>
        <div class="text-slate-500 dark:text-slate-400 mt-1">
          Rute: ${escapeHtml(t.kota_asal || '-')} &rarr; ${escapeHtml(t.kota_tujuan || '-')} &middot; Tgl: ${t.tanggal_penerbangan ? new Date(t.tanggal_penerbangan).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'}
        </div>
        <div class="text-[11px] font-mono text-slate-400 dark:text-slate-500 mt-0.5">
          Tiket: ${escapeHtml(t.nomor_tiket || '-')} &middot; Booking: ${escapeHtml(t.kode_booking || '-')}
        </div>
      </div>
    `).join('') + `</div>`;
  }

  // Hotel details
  let hotelHtml = '<span class="text-slate-400 italic">Tidak ada penginapan / hotel</span>';
  if (r.hotel && (parseFloat(r.hotel.total_bill) > 0 || parseFloat(r.hotel.total_biaya_30persen) > 0 || r.hotel.nama_hotel)) {
    const totalH = (parseFloat(r.hotel.total_bill) || 0) + (parseFloat(r.hotel.total_biaya_30persen) || 0);
    hotelHtml = `
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs mt-1">
        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-200">
          <span>${escapeHtml(r.hotel.nama_hotel || 'Hotel')}</span>
          <span class="text-emerald-600 font-semibold">${fmtRp(totalH)}</span>
        </div>
        <div class="text-slate-500 dark:text-slate-400 mt-1">
          Check-in: ${r.hotel.check_in ? new Date(r.hotel.check_in).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'} &middot; 
          Check-out: ${r.hotel.check_out ? new Date(r.hotel.check_out).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'} &middot;
          No Kamar: ${escapeHtml(r.hotel.nomor_kamar || '-')}
        </div>
        ${r.hotel.alamat_telepon ? `<div class="text-slate-400 text-[11px] mt-0.5">${escapeHtml(r.hotel.alamat_telepon)}</div>` : ''}
      </div>
    `;
  }

  document.getElementById('perjadin-pub-body').innerHTML = `
    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 space-y-2.5">
      <div>
        <div class="text-[11px] uppercase tracking-wide font-semibold text-slate-500 dark:text-slate-400">Maksud / Kegiatan Perjalanan Dinas</div>
        <div class="text-sm font-medium text-slate-800 dark:text-slate-100 mt-0.5">${escapeHtml(r.maksud || '-')}</div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
        <div><span class="text-slate-500">No. Surat Tugas:</span> <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_surat_tugas || '-')}</span></div>
        <div><span class="text-slate-500">Tgl. Surat Tugas:</span> <span class="font-medium text-slate-700 dark:text-slate-200">${tglST}</span></div>
        <div><span class="text-slate-500">Kode MAK:</span> <span class="font-mono font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.kode_mak || '-')}</span></div>
        <div><span class="text-slate-500">No. SPM:</span> <span class="font-mono font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_spm || '-')}</span></div>
      </div>
    </div>

    <div>
      <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">Rincian Komponen SPJ</h4>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Uang Harian</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.uang_harian)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullboard</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.meeting_fullboard)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullday</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.meeting_fullday)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Uang Representasi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.uang_representasi)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Transport Lokal / Taksi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.transport_lokal)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">BBM (Jalan Darat)</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.bbm)}</div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Tiket Pesawat</h4>
        ${tiketHtml}
      </div>
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Penginapan / Hotel</h4>
        ${hotelHtml}
      </div>
    </div>

    <div class="p-3 rounded-xl bg-primary-50/60 dark:bg-primary-950/40 border border-primary-100 dark:border-primary-900/60 flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="text-xs text-primary-700 dark:text-primary-300 font-medium">Total SPJ Dibayarkan</div>
        <div class="text-lg font-bold text-primary-900 dark:text-primary-100">${fmtRp(r.total_spj)}</div>
      </div>
      <div class="text-right">
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dana Taktis (10%)</div>
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${fmtRp(r.dana_taktis)}</div>
        <div class="mt-1">${statusBadge}</div>
      </div>
    </div>
  `;

  document.getElementById('modal-detail-perjadin-pub').classList.remove('hidden');
}
function closePerjadinModalPub() { document.getElementById('modal-detail-perjadin-pub').classList.add('hidden'); }
window.showPerjadinModalPub = showPerjadinModalPub;
window.closePerjadinModalPub = closePerjadinModalPub;

/* ── Modal Detail Dana Taktis (Public) ── */
function showDtModalPub(rowOrEncoded) {
  const r = typeof rowOrEncoded === 'string' ? JSON.parse(decodeURIComponent(rowOrEncoded)) : rowOrEncoded;
  const isUp = r.no_pd && r.no_pd.includes('UP');
  const noPdBadge = r.no_pd 
    ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${isUp ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}">No. PD: ${escapeHtml(r.no_pd)}</span>`
    : '';

  document.getElementById('dt-pub-subtitle').innerHTML = `${noPdBadge} <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.nama_peserta || '')}</span>`;

  const taktisVal = parseFloat(r.dana_taktis) || 0;
  const isZeroTaktis = taktisVal <= 0;
  const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
  const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
  const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const statusLabel = isZeroTaktis ? 'Lunas (Tanpa Taktis)' : (isLunas ? 'Lunas' : isSebagian ? 'Sebagian' : 'Belum Lunas');
  const badgeClass = isLunas ? 'badge-success' : isSebagian ? 'badge-info' : 'badge-warning';

  const items = [
    ['Nama Pelaksana Perjalanan Dinas', escapeHtml(r.nama_peserta || '-')],
    ['Maksud Perjalanan Dinas', escapeHtml(r.maksud || '-')],
    ['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(r.kode_mak || '-')}</span>`],
    ['No. Surat Tugas', escapeHtml(r.no_surat_tugas || '-')],
    ['No. SPM', escapeHtml(r.no_spm || '-')],
    ['Tgl. Surat Tugas', r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'],
    ['Uang Harian', fmtRp(r.uang_harian)],
    ['Total SPJ Yang Dibayarkan', fmtRp(r.total_spj)],
    ['Dana Taktis (10%)', `<span class="font-semibold text-emerald-600">${fmtRp(r.dana_taktis)}</span>`],
    ['Status Setoran', `<span class="badge ${badgeClass}">${statusLabel}</span>`],
  ];

  if (isSebagian) {
    items.push(['Sudah Disetor', fmtRp(r.jumlah_disetor)]);
    items.push(['Sisa Kurang', fmtRp(taktisVal - (parseFloat(r.jumlah_disetor) || 0))]);
  }
  if (r.tanggal_lunas) {
    items.push(['Tanggal Setor / Lunas', tglLunas]);
  }

  document.getElementById('dt-pub-body').innerHTML = `
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
      ${items.map(([k, v]) => `<div><dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt><dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd></div>`).join('')}
    </dl>
  `;

  document.getElementById('modal-detail-dt-pub').classList.remove('hidden');
}
function closeDtModalPub() { document.getElementById('modal-detail-dt-pub').classList.add('hidden'); }
window.showDtModalPub = showDtModalPub;
window.closeDtModalPub = closeDtModalPub;

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

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  buildKat();
  loadTren(<?= $tahunSekarang ?>);
  refreshTxn();
  if (location.hash === '#perjadin') aktifkanTab('perjadin', true);
  else if (location.hash === '#dana-taktis') aktifkanTab('dana-taktis', true);
});
</script>
<?= $this->endSection() ?>
