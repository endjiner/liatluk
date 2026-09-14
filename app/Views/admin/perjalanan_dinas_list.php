<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<?php if (empty($trips)): ?>
<div class="card"><div class="card-body text-center py-12 text-slate-500">
  <i data-lucide="plane" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
  Belum ada data perjalanan dinas untuk filter ini.
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
      <div class="flex items-center gap-1 shrink-0">
        <button type="button" class="btn btn-success btn-sm" onclick='bukaModalPeserta(<?= $trip["id"] ?>)'>
          <i data-lucide="user-plus" class="w-4 h-4"></i> <span class="hidden md:inline">Peserta</span>
        </button>
        <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Edit"
                onclick='editTrip(<?= json_encode($trip, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
          <i data-lucide="pencil" class="w-4 h-4"></i>
        </button>
        <button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" title="Hapus"
                onclick="hapusTrip(<?= $trip['id'] ?>)">
          <i data-lucide="trash-2" class="w-4 h-4"></i>
        </button>
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
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($trip['peserta'])): ?>
          <tr><td colspan="9" class="text-center py-6 text-slate-500">Belum ada peserta. Klik "Peserta" untuk menambahkan.</td></tr>
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
                <button type="button" class="block text-xs text-slate-400 hover:text-red-600 mt-0.5" onclick="batalkanLunas(<?= $p['id'] ?>)">batalkan</button>
              <?php else: ?>
                <button type="button" class="badge badge-warning" onclick="bukaModalLunas(<?= $p['id'] ?>)" title="Tandai setoran ini lunas">Belum Lunas</button>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex items-center justify-center gap-1">
                <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Edit peserta"
                        onclick='editPeserta(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                </button>
                <button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" title="Hapus peserta"
                        onclick="hapusPeserta(<?= $p['id'] ?>)">
                  <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>
