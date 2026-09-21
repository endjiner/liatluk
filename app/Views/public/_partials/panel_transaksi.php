<?php
$tahunSekarang = $tahunSekarang ?? (int)date('Y');
$tahunOpsi = $tahunOpsi ?? range($tahunSekarang, 2016);
$namaBulan = $namaBulan ?? [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>
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