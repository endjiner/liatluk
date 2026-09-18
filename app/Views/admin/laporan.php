<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Laporan &amp; Rekapitulasi</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
      <?= $isSuperAdmin ? 'Ringkasan keuangan per periode' : 'Rekapitulasi Perjalanan Dinas &amp; Dana Taktis per periode' ?>
    </p>
  </div>
  <?php
    $qsExport = http_build_query([
      'bulan_dari'           => $bulanDari,
      'bulan_sampai'         => $bulanSampai,
      'sertakan_perjadin'    => $sertakanPerjadin ? 1 : 0,
      'sertakan_dana_taktis' => $sertakanDanaTaktis ? 1 : 0,
      'filter_nama_taktis'   => $filterNamaTaktis ?? '',
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
    <div class="flex flex-wrap items-center gap-4 pt-1">
      <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
        <input type="checkbox" name="sertakan_perjadin" value="1" class="form-checkbox" <?= $sertakanPerjadin ? 'checked' : '' ?>>
        Sertakan Perjalanan Dinas
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
        <input type="checkbox" name="sertakan_dana_taktis" value="1" class="form-checkbox" <?= $sertakanDanaTaktis ? 'checked' : '' ?>>
        Sertakan Dana Taktis
      </label>
    </div>
    <div class="ml-auto">
      <button type="submit" class="btn btn-primary btn-sm"><?= iconsax('filter', '') ?> Tampilkan</button>
    </div>
  </div>
  <?php if ($sertakanDanaTaktis): ?>
  <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
    <label class="form-label">Filter Nama (Dana Taktis / Pegawai)</label>
    <input type="text" name="filter_nama_taktis" placeholder="Kosongkan untuk semua pegawai..." class="form-control form-control-sm max-w-xs" value="<?= esc($filterNamaTaktis ?? '') ?>">
  </div>
  <?php endif; ?>
</form>

<?php if (!empty($periodeDipangkas)): ?>
<div class="mb-4 flex items-center gap-2 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
  <?= iconsax('warning-2', 'w-4 h-4 shrink-0') ?>
  <span>Rentang periode yang diminta terlalu lebar, dibatasi maksimal <?= $maxBulanPeriode ?> bulan (<?= (int) round($maxBulanPeriode / 12) ?> tahun) supaya laporan tetap cepat dibuat.</span>
</div>
<?php endif; ?>

<?php if ($isSuperAdmin): ?>
<!-- KPI Summary (Super Admin) -->
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
<?php endif; ?>

<?php if ($sertakanPerjadin): ?>
<?php
  $perjadinTripCount = !empty($perjadinTrips) ? count($perjadinTrips) : count(array_unique(array_filter(array_column($perjadinRows ?? [], 'perjalanan_dinas_id'))));
  $perjadinPesertaCount = !empty($perjadinRows) ? count($perjadinRows) : 0;
  $perjadinTotalTaktis = !empty($perjadinRows) ? array_sum(array_column($perjadinRows, 'dana_taktis')) : 0;
  $perjadinBelumLunas = 0;
  if (!empty($perjadinRows)) {
    foreach ($perjadinRows as $pr) {
      if (($pr['status_lunas'] ?? '') !== 'lunas') {
        $perjadinBelumLunas += (float)($pr['dana_taktis'] ?? 0);
      }
    }
  }
?>
<div class="mt-6 mb-2 flex items-center justify-between">
  <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
    <?= iconsax('airplane', 'w-4 h-4 text-primary-600') ?> Rincian Perjalanan Dinas Periode Ini
  </h3>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3 mb-3">
  <div class="kpi p-3 sm:p-4">
    <div class="kpi-label text-[11px]"><?= iconsax('routing', 'w-4 h-4 text-primary-600') ?> Total ST / Trip</div>
    <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200"><?= number_format($perjadinTripCount, 0, ',', '.') ?></div>
  </div>
  <div class="kpi p-3 sm:p-4">
    <div class="kpi-label text-[11px]"><?= iconsax('profile-2user', 'w-4 h-4 text-indigo-600') ?> Total Pelaksana</div>
    <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200"><?= number_format($perjadinPesertaCount, 0, ',', '.') ?></div>
  </div>
  <div class="kpi p-3 sm:p-4">
    <div class="kpi-label text-[11px]"><?= iconsax('receipt-2', 'w-4 h-4 text-blue-600') ?> Total SPJ</div>
    <div class="kpi-value text-base sm:text-lg text-primary-700 dark:text-primary-300 text-currency">Rp <?= number_format($perjadinTotalSpj, 0, ',', '.') ?></div>
  </div>
  <div class="kpi p-3 sm:p-4">
    <div class="kpi-label text-[11px]"><?= iconsax('moneys', 'w-4 h-4 text-emerald-600') ?> Total Dana Taktis</div>
    <div class="kpi-value text-base sm:text-lg text-emerald-700 dark:text-emerald-400 text-currency">Rp <?= number_format($perjadinTotalTaktis, 0, ',', '.') ?></div>
  </div>
  <div class="kpi p-3 sm:p-4 bg-amber-50/70 dark:bg-amber-900/20 col-span-2 lg:col-span-1">
    <div class="kpi-label text-[11px]"><?= iconsax('warning-2', 'w-4 h-4 text-amber-600') ?> Belum Lunas</div>
    <div class="kpi-value text-base sm:text-lg text-amber-700 dark:text-amber-400 text-currency">Rp <?= number_format($perjadinBelumLunas, 0, ',', '.') ?></div>
  </div>
</div>

<div class="card">
  <div class="overflow-x-auto">
    <table class="table text-xs">
      <thead>
        <tr>
          <th class="w-14 text-center">No. PD</th>
          <th class="min-w-[7rem]">Tgl & No. ST</th>
          <th class="min-w-[12rem]">Maksud Perjalanan</th>
          <th class="min-w-[9rem]">MAK / SPM</th>
          <th class="min-w-[7rem]">Pelaksana</th>
          <th class="col-currency text-right">Uang Harian</th>
          <th class="col-currency text-right">Paket Meeting Fullboard</th>
          <th class="col-currency text-right">Paket Meeting Fullday</th>
          <th class="col-currency text-right">Uang Representasi</th>
          <th class="col-currency text-right">Transport Lokal / Taksi</th>
          <th class="col-currency text-right">BBM Jalan Darat</th>
          <th class="col-currency text-right">Tiket Pesawat</th>
          <th class="col-currency text-right">Hotel</th>
          <th class="col-currency text-right font-semibold">Total SPJ</th>
          <th class="col-currency-sm text-right">Dana Taktis</th>
          <th class="min-w-[4rem] text-center">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($perjadinRows)): ?>
        <tr><td colspan="16" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data pada periode ini</td></tr>
      <?php else: foreach ($perjadinRows as $idx => $r): ?>
        <tr>
          <td class="text-center font-mono text-slate-700 dark:text-slate-300 font-semibold whitespace-nowrap">
            <?php if (!empty($r['no_pd'])): ?>
              <?php if (stripos($r['no_pd'], 'UP') !== false): ?>
                <span class="inline-block px-1.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80"><?= esc($r['no_pd']) ?></span>
              <?php else: ?>
                <?= esc($r['no_pd']) ?>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-slate-400 font-normal"><?= $idx + 1 ?></span>
            <?php endif; ?>
          </td>
          <td class="whitespace-nowrap">
            <span class="font-medium text-slate-700 dark:text-slate-200"><?= !empty($r['tanggal_surat_tugas']) ? date('d/m/Y', strtotime($r['tanggal_surat_tugas'])) : '-' ?></span>
            <div class="text-[11px] text-slate-500 font-mono"><?= esc($r['no_surat_tugas'] ?: '-') ?></div>
          </td>
          <td>
            <div class="truncate max-w-[200px]" title="<?= esc($r['maksud']) ?>"><?= esc($r['maksud']) ?></div>
          </td>
          <td class="whitespace-nowrap">
            <span class="font-mono text-slate-600 dark:text-slate-300"><?= esc($r['kode_mak'] ?: '-') ?></span>
            <?php if (!empty($r['no_spm'])): ?>
              <div class="text-[11px] text-slate-400">SPM: <?= esc($r['no_spm']) ?></div>
            <?php endif; ?>
          </td>
          <td class="font-medium whitespace-nowrap text-slate-800 dark:text-slate-200"><?= esc($r['nama_peserta']) ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['uang_harian'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['meeting_fullboard'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['meeting_fullday'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['uang_representasi'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['transport_lokal'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['bbm'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['total_tiket'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency text-slate-600 dark:text-slate-300">Rp <?= number_format((float)($r['total_hotel'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right font-semibold text-currency text-income">Rp <?= number_format((float)($r['total_spj'] ?? 0), 0, ',', '.') ?></td>
          <td class="text-right text-currency font-medium <?= (float)($r['dana_taktis'] ?? 0) > 0 ? 'text-taktis' : 'text-slate-400' ?>">
            Rp <?= number_format((float)($r['dana_taktis'] ?? 0), 0, ',', '.') ?>
          </td>
          <td class="text-center">
            <?php if (($r['status_lunas'] ?? '') === 'lunas'): ?>
              <span class="badge badge-success text-[11px]">Lunas</span>
              <?php if (!empty($r['tanggal_lunas'])): ?>
                <div class="text-[10px] text-slate-500 mt-0.5 whitespace-nowrap"><?= date('d/m/Y', strtotime($r['tanggal_lunas'])) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-warning text-[11px]">Belum</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
      <?php if (!empty($perjadinRows)): ?>
      <tfoot>
        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
          <td colspan="13" class="text-right pr-3 text-slate-600 dark:text-slate-300">TOTAL SPJ</td>
          <td class="text-right text-currency text-income">Rp <?= number_format($perjadinTotalSpj, 0, ',', '.') ?></td>
          <td class="text-right text-currency text-taktis">Rp <?= number_format(array_sum(array_column($perjadinRows, 'dana_taktis')), 0, ',', '.') ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($sertakanDanaTaktis): ?>
<div class="mt-4">
  <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2 mb-2.5">
    <?= iconsax('wallet', 'w-4 h-4 text-primary-600') ?> Rincian Dana Taktis Periode Ini
    <?php if (($filterNamaTaktis ?? '') !== ''): ?>
      <span class="text-xs font-normal text-slate-400">Filter nama: "<?= esc($filterNamaTaktis) ?>"</span>
    <?php endif; ?>
  </h3>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-3">
    <div class="kpi p-3 sm:p-4">
      <div class="kpi-label text-[11px]"><?= iconsax('money', 'w-4 h-4') ?> Total Uang Harian</div>
      <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency">Rp <?= number_format($danaTaktisTotalUangHarian, 0, ',', '.') ?></div>
    </div>
    <div class="kpi p-3 sm:p-4">
      <div class="kpi-label text-[11px]"><?= iconsax('receipt-2', 'w-4 h-4') ?> Total SPJ</div>
      <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency">Rp <?= number_format($danaTaktisTotalSpj, 0, ',', '.') ?></div>
    </div>
    <div class="kpi p-3 sm:p-4">
      <div class="kpi-label text-[11px]"><?= iconsax('moneys', 'w-4 h-4 text-emerald-600') ?> Dana Taktis</div>
      <div class="kpi-value text-base sm:text-lg text-emerald-700 dark:text-emerald-400 text-currency">Rp <?= number_format($danaTaktisTotalTaktis, 0, ',', '.') ?></div>
    </div>
    <div class="kpi p-3 sm:p-4 bg-amber-50/70 dark:bg-amber-900/20">
      <div class="kpi-label text-[11px]"><?= iconsax('warning-2', 'w-4 h-4 text-amber-600') ?> Belum Dibayar</div>
      <div class="kpi-value text-base sm:text-lg text-amber-700 dark:text-amber-400 text-currency">Rp <?= number_format($danaTaktisTotalBelumSetor, 0, ',', '.') ?></div>
    </div>
  </div>

  <div class="card">
    <div class="overflow-x-auto">
      <table class="table">
        <thead><tr><th>Tanggal</th><th>Nama Peserta</th><th>Maksud Perjalanan</th><th class="text-right">Dana Taktis</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($danaTaktisRows)): ?>
          <tr><td colspan="5" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data pada periode<?= ($filterNamaTaktis ?? '') !== '' ? ' / filter nama' : '' ?> ini</td></tr>
        <?php else: foreach ($danaTaktisRows as $row): ?>
          <tr>
            <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tanggal_surat_tugas'])) ?></td>
            <td><?= esc($row['nama_peserta']) ?></td>
            <td class="truncate max-w-[240px]"><?= esc($row['maksud']) ?></td>
            <td class="text-right font-medium text-currency">Rp <?= number_format($row['dana_taktis'], 0, ',', '.') ?></td>
            <td>
              <?= $row['status_lunas'] === 'lunas' ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-warning">Belum Lunas</span>' ?>
              <?php if ($row['status_lunas'] === 'lunas' && !empty($row['tanggal_lunas'])): ?>
                <div class="text-[11px] text-slate-500 mt-0.5 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> <?= date('d/m/Y', strtotime($row['tanggal_lunas'])) ?></div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
