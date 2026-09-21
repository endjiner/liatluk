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
          Publikasi arus kas Balai Besar POM di Pangkal Pinang secara terbuka, transparan, dan akuntabel.
        </p>
      </div>
      <img src="https://img.icons8.com/clouds/500/wallet.png" alt="" class="hidden md:block w-28 lg:w-36 shrink-0">
    </div>
  </div>
</section>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-6 space-y-6">

  <!-- KPI Cards: 4 kartu sejajar (2 kolom di mobile, 4 kolom di desktop) -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 lg:gap-5">
    <!-- Saldo Kas -->
    <div class="kpi p-3.5 sm:p-5 bg-primary-50/70 dark:bg-primary-900/20">
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

  <?= $this->include('public/_partials/panel_transaksi') ?>
  <?= $this->include('public/_partials/panel_perjadin') ?>
  <?= $this->include('public/_partials/panel_dana_taktis') ?>
</div><!-- /container -->

<?= $this->include('public/_partials/modals') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
<?= $this->include('public/_partials/dashboard_scripts') ?>
</script>
<?= $this->endSection() ?>