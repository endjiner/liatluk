<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-8 lg:py-10">

  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Rekap Perjalanan Dinas</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rincian SPJ &amp; setoran Dana Taktis (10% Uang Harian) per perjalanan dinas</p>
    </div>
    <a href="<?= base_url('perjalanan-dinas/dana-taktis') ?>" class="btn btn-outline btn-sm">
      <i data-lucide="piggy-bank"></i> Cek Dana Taktis Saya
    </a>
  </div>

  <form method="get" class="card mb-4">
    <div class="card-body flex flex-wrap items-end gap-3 py-3">
      <div>
        <label class="form-label">Tahun</label>
        <select name="tahun" class="form-control form-control-sm" onchange="this.form.submit()">
          <?php $tahunSaatIni = (int)date('Y'); $daftarTahun = $tahunList; if (!in_array($tahunSaatIni, $daftarTahun)) $daftarTahun[] = $tahunSaatIni; rsort($daftarTahun); ?>
          <?php foreach ($daftarTahun as $th): ?>
          <option value="<?= $th ?>" <?= (int)$filters['tahun'] === $th ? 'selected' : '' ?>><?= $th ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Bulan</label>
        <select name="bulan" class="form-control form-control-sm" onchange="this.form.submit()">
          <option value="">Semua Bulan</option>
          <?php for ($b = 1; $b <= 12; $b++): ?>
          <option value="<?= $b ?>" <?= (int)($filters['bulan'] ?? 0) === $b ? 'selected' : '' ?>><?= $namaBulan[$b] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="form-label">Cari</label>
        <input type="text" name="search" value="<?= esc($filters['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="Maksud, no surat tugas, kode MAK...">
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search"></i> Filter</button>
    </div>
  </form>

  <?php if (empty($trips)): ?>
  <div class="card"><div class="card-body text-center py-12 text-slate-500">
    <i data-lucide="plane" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
    Belum ada data perjalanan dinas untuk periode ini.
  </div></div>
  <?php endif; ?>

  <?php $bulanBerjalan = null; $no = 0; foreach ($trips as $trip): $bulanKey = date('Y-m', strtotime($trip['tanggal_surat_tugas']));
    if ($bulanKey !== $bulanBerjalan): $bulanBerjalan = $bulanKey; ?>
    <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-6 mb-2">
      <?= $namaBulan[(int)date('n', strtotime($trip['tanggal_surat_tugas']))] . ' ' . date('Y', strtotime($trip['tanggal_surat_tugas'])) ?>
    </h2>
    <?php endif; $no++; ?>

    <div class="card mb-4">
      <div class="card-header items-start">
        <div class="flex gap-3">
          <div class="w-8 h-8 shrink-0 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 flex items-center justify-center text-sm font-semibold"><?= $no ?></div>
          <div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><?= esc($trip['maksud']) ?></h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              <?= date('d M Y', strtotime($trip['tanggal_surat_tugas'])) ?>
              <?php if ($trip['no_surat_tugas']): ?> &middot; ST: <?= esc($trip['no_surat_tugas']) ?><?php endif; ?>
              <?php if ($trip['kode_mak']): ?> &middot; MAK: <?= esc($trip['kode_mak']) ?><?php endif; ?>
              <?php if ($trip['no_spm']): ?> &middot; SPM: <?= esc($trip['no_spm']) ?><?php endif; ?>
            </p>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Peserta</th>
              <th class="text-right">Uang Harian</th>
              <th class="text-right">Biaya Lain</th>
              <th class="text-right">Tiket</th>
              <th class="text-right">Hotel</th>
              <th class="text-right">Total SPJ</th>
              <th class="text-right">Dana Taktis (10%)</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($trip['peserta'])): ?>
            <tr><td colspan="8" class="text-center py-6 text-slate-500">Belum ada peserta.</td></tr>
          <?php else: foreach ($trip['peserta'] as $p):
            $biayaLain = (float)$p['meeting_fullboard'] + (float)$p['meeting_fullday'] + (float)$p['uang_representasi'] + (float)$p['transport_lokal'] + (float)$p['bbm'];
            $tooltipLain = "Meeting Fullboard: Rp " . number_format($p['meeting_fullboard'],0,',','.') . "\nMeeting Fullday: Rp " . number_format($p['meeting_fullday'],0,',','.') . "\nUang Representasi: Rp " . number_format($p['uang_representasi'],0,',','.') . "\nTransport Lokal: Rp " . number_format($p['transport_lokal'],0,',','.') . "\nBBM: Rp " . number_format($p['bbm'],0,',','.');
            $totalTiket = array_sum(array_column($p['tiket'], 'harga_tiket'));
            $totalHotel = $p['hotel'] ? ((float)$p['hotel']['total_bill'] + (float)$p['hotel']['total_biaya_30persen']) : 0;
          ?>
            <tr>
              <td class="font-medium text-slate-700 dark:text-slate-200"><?= esc($p['nama_peserta']) ?></td>
              <td class="text-right text-currency"><?= $p['uang_harian'] > 0 ? 'Rp ' . number_format($p['uang_harian'],0,',','.') : '-' ?></td>
              <td class="text-right text-currency" title="<?= esc($tooltipLain) ?>"><?= $biayaLain > 0 ? 'Rp ' . number_format($biayaLain,0,',','.') : '-' ?></td>
              <td class="text-right text-currency"><?= count($p['tiket']) > 0 ? count($p['tiket']) . 'x &middot; Rp ' . number_format($totalTiket,0,',','.') : '-' ?></td>
              <td class="text-right text-currency"><?= $p['hotel'] ? esc($p['hotel']['nama_hotel'] ?: 'Hotel') . '<br><span class="text-xs">Rp ' . number_format($totalHotel,0,',','.') . '</span>' : '-' ?></td>
              <td class="text-right text-currency font-semibold">Rp <?= number_format($p['total_spj'],0,',','.') ?></td>
              <td class="text-right text-currency font-semibold text-emerald-600">Rp <?= number_format($p['dana_taktis'],0,',','.') ?></td>
              <td>
                <?php if ($p['status_lunas'] === 'lunas'): ?>
                  <span class="badge badge-success">Lunas</span>
                <?php else: ?>
                  <span class="badge badge-warning">Belum Lunas</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

</div>
<?= $this->endSection() ?>
