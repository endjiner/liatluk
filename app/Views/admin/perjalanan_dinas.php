<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Perjalanan Dinas</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rekap SPJ &amp; setoran Dana Taktis (10% Uang Harian) per perjalanan dinas</p>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <a href="<?= base_url('admin/perjalanan-dinas/dana-taktis') ?>" class="btn btn-outline btn-sm">
      <i data-lucide="piggy-bank"></i> <span class="hidden sm:inline">Dana Taktis</span>
    </a>
    <button class="btn btn-outline btn-sm" onclick="bukaModalPegawai()">
      <i data-lucide="users"></i> <span class="hidden sm:inline">Kelola Pegawai</span>
    </button>
    <button class="btn btn-success btn-sm" onclick="bukaModalTrip()">
      <i data-lucide="plus"></i> <span class="hidden sm:inline">Perjalanan Dinas</span>
    </button>
  </div>
</div>

<!-- Filter -->
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

<!-- Modal Tambah/Edit Perjalanan Dinas -->
<div id="modal-trip" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-trip')"></div>
  <div class="modal-container">
    <div class="modal-box">
      <div class="modal-header">
        <div><h3 class="modal-title" id="trip-modal-title"><i data-lucide="plane" class="w-5 h-5 text-primary-600"></i> Perjalanan Dinas Baru</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-trip')"><i data-lucide="x"></i></button>
      </div>
      <form id="form-trip" onsubmit="submitTrip(event)">
        <input type="hidden" id="trip-id">
        <div class="modal-body space-y-4">
          <div><label class="form-label">Maksud Perjalanan Dinas <span class="text-red-500">*</span></label>
            <textarea name="maksud" id="trip-maksud" class="form-control" rows="2" required placeholder="Contoh: Perjalanan Dinas dalam Rangka Koordinasi ke Badan POM di Jakarta Pusat..."></textarea></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label class="form-label">Tanggal Surat Tugas <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal_surat_tugas" id="trip-tanggal" class="form-control" required></div>
            <div><label class="form-label">No. Surat Tugas</label>
              <input type="text" name="no_surat_tugas" id="trip-no-surat" class="form-control" placeholder="HM.03.01.7B.01.26.01"></div>
            <div><label class="form-label">Kode MAK</label>
              <input type="text" name="kode_mak" id="trip-kode-mak" class="form-control" placeholder="6384.EBA.994.002.524111.R"></div>
            <div><label class="form-label">No. SPM</label>
              <input type="text" name="no_spm" id="trip-no-spm" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-trip')">Batal</button>
          <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Peserta -->
<div id="modal-peserta" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-peserta')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg" style="max-width:52rem">
      <div class="modal-header">
        <div><h3 class="modal-title" id="peserta-modal-title"><i data-lucide="user-plus" class="w-5 h-5 text-primary-600"></i> Tambah Peserta</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-peserta')"><i data-lucide="x"></i></button>
      </div>
      <form id="form-peserta" onsubmit="submitPeserta(event)">
        <input type="hidden" id="peserta-id">
        <input type="hidden" name="perjalanan_dinas_id" id="peserta-trip-id">
        <div class="modal-body space-y-5" style="max-height:65vh;overflow-y:auto">

          <div>
            <label class="form-label">Nama Pegawai <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <select name="pegawai_id" id="peserta-pegawai-id" class="form-control pegawai-select" required oninput="hitungPreviewPeserta()">
                <option value="">Pilih pegawai...</option>
                <?php foreach ($pegawaiList as $pg): ?>
                <option value="<?= $pg['id'] ?>"><?= esc($pg['nama']) ?><?= $pg['nip'] ? ' — ' . esc($pg['nip']) : '' ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-outline btn-sm shrink-0" onclick="bukaModalPegawai()" title="Tambah pegawai baru"><i data-lucide="plus"></i></button>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div><label class="form-label">Uang Harian</label>
              <input type="text" inputmode="numeric" name="uang_harian" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            <div><label class="form-label">Meeting Fullboard</label>
              <input type="text" inputmode="numeric" name="meeting_fullboard" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            <div><label class="form-label">Meeting Fullday</label>
              <input type="text" inputmode="numeric" name="meeting_fullday" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            <div><label class="form-label">Uang Representasi</label>
              <input type="text" inputmode="numeric" name="uang_representasi" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            <div><label class="form-label">Transport Lokal</label>
              <input type="text" inputmode="numeric" name="transport_lokal" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            <div><label class="form-label">BBM</label>
              <input type="text" inputmode="numeric" name="bbm" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()"></div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="form-label mb-0">Tiket Pesawat</label>
              <button type="button" class="btn btn-outline btn-sm" onclick="tambahBarisTiket()"><i data-lucide="plus"></i> Tambah Tiket</button>
            </div>
            <div id="tiket-rows" class="space-y-2"></div>
          </div>

          <div>
            <label class="form-label">Hotel (opsional)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
              <input type="text" name="hotel[nama_hotel]" id="hotel-nama" class="form-control form-control-sm col-span-2 sm:col-span-1" placeholder="Nama hotel">
              <input type="text" name="hotel[alamat_hotel]" class="form-control form-control-sm col-span-2" placeholder="Alamat hotel">
              <input type="text" name="hotel[telp_hotel]" class="form-control form-control-sm" placeholder="No. telepon">
              <div><label class="form-hint">Check-in</label><input type="date" name="hotel[checkin]" class="form-control form-control-sm"></div>
              <div><label class="form-hint">Check-out</label><input type="date" name="hotel[checkout]" class="form-control form-control-sm"></div>
              <input type="text" name="hotel[no_kamar]" class="form-control form-control-sm" placeholder="No. kamar">
              <input type="text" name="hotel[no_invoice]" class="form-control form-control-sm" placeholder="No. invoice">
              <div><label class="form-hint">Total Bill Hotel</label>
                <input type="text" inputmode="numeric" name="hotel[total_bill]" class="form-control form-control-sm input-rupiah nominal-hotel" placeholder="0" oninput="hitungPreviewPeserta()"></div>
              <div><label class="form-hint">Total Biaya Hotel (jika 30%)</label>
                <input type="text" inputmode="numeric" name="hotel[total_biaya_30persen]" class="form-control form-control-sm input-rupiah nominal-hotel" placeholder="0" oninput="hitungPreviewPeserta()"></div>
            </div>
          </div>

          <div class="flex flex-wrap gap-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 text-sm">
            <div>Estimasi Total SPJ: <span class="font-semibold text-slate-800 dark:text-slate-100" id="preview-total-spj">Rp 0</span></div>
            <div>Estimasi Dana Taktis (10% Uang Harian): <span class="font-semibold text-emerald-600" id="preview-dana-taktis">Rp 0</span></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-peserta')">Batal</button>
          <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Peserta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<template id="tiket-row-template">
  <div class="tiket-row grid grid-cols-2 sm:grid-cols-4 gap-2 p-2 rounded-lg border border-slate-200 dark:border-slate-700 relative">
    <button type="button" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs" onclick="this.closest('.tiket-row').remove(); hitungPreviewPeserta()">&times;</button>
    <input type="text" name="tiket[__IDX__][maskapai]" class="form-control form-control-sm" placeholder="Maskapai">
    <select name="tiket[__IDX__][arah]" class="form-control form-control-sm">
      <option value="pergi">Pergi</option>
      <option value="pulang">Pulang</option>
    </select>
    <input type="text" name="tiket[__IDX__][no_tiket]" class="form-control form-control-sm" placeholder="No. Tiket">
    <input type="text" name="tiket[__IDX__][kode_booking]" class="form-control form-control-sm" placeholder="Kode Booking">
    <input type="text" name="tiket[__IDX__][no_penerbangan]" class="form-control form-control-sm" placeholder="No. Penerbangan">
    <input type="text" name="tiket[__IDX__][tempat_asal]" class="form-control form-control-sm" placeholder="Tempat Asal">
    <input type="text" name="tiket[__IDX__][tempat_tujuan]" class="form-control form-control-sm" placeholder="Tempat Tujuan">
    <input type="date" name="tiket[__IDX__][tanggal_terbang]" class="form-control form-control-sm">
    <input type="text" inputmode="numeric" name="tiket[__IDX__][harga_tiket]" class="form-control form-control-sm input-rupiah nominal-tiket col-span-2" placeholder="Harga tiket" oninput="hitungPreviewPeserta()">
  </div>
</template>

<!-- Modal Tandai Lunas -->
<div id="modal-lunas" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-lunas')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div><h3 class="modal-title"><i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i> Tandai Lunas</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-lunas')"><i data-lucide="x"></i></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="lunas-peserta-id">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Setoran Dana Taktis peserta ini akan otomatis tercatat sebagai Pemasukan (kategori "Setoran Taktis Pegawai").</p>
        <label class="form-label">Tanggal Setoran</label>
        <input type="date" id="lunas-tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-lunas')">Batal</button>
        <button class="btn btn-success" onclick="konfirmasiLunas()"><i data-lucide="check"></i> Tandai Lunas</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kelola Pegawai -->
<div id="modal-pegawai" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-pegawai')"></div>
  <div class="modal-container">
    <div class="modal-box">
      <div class="modal-header">
        <div><h3 class="modal-title"><i data-lucide="users" class="w-5 h-5 text-primary-600"></i> Kelola Data Pegawai</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-pegawai')"><i data-lucide="x"></i></button>
      </div>
      <div class="modal-body">
        <form onsubmit="tambahPegawai(event)" class="flex flex-wrap gap-2 mb-4">
          <input type="text" id="pegawai-baru-nama" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="Nama pegawai" required>
          <input type="text" id="pegawai-baru-nip" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="NIP (opsional)">
          <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Tambah</button>
        </form>
        <div class="overflow-x-auto" style="max-height:50vh;overflow-y:auto">
          <table class="table">
            <thead><tr><th>Nama</th><th>NIP</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody id="pegawai-tbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-pegawai')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi -->
<div id="modal-konfirmasi" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-konfirmasi')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
          </div>
          <div><h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Konfirmasi</h3></div>
        </div>
      </div>
      <div class="modal-body"><p class="text-sm text-slate-600 dark:text-slate-300" id="konfirmasi-text">Yakin ingin melanjutkan?</p></div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-konfirmasi')">Batal</button>
        <button class="btn btn-danger" id="btn-konfirmasi-ya">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';
const API  = BASE + 'admin/perjalanan-dinas';
let tiketIdx = 0;

function tampilkanKonfirmasi(pesan, aksi) {
  document.getElementById('konfirmasi-text').textContent = pesan;
  const btn = document.getElementById('btn-konfirmasi-ya');
  btn.onclick = async function() { closeModal('modal-konfirmasi'); await aksi(); };
  openModal('modal-konfirmasi');
}

// ── Trip (header) ──────────────────────────────────────────────────────────────

function bukaModalTrip() {
  document.getElementById('form-trip').reset();
  document.getElementById('trip-id').value = '';
  document.getElementById('trip-modal-title').innerHTML = '<i data-lucide="plane" class="w-5 h-5 text-primary-600"></i> Perjalanan Dinas Baru';
  openModal('modal-trip');
  lucide.createIcons({ props: { search: document.getElementById('modal-trip') } });
}
function editTrip(trip) {
  document.getElementById('trip-id').value = trip.id;
  document.getElementById('trip-maksud').value = trip.maksud;
  document.getElementById('trip-tanggal').value = trip.tanggal_surat_tugas;
  document.getElementById('trip-no-surat').value = trip.no_surat_tugas || '';
  document.getElementById('trip-kode-mak').value = trip.kode_mak || '';
  document.getElementById('trip-no-spm').value = trip.no_spm || '';
  document.getElementById('trip-modal-title').innerHTML = '<i data-lucide="pencil" class="w-5 h-5 text-primary-600"></i> Edit Perjalanan Dinas';
  openModal('modal-trip');
  lucide.createIcons({ props: { search: document.getElementById('modal-trip') } });
}
async function submitTrip(e) {
  e.preventDefault();
  const id = document.getElementById('trip-id').value;
  const url = id ? API + '/update/' + id : API;
  const res = await fetch(url, { method: 'POST', body: buildFormData(document.getElementById('form-trip')) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-trip'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal menyimpan', 'error');
}
function hapusTrip(id) {
  tampilkanKonfirmasi('Hapus perjalanan dinas ini beserta seluruh peserta, tiket, dan hotelnya? Setoran Dana Taktis yang sudah lunas juga akan dibatalkan.', async () => {
    const res = await fetch(API + '/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
  });
}

// ── Peserta (+ tiket & hotel) ────────────────────────────────────────────────────

function kosongkanFormPeserta() {
  document.getElementById('form-peserta').reset();
  document.getElementById('peserta-id').value = '';
  document.getElementById('tiket-rows').innerHTML = '';
  tiketIdx = 0;
  hitungPreviewPeserta();
}

function bukaModalPeserta(tripId) {
  kosongkanFormPeserta();
  document.getElementById('peserta-trip-id').value = tripId;
  document.getElementById('peserta-modal-title').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5 text-primary-600"></i> Tambah Peserta';
  openModal('modal-peserta');
  lucide.createIcons({ props: { search: document.getElementById('modal-peserta') } });
}

function tambahBarisTiket(data) {
  const tpl = document.getElementById('tiket-row-template').innerHTML.replaceAll('__IDX__', tiketIdx++);
  const div = document.createElement('div');
  div.innerHTML = tpl;
  const row = div.firstElementChild;
  if (data) {
    row.querySelector('[name*="[maskapai]"]').value = data.maskapai || '';
    row.querySelector('[name*="[arah]"]').value = data.arah || 'pergi';
    row.querySelector('[name*="[no_tiket]"]').value = data.no_tiket || '';
    row.querySelector('[name*="[kode_booking]"]').value = data.kode_booking || '';
    row.querySelector('[name*="[no_penerbangan]"]').value = data.no_penerbangan || '';
    row.querySelector('[name*="[tempat_asal]"]').value = data.tempat_asal || '';
    row.querySelector('[name*="[tempat_tujuan]"]').value = data.tempat_tujuan || '';
    row.querySelector('[name*="[tanggal_terbang]"]').value = data.tanggal_terbang || '';
    setRupiahValue(row.querySelector('[name*="[harga_tiket]"]'), data.harga_tiket);
  }
  document.getElementById('tiket-rows').appendChild(row);
}

function editPeserta(p) {
  kosongkanFormPeserta();
  document.getElementById('peserta-id').value = p.id;
  document.getElementById('peserta-trip-id').value = p.perjalanan_dinas_id;
  document.getElementById('peserta-pegawai-id').value = p.pegawai_id || '';
  setRupiahValue(document.querySelector('[name="uang_harian"]'), p.uang_harian);
  setRupiahValue(document.querySelector('[name="meeting_fullboard"]'), p.meeting_fullboard);
  setRupiahValue(document.querySelector('[name="meeting_fullday"]'), p.meeting_fullday);
  setRupiahValue(document.querySelector('[name="uang_representasi"]'), p.uang_representasi);
  setRupiahValue(document.querySelector('[name="transport_lokal"]'), p.transport_lokal);
  setRupiahValue(document.querySelector('[name="bbm"]'), p.bbm);
  (p.tiket || []).forEach(t => tambahBarisTiket(t));
  if (p.hotel) {
    document.getElementById('hotel-nama').value = p.hotel.nama_hotel || '';
    document.querySelector('[name="hotel[alamat_hotel]"]').value = p.hotel.alamat_hotel || '';
    document.querySelector('[name="hotel[telp_hotel]"]').value = p.hotel.telp_hotel || '';
    document.querySelector('[name="hotel[checkin]"]').value = p.hotel.checkin || '';
    document.querySelector('[name="hotel[checkout]"]').value = p.hotel.checkout || '';
    document.querySelector('[name="hotel[no_kamar]"]').value = p.hotel.no_kamar || '';
    document.querySelector('[name="hotel[no_invoice]"]').value = p.hotel.no_invoice || '';
    setRupiahValue(document.querySelector('[name="hotel[total_bill]"]'), p.hotel.total_bill);
    setRupiahValue(document.querySelector('[name="hotel[total_biaya_30persen]"]'), p.hotel.total_biaya_30persen);
  }
  document.getElementById('peserta-modal-title').innerHTML = '<i data-lucide="pencil" class="w-5 h-5 text-primary-600"></i> Edit Peserta: ' + p.nama_peserta;
  hitungPreviewPeserta();
  openModal('modal-peserta');
  lucide.createIcons({ props: { search: document.getElementById('modal-peserta') } });
}

async function submitPeserta(e) {
  e.preventDefault();
  const id = document.getElementById('peserta-id').value;
  const url = id ? API + '/peserta/update/' + id : API + '/peserta';
  const res = await fetch(url, { method: 'POST', body: buildFormData(document.getElementById('form-peserta')) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-peserta'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal menyimpan', 'error');
}

function hapusPeserta(id) {
  tampilkanKonfirmasi('Hapus peserta ini beserta tiket & hotelnya?', async () => {
    const res = await fetch(API + '/peserta/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
  });
}

/** Estimasi Total SPJ & Dana Taktis di sisi klien — cuma preview, angka final selalu dihitung ulang di server (recalculate()) saat disimpan. */
function hitungPreviewPeserta() {
  const num = (el) => parseFloat(unformatRibuan(el ? el.value : '0')) || 0;
  let total = 0;
  document.querySelectorAll('#form-peserta .nominal-peserta').forEach(el => total += num(el));
  document.querySelectorAll('#form-peserta .nominal-tiket').forEach(el => total += num(el));
  document.querySelectorAll('#form-peserta .nominal-hotel').forEach(el => total += num(el));
  const uangHarian = num(document.querySelector('#form-peserta [name="uang_harian"]'));
  const danaTaktis = Math.round(uangHarian * 0.10);
  document.getElementById('preview-total-spj').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
  document.getElementById('preview-dana-taktis').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(danaTaktis);
}

// ── Status Lunas ─────────────────────────────────────────────────────────────────

function bukaModalLunas(pesertaId) {
  document.getElementById('lunas-peserta-id').value = pesertaId;
  document.getElementById('lunas-tanggal').value = new Date().toISOString().slice(0, 10);
  openModal('modal-lunas');
}
async function konfirmasiLunas() {
  const id = document.getElementById('lunas-peserta-id').value;
  const fd = new FormData();
  fd.append('aksi', 'lunas');
  fd.append('tanggal_lunas', document.getElementById('lunas-tanggal').value);
  const res = await fetch(API + '/lunas/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-lunas'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal', 'error');
}
function batalkanLunas(pesertaId) {
  tampilkanKonfirmasi('Batalkan status lunas? Pemasukan otomatis yang sudah tercatat akan ikut dihapus.', async () => {
    const fd = new FormData();
    fd.append('aksi', 'batal');
    const res = await fetch(API + '/lunas/' + pesertaId, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal', 'error');
  });
}

// ── Master Data Pegawai ──────────────────────────────────────────────────────────

async function muatDaftarPegawai() {
  const res = await fetch(API + '/pegawai');
  const json = await res.json();
  const tbody = document.getElementById('pegawai-tbody');
  tbody.innerHTML = '';
  (json.data || []).forEach(pg => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${pg.nama}</td><td>${pg.nip || '-'}</td>` +
      `<td>${pg.aktif == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>'}</td>` +
      `<td class="text-center">` +
      (pg.aktif == 1 ? `<button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" onclick="nonaktifkanPegawai(${pg.id})" title="Nonaktifkan"><i data-lucide="user-x" class="w-4 h-4"></i></button>` : '') +
      `</td>`;
    tbody.appendChild(tr);
  });
  lucide.createIcons({ props: { search: tbody } });
}
function bukaModalPegawai() {
  openModal('modal-pegawai');
  muatDaftarPegawai();
}
async function tambahPegawai(e) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('nama', document.getElementById('pegawai-baru-nama').value);
  fd.append('nip', document.getElementById('pegawai-baru-nip').value);
  const res = await fetch(API + '/pegawai', { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) {
    document.getElementById('pegawai-baru-nama').value = '';
    document.getElementById('pegawai-baru-nip').value = '';
    showToast(json.message, 'success');
    await muatDaftarPegawai();
    await segarkanOpsiPegawai();
  } else showToast(json.message || 'Gagal', 'error');
}
async function nonaktifkanPegawai(id) {
  const res = await fetch(API + '/pegawai/delete/' + id, { method: 'POST' });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
}
async function segarkanOpsiPegawai() {
  const res = await fetch(API + '/pegawai');
  const json = await res.json();
  const aktif = (json.data || []).filter(p => p.aktif == 1);
  document.querySelectorAll('.pegawai-select').forEach(sel => {
    const current = sel.value;
    sel.innerHTML = '<option value="">Pilih pegawai...</option>' +
      aktif.map(p => `<option value="${p.id}">${p.nama}${p.nip ? ' — ' + p.nip : ''}</option>`).join('');
    sel.value = current;
  });
}
</script>
<?= $this->endSection() ?>
