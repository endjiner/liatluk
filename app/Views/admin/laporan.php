<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Laporan &amp; Rekapitulasi</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan keuangan per periode</p>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <a href="<?= base_url('admin/laporan/export-pdf?bulan_dari=' . $bulanDari . '&bulan_sampai=' . $bulanSampai) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i data-lucide="file-text" class="text-red-600"></i> Export PDF
    </a>
    <a href="<?= base_url('admin/laporan/export-excel?bulan_dari=' . $bulanDari . '&bulan_sampai=' . $bulanSampai) ?>" class="btn btn-outline btn-sm">
      <i data-lucide="file-spreadsheet" class="text-emerald-600"></i> Export Excel
    </a>
  </div>
</div>

<!-- Filter -->
<form method="GET" action="<?= base_url('admin/laporan') ?>" class="card p-3 mb-4">
  <div class="flex flex-wrap items-end gap-3">
    <div>
      <label class="form-label">Bulan Dari</label>
      <input type="month" name="bulan_dari" class="form-control form-control-sm" value="<?= $bulanDari ?>">
    </div>
    <div>
      <label class="form-label">Bulan Sampai</label>
      <input type="month" name="bulan_sampai" class="form-control form-control-sm" value="<?= $bulanSampai ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> Tampilkan</button>
  </div>
</form>

<?php if (!empty($periodeDipangkas)): ?>
<div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
  <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
  <span>Rentang periode yang diminta terlalu lebar, dibatasi maksimal <?= $maxBulanPeriode ?> bulan (<?= (int) round($maxBulanPeriode / 12) ?> tahun) supaya laporan tetap cepat dibuat.</span>
</div>
<?php endif; ?>

<!-- KPI Summary -->
<div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <div class="kpi">
    <div class="kpi-label"><i data-lucide="wallet" class="w-4 h-4"></i> Saldo Awal Periode</div>
    <div class="kpi-value text-primary-700 dark:text-primary-300 text-currency">Rp <?= number_format($saldoAwal, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i> Total Pemasukan</div>
    <div class="kpi-value text-emerald-700 dark:text-emerald-400 text-currency">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><i data-lucide="trending-down" class="w-4 h-4 text-red-600"></i> Total Pengeluaran</div>
    <div class="kpi-value text-red-700 dark:text-red-400 text-currency">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><i data-lucide="scale" class="w-4 h-4"></i> Saldo Akhir Periode</div>
    <div class="kpi-value <?= $saldoAkhir >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' ?> text-currency">
      Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
    </div>
  </div>
</div>

<!-- Rekap per Kategori -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <i data-lucide="list-ordered" class="w-4 h-4 text-emerald-600"></i> Rekap Pemasukan per Kategori
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead><tr><th>Kategori</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php if (empty($kategoriPemasukan)): ?>
          <tr><td colspan="2" class="text-center py-8 text-slate-500">Tidak ada data</td></tr>
        <?php else: foreach ($kategoriPemasukan as $kat => $tot): ?>
          <tr>
            <td class="truncate max-w-[200px]"><?= esc($kat) ?></td>
            <td class="text-right font-medium text-emerald-600 text-currency">Rp <?= number_format($tot, 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <i data-lucide="list-ordered" class="w-4 h-4 text-red-600"></i> Rekap Pengeluaran per Kategori
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead><tr><th>Kategori</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php if (empty($kategoriPengeluaran)): ?>
          <tr><td colspan="2" class="text-center py-8 text-slate-500">Tidak ada data</td></tr>
        <?php else: foreach ($kategoriPengeluaran as $kat => $tot): ?>
          <tr>
            <td class="truncate max-w-[200px]"><?= esc($kat) ?></td>
            <td class="text-right font-medium text-red-600 text-currency">Rp <?= number_format($tot, 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
