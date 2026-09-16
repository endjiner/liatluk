<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Laporan &amp; Rekapitulasi</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan keuangan per periode</p>
  </div>
  <?php
    $qsExport = http_build_query([
      'bulan_dari'           => $bulanDari,
      'bulan_sampai'         => $bulanSampai,
      'sertakan_perjadin'    => $sertakanPerjadin ? 1 : 0,
      'sertakan_dana_taktis' => $sertakanDanaTaktis ? 1 : 0,
    ]);
  ?>
  <div class="flex flex-wrap items-center gap-2">
    <a href="<?= base_url('admin/laporan/export-pdf?' . $qsExport) ?>" target="_blank" class="btn btn-outline btn-sm">
      <?= iconsax('document-text', 'text-red-600') ?> Export PDF
    </a>
    <a href="<?= base_url('admin/laporan/export-excel?' . $qsExport) ?>" class="btn btn-outline btn-sm">
      <?= iconsax('export-square', 'text-emerald-600') ?> Export Excel
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
    <button type="submit" class="btn btn-primary btn-sm"><?= iconsax('filter', '') ?> Tampilkan</button>
    <div class="flex flex-wrap items-center gap-4 ml-auto pt-1">
      <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
        <input type="checkbox" name="sertakan_perjadin" value="1" class="form-checkbox" <?= $sertakanPerjadin ? 'checked' : '' ?>>
        Sertakan Perjalanan Dinas
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
        <input type="checkbox" name="sertakan_dana_taktis" value="1" class="form-checkbox" <?= $sertakanDanaTaktis ? 'checked' : '' ?>>
        Sertakan Dana Taktis
      </label>
    </div>
  </div>
</form>

<?php if (!empty($periodeDipangkas)): ?>
<div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
  <?= iconsax('warning-2', 'w-4 h-4 shrink-0') ?>
  <span>Rentang periode yang diminta terlalu lebar, dibatasi maksimal <?= $maxBulanPeriode ?> bulan (<?= (int) round($maxBulanPeriode / 12) ?> tahun) supaya laporan tetap cepat dibuat.</span>
</div>
<?php endif; ?>

<!-- KPI Summary -->
<div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <div class="kpi">
    <div class="kpi-label"><?= iconsax('wallet', 'w-4 h-4') ?> Saldo Awal Periode</div>
    <div class="kpi-value text-primary-700 dark:text-primary-300 text-currency">Rp <?= number_format($saldoAwal, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= iconsax('trend-up', 'w-4 h-4 text-emerald-600') ?> Total Pemasukan</div>
    <div class="kpi-value text-emerald-700 dark:text-emerald-400 text-currency">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= iconsax('trend-down', 'w-4 h-4 text-red-600') ?> Total Pengeluaran</div>
    <div class="kpi-value text-red-700 dark:text-red-400 text-currency">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= iconsax('wallet', 'w-4 h-4') ?> Saldo Akhir Periode</div>
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
        <?= iconsax('task-square', 'w-4 h-4 text-emerald-600') ?> Rekap Pemasukan per Kategori
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead><tr><th>Kategori</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php if (empty($kategoriPemasukan)): ?>
          <tr><td colspan="2" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data</td></tr>
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
        <?= iconsax('task-square', 'w-4 h-4 text-red-600') ?> Rekap Pengeluaran per Kategori
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead><tr><th>Kategori</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php if (empty($kategoriPengeluaran)): ?>
          <tr><td colspan="2" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data</td></tr>
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

<?php if ($sertakanPerjadin): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('airplane', 'w-4 h-4 text-primary-600') ?> Rincian Perjalanan Dinas Periode Ini
    </h3>
  </div>
  <div class="overflow-x-auto">
    <table class="table">
      <thead><tr><th>Tanggal</th><th>No. Surat Tugas</th><th>Maksud</th><th class="text-right">Jml Peserta</th><th class="text-right">Total SPJ</th></tr></thead>
      <tbody>
      <?php if (empty($perjadinTrips)): ?>
        <tr><td colspan="5" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data pada periode ini</td></tr>
      <?php else: foreach ($perjadinTrips as $t): ?>
        <tr>
          <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($t['tanggal_surat_tugas'])) ?></td>
          <td><?= esc($t['no_surat_tugas'] ?: '-') ?></td>
          <td class="truncate max-w-[280px]"><?= esc($t['maksud']) ?></td>
          <td class="text-right"><?= $t['jumlah_peserta'] ?></td>
          <td class="text-right font-medium text-currency">Rp <?= number_format($t['total_spj'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
      <?php if (!empty($perjadinTrips)): ?>
      <tfoot>
        <tr>
          <td colspan="4" class="text-right font-semibold">TOTAL SPJ</td>
          <td class="text-right font-semibold text-currency">Rp <?= number_format($perjadinTotalSpj, 0, ',', '.') ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($sertakanDanaTaktis): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('wallet', 'w-4 h-4 text-primary-600') ?> Rincian Dana Taktis Periode Ini
    </h3>
  </div>
  <div class="overflow-x-auto">
    <table class="table">
      <thead><tr><th>Tanggal</th><th>Nama Peserta</th><th>Maksud Perjalanan</th><th class="text-right">Dana Taktis</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($danaTaktisRows)): ?>
        <tr><td colspan="5" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data pada periode ini</td></tr>
      <?php else: foreach ($danaTaktisRows as $row): ?>
        <tr>
          <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tanggal_surat_tugas'])) ?></td>
          <td><?= esc($row['nama_peserta']) ?></td>
          <td class="truncate max-w-[240px]"><?= esc($row['maksud']) ?></td>
          <td class="text-right font-medium text-currency">Rp <?= number_format($row['dana_taktis'], 0, ',', '.') ?></td>
          <td><?= $row['status_lunas'] === 'lunas' ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-warning">Belum Lunas</span>' ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
      <?php if (!empty($danaTaktisRows)): ?>
      <tfoot>
        <tr>
          <td colspan="3" class="text-right font-semibold">TOTAL LUNAS / BELUM LUNAS</td>
          <td colspan="2" class="text-right font-semibold text-currency">
            <span class="text-emerald-600">Rp <?= number_format($danaTaktisTotalLunas, 0, ',', '.') ?></span>
            /
            <span class="text-amber-600">Rp <?= number_format($danaTaktisTotalBelum, 0, ',', '.') ?></span>
          </td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
