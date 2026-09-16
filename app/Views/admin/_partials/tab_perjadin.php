<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="mb-3 flex items-center justify-end gap-2">
  <button class="btn btn-outline btn-sm" onclick="bukaModalPegawai()">
    <?= iconsax('people', '') ?> <span class="hidden sm:inline">Kelola Pegawai</span>
  </button>
  <button class="btn btn-success btn-sm" onclick="bukaModalTrip()">
    <?= iconsax('add', '') ?> <span class="hidden sm:inline">Perjalanan Dinas</span>
  </button>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar Perjalanan Dinas</h3>
    <span class="text-xs text-slate-500">Total: <span id="pd-total-perjadin">-</span></span>
  </div>
  <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[160px] max-w-xs">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
          <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
        </span>
        <input type="text" id="filter-search-perjadin" oninput="jadwalkanMuatDaftar()" placeholder="Nama, maksud, no surat tugas, MAK..." class="form-control form-control-sm pl-8">
      </div>
      <select id="filter-status-perjadin" onchange="muatDaftarTrip(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Status</option>
        <option value="belum">Belum Lunas</option>
        <option value="lunas">Lunas</option>
      </select>
      <select id="filter-bulan-perjadin" onchange="muatDaftarTrip(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Bulan</option>
        <?php for ($b = 1; $b <= 12; $b++): ?>
        <option value="<?= $b ?>"><?= $namaBulan[$b] ?></option>
        <?php endfor; ?>
      </select>
      <select id="filter-tahun-perjadin" onchange="muatDaftarTrip(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Tahun</option>
        <?php $tahunSaatIni = (int)date('Y'); $daftarTahun = $tahunListPerjadin; if (!in_array($tahunSaatIni, $daftarTahun)) $daftarTahun[] = $tahunSaatIni; rsort($daftarTahun); ?>
        <?php foreach ($daftarTahun as $th): ?>
        <option value="<?= $th ?>"><?= $th ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex items-center gap-1.5 pl-3 border-l border-slate-200 dark:border-slate-700">
        <span class="text-xs text-slate-600 dark:text-slate-400">Tampilkan</span>
        <select id="filter-per-page-perjadin" onchange="muatDaftarTrip(1)" class="form-control form-control-sm w-auto">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-xs text-slate-600 dark:text-slate-400">baris</span>
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
          <th class="w-10">No</th>
          <th class="min-w-[220px]">Perjalanan Dinas</th>
          <th>Peserta</th>
          <th class="text-right">Uang Harian</th>
          <th class="text-right">Total SPJ</th>
          <th class="text-right">Dana Taktis</th>
          <th>Status</th>
          <th class="w-20 text-center">Aksi</th>
          <th class="text-right">Biaya Lain</th>
          <th class="text-right">Tiket</th>
          <th class="text-right">Hotel</th>
        </tr>
      </thead>
      <tbody id="pd-tbody-perjadin">
        <tr><td colspan="11" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>

  <div id="pd-pagination-wrap-perjadin" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
</div>

<!-- Modal Tambah/Edit Perjalanan Dinas -->
<div id="modal-trip" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-trip')"></div>
  <div class="modal-container">
    <div class="modal-box">
      <div class="modal-header">
        <div><h3 class="modal-title" id="trip-modal-title"><?= iconsax('airplane', 'w-5 h-5 text-primary-600') ?> Perjalanan Dinas Baru</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-trip')"><?= iconsax('close-circle', '') ?></button>
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
          <button type="submit" class="btn btn-primary"><?= iconsax('save-2', '') ?> Simpan</button>
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
        <div><h3 class="modal-title" id="peserta-modal-title"><?= iconsax('user-add', 'w-5 h-5 text-primary-600') ?> Tambah Peserta</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-peserta')"><?= iconsax('close-circle', '') ?></button>
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
              <button type="button" class="btn btn-outline btn-sm shrink-0" onclick="bukaModalPegawai()" title="Tambah pegawai baru"><?= iconsax('add', '') ?></button>
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
              <button type="button" class="btn btn-outline btn-sm" onclick="tambahBarisTiket()"><?= iconsax('add', '') ?> Tambah Tiket</button>
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

          <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-700">
            <label class="form-label mb-2">Status Setoran Dana Taktis</label>
            <div class="segment w-full mb-3">
              <button type="button" data-status="belum" class="segment-btn status-lunas-btn active flex-1" onclick="pilihStatusLunas('belum')">Belum Lunas</button>
              <button type="button" data-status="sebagian" class="segment-btn status-lunas-btn flex-1" onclick="pilihStatusLunas('sebagian')">Bayar Sebagian</button>
              <button type="button" data-status="lunas" class="segment-btn status-lunas-btn flex-1" onclick="pilihStatusLunas('lunas')">Lunas</button>
            </div>
            <input type="hidden" name="status_lunas_input" id="peserta-status-lunas" value="belum">
            <div id="peserta-status-detail" class="hidden grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Tanggal Setoran</label>
                <input type="date" name="tanggal_setoran" id="peserta-tanggal-setoran" class="form-control form-control-sm">
              </div>
              <div id="peserta-jumlah-disetor-wrap" class="hidden">
                <label class="form-label">Jumlah Disetor</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                  <input type="text" inputmode="numeric" name="jumlah_disetor" id="peserta-jumlah-disetor" class="form-control form-control-sm input-rupiah pl-9" placeholder="0">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-peserta')">Batal</button>
          <button type="button" class="btn btn-outline" id="btn-simpan-tambah-lagi" onclick="submitPeserta(event, true)"><?= iconsax('add', '') ?> Simpan & Tambah Lagi</button>
          <button type="submit" class="btn btn-primary"><?= iconsax('save-2', '') ?> Simpan Peserta</button>
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
        <div><h3 class="modal-title"><?= iconsax('tick-circle', 'w-5 h-5 text-emerald-600') ?> Kelola Setoran Dana Taktis</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-lunas')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="lunas-peserta-id">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Dana Taktis: <span class="font-semibold text-slate-800 dark:text-slate-100" id="lunas-dana-taktis-info">Rp 0</span>. Setoran akan otomatis tercatat sebagai Pemasukan (kategori "Setoran Taktis Pegawai").</p>
        <div class="segment w-full mb-3">
          <button type="button" data-status="sebagian" class="segment-btn lunas-status-btn flex-1" onclick="pilihStatusModalLunas('sebagian')">Bayar Sebagian</button>
          <button type="button" data-status="lunas" class="segment-btn lunas-status-btn active flex-1" onclick="pilihStatusModalLunas('lunas')">Lunas</button>
        </div>
        <div id="lunas-jumlah-wrap" class="hidden mb-3">
          <label class="form-label">Jumlah Disetor</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
            <input type="text" inputmode="numeric" id="lunas-jumlah-disetor" class="form-control input-rupiah pl-9" placeholder="0">
          </div>
        </div>
        <label class="form-label">Tanggal Setoran</label>
        <input type="date" id="lunas-tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-lunas')">Batal</button>
        <button class="btn btn-success" onclick="konfirmasiLunas()"><?= iconsax('check', '') ?> Simpan Setoran</button>
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
        <div><h3 class="modal-title"><?= iconsax('people', 'w-5 h-5 text-primary-600') ?> Kelola Data Pegawai</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-pegawai')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <form onsubmit="tambahPegawai(event)" class="flex flex-wrap gap-2 mb-4">
          <input type="text" id="pegawai-baru-nama" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="Nama pegawai" required>
          <input type="text" id="pegawai-baru-nip" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="NIP (opsional)">
          <button type="submit" class="btn btn-primary btn-sm"><?= iconsax('add', '') ?> Tambah</button>
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

<script>
(function() {
const BASE = '<?= base_url() ?>';
const API  = BASE + 'admin/perjalanan-dinas';
let tiketIdx = 0;
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

// ── Filter & Search (AJAX, tabel datar 1 baris = 1 peserta) ───────────────────────
let filterDebounceTimer = null;
let halamanTripSaatIni = 1;
let currentRows = [];
function jadwalkanMuatDaftar() {
  clearTimeout(filterDebounceTimer);
  filterDebounceTimer = setTimeout(() => muatDaftarTrip(1), 400);
}
window.jadwalkanMuatDaftar = jadwalkanMuatDaftar;

async function muatDaftarTrip(page) {
  halamanTripSaatIni = page || halamanTripSaatIni || 1;
  const params = new URLSearchParams();
  const tahun = document.getElementById('filter-tahun-perjadin').value;
  const bulan = document.getElementById('filter-bulan-perjadin').value;
  const status = document.getElementById('filter-status-perjadin').value;
  const search = document.getElementById('filter-search-perjadin').value;
  const perPage = document.getElementById('filter-per-page-perjadin').value;
  if (tahun) params.set('tahun', tahun);
  if (bulan) params.set('bulan', bulan);
  if (status) params.set('status', status);
  if (search) params.set('search', search);
  if (perPage) params.set('per_page', perPage);
  params.set('page', halamanTripSaatIni);

  const tbody = document.getElementById('pd-tbody-perjadin');
  try {
    const res = await fetch(API + '/ajax?' + params.toString());
    const json = await res.json();
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="11" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    renderTripTable(json.data);
    renderPaginasiHalaman(document.getElementById('pd-pagination-wrap-perjadin'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'peserta', onPageChange: muatDaftarTrip,
    });
    document.getElementById('pd-total-perjadin').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}
window.muatDaftarTrip = muatDaftarTrip;

/** Tabel datar 1 baris = 1 peserta. Kolom "No"/"Perjalanan Dinas" (+ aksi trip) hanya
 *  ditampilkan pada baris pertama tiap trip (baris-baris berikutnya dari trip yang sama
 *  dikosongkan) supaya trip dengan banyak peserta tidak mengulang info yang sama. */
function renderTripTable(rows) {
  currentRows = rows;
  const tbody = document.getElementById('pd-tbody-perjadin');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  let lastTripId = null;
  let noTrip = 0;
  tbody.innerHTML = rows.map((r, idx) => {
    const tripBaru = r.perjalanan_dinas_id !== lastTripId;
    if (tripBaru) { lastTripId = r.perjalanan_dinas_id; noTrip++; }

    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const biayaLain = (parseFloat(r.meeting_fullboard) || 0) + (parseFloat(r.meeting_fullday) || 0) + (parseFloat(r.uang_representasi) || 0) + (parseFloat(r.transport_lokal) || 0) + (parseFloat(r.bbm) || 0);
    const totalTiket = (r.tiket || []).reduce((s, t) => s + (parseFloat(t.harga_tiket) || 0), 0);
    const totalHotel = r.hotel ? ((parseFloat(r.hotel.total_bill) || 0) + (parseFloat(r.hotel.total_biaya_30persen) || 0)) : 0;
    const statusBadge = r.status_lunas === 'lunas'
      ? '<span class="badge badge-success">Lunas</span>'
      : r.status_lunas === 'sebagian'
      ? `<span class="badge badge-info" title="Disetor ${rupiah(r.jumlah_disetor)} dari ${rupiah(r.dana_taktis)}">Sebagian</span>`
      : '<span class="badge badge-warning">Belum Lunas</span>';
    const aksiLunas = r.status_lunas === 'belum'
      ? `<button type="button" class="mt-1 p-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white" onclick="bukaModalLunas(${r.id})" title="Kelola Setoran">${iconsax('check', 'w-3.5 h-3.5')}</button>`
      : `<button type="button" class="block text-[11px] text-primary-600 hover:underline mt-0.5" onclick="bukaModalLunas(${r.id})">${r.status_lunas === 'sebagian' ? 'lanjutkan' : 'ubah'}</button>` +
        `<button type="button" class="block text-[11px] text-slate-400 hover:text-red-600 mt-0.5" onclick="batalkanLunas(${r.id})">batalkan</button>`;

    const selTrip = tripBaru ? `
      <td class="align-top font-semibold text-slate-500 dark:text-slate-400">${noTrip}</td>
      <td class="align-top">
        <div class="flex items-start gap-1">
          <div class="min-w-0">
            <p class="font-medium text-slate-800 dark:text-slate-100 max-w-[240px] truncate" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">${escapeHtml(r.no_surat_tugas || '-')} &middot; ${tgl}</p>
          </div>
          <div class="flex items-center gap-0.5 shrink-0">
            <button type="button" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-primary-600" title="Tambah peserta" onclick="bukaModalPeserta(${r.perjalanan_dinas_id})">${iconsax('user-add', 'w-3.5 h-3.5')}</button>
            <button type="button" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-primary-600" title="Edit perjalanan dinas" onclick="editTripByIdx(${idx})">${iconsax('edit-2', 'w-3.5 h-3.5')}</button>
            <button type="button" class="p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-400 hover:text-red-600" title="Hapus perjalanan dinas" onclick="hapusTrip(${r.perjalanan_dinas_id})">${iconsax('trash', 'w-3.5 h-3.5')}</button>
          </div>
        </div>
      </td>` : `<td></td><td></td>`;

    return `<tr class="${tripBaru ? 'border-t-2 border-slate-100 dark:border-slate-800' : ''}">
      ${selTrip}
      <td class="font-medium text-slate-700 dark:text-slate-200 max-w-[200px] truncate" title="${escapeHtml(r.nama_peserta)}">${escapeHtml(r.nama_peserta)}</td>
      <td class="text-right text-currency">${r.uang_harian > 0 ? rupiah(r.uang_harian) : '-'}</td>
      <td class="text-right text-currency font-semibold">${rupiah(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600">${rupiah(r.dana_taktis)}</td>
      <td class="whitespace-nowrap">${statusBadge}${aksiLunas}</td>
      <td class="text-center">
        <div class="flex items-center justify-center gap-1">
          <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Edit peserta" onclick="editPesertaByIdx(${idx})">${iconsax('edit-2', 'w-4 h-4')}</button>
          <button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" title="Hapus peserta" onclick="hapusPeserta(${r.id})">${iconsax('trash', 'w-4 h-4')}</button>
        </div>
      </td>
      <td class="text-right text-currency">${biayaLain > 0 ? rupiah(biayaLain) : '-'}</td>
      <td class="text-right text-currency">${(r.tiket || []).length > 0 ? ((r.tiket.length) + 'x &middot; ' + rupiah(totalTiket)) : '-'}</td>
      <td class="text-right text-currency">${r.hotel ? (escapeHtml(r.hotel.nama_hotel || 'Hotel') + '<br><span class="text-xs">' + rupiah(totalHotel) + '</span>') : '-'}</td>
    </tr>`;
  }).join('');
}

function editTripByIdx(idx) {
  const r = currentRows[idx];
  editTrip({ id: r.perjalanan_dinas_id, maksud: r.maksud, tanggal_surat_tugas: r.tanggal_surat_tugas, no_surat_tugas: r.no_surat_tugas, kode_mak: r.kode_mak, no_spm: r.no_spm });
}
window.editTripByIdx = editTripByIdx;
function editPesertaByIdx(idx) { editPeserta(currentRows[idx]); }
window.editPesertaByIdx = editPesertaByIdx;

// ── Trip (header) ──────────────────────────────────────────────────────────────

function bukaModalTrip() {
  document.getElementById('form-trip').reset();
  document.getElementById('trip-id').value = '';
  document.getElementById('trip-modal-title').innerHTML = iconsax('airplane', 'w-5 h-5 text-primary-600') + ' Perjalanan Dinas Baru';
  openModal('modal-trip');
}
window.bukaModalTrip = bukaModalTrip;

function editTrip(trip) {
  document.getElementById('trip-id').value = trip.id;
  document.getElementById('trip-maksud').value = trip.maksud;
  document.getElementById('trip-tanggal').value = trip.tanggal_surat_tugas;
  document.getElementById('trip-no-surat').value = trip.no_surat_tugas || '';
  document.getElementById('trip-kode-mak').value = trip.kode_mak || '';
  document.getElementById('trip-no-spm').value = trip.no_spm || '';
  document.getElementById('trip-modal-title').innerHTML = iconsax('edit-2', 'w-5 h-5 text-primary-600') + ' Edit Perjalanan Dinas';
  openModal('modal-trip');
}
async function submitTrip(e) {
  e.preventDefault();
  const id = document.getElementById('trip-id').value;
  const url = id ? API + '/update/' + id : API;
  const res = await fetch(url, { method: 'POST', body: buildFormData(document.getElementById('form-trip')) });
  const json = await res.json();
  if (json.success) {
    showToast(json.message, 'success');
    closeModal('modal-trip');
    // Trip baru: langsung lanjut ke form Tambah Peserta supaya alurnya menyatu, tidak perlu
    // cari-cari barisnya lagi di tabel (bisa ratusan baris) untuk tambah peserta pertama.
    if (!id && json.id) bukaModalPeserta(json.id);
    setTimeout(muatDaftarTrip, 800);
  }
  else showToast(json.message || 'Gagal menyimpan', 'error');
}
window.submitTrip = submitTrip;
function hapusTrip(id) {
  tampilkanKonfirmasi('Hapus perjalanan dinas ini beserta seluruh peserta, tiket, dan hotelnya? Setoran Dana Taktis yang sudah lunas juga akan dibatalkan.', async () => {
    const res = await fetch(API + '/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(muatDaftarTrip, 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
  });
}
window.hapusTrip = hapusTrip;

// ── Peserta (+ tiket & hotel) ────────────────────────────────────────────────────

function kosongkanFormPeserta() {
  document.getElementById('form-peserta').reset();
  document.getElementById('peserta-id').value = '';
  document.getElementById('tiket-rows').innerHTML = '';
  tiketIdx = 0;
  pilihStatusLunas('belum');
  hitungPreviewPeserta();
}

/* ── Status Setoran Dana Taktis (dipilih langsung di form Tambah/Edit Peserta) ── */
function pilihStatusLunas(status) {
  document.getElementById('peserta-status-lunas').value = status;
  document.querySelectorAll('.status-lunas-btn').forEach(b => b.classList.toggle('active', b.dataset.status === status));
  document.getElementById('peserta-status-detail').classList.toggle('hidden', status === 'belum');
  document.getElementById('peserta-jumlah-disetor-wrap').classList.toggle('hidden', status !== 'sebagian');
  const tglInput = document.getElementById('peserta-tanggal-setoran');
  if (status !== 'belum' && !tglInput.value) tglInput.value = new Date().toISOString().slice(0, 10);
}
window.pilihStatusLunas = pilihStatusLunas;

function bukaModalPeserta(tripId) {
  kosongkanFormPeserta();
  document.getElementById('peserta-trip-id').value = tripId;
  document.getElementById('peserta-modal-title').innerHTML = iconsax('user-add', 'w-5 h-5 text-primary-600') + ' Tambah Peserta';
  document.getElementById('btn-simpan-tambah-lagi').classList.remove('hidden');
  openModal('modal-peserta');
}
window.bukaModalPeserta = bukaModalPeserta;

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
window.tambahBarisTiket = tambahBarisTiket;

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
  pilihStatusLunas(p.status_lunas || 'belum');
  if (p.status_lunas === 'sebagian') setRupiahValue(document.getElementById('peserta-jumlah-disetor'), p.jumlah_disetor);
  if (p.status_lunas !== 'belum' && p.tanggal_lunas) document.getElementById('peserta-tanggal-setoran').value = p.tanggal_lunas;
  document.getElementById('peserta-modal-title').innerHTML = iconsax('edit-2', 'w-5 h-5 text-primary-600') + ' Edit Peserta: ' + p.nama_peserta;
  document.getElementById('btn-simpan-tambah-lagi').classList.add('hidden');
  hitungPreviewPeserta();
  openModal('modal-peserta');
}

async function submitPeserta(e, tambahLagi) {
  e.preventDefault();
  const id = document.getElementById('peserta-id').value;
  const tripId = document.getElementById('peserta-trip-id').value;
  const url = id ? API + '/peserta/update/' + id : API + '/peserta';
  const res = await fetch(url, { method: 'POST', body: buildFormData(document.getElementById('form-peserta')) });
  const json = await res.json();
  if (json.success) {
    showToast(json.message, 'success');
    if (tambahLagi && !id) bukaModalPeserta(tripId);
    else closeModal('modal-peserta');
    setTimeout(muatDaftarTrip, 800);
  }
  else showToast(json.message || 'Gagal menyimpan', 'error');
}
window.submitPeserta = submitPeserta;

function hapusPeserta(id) {
  tampilkanKonfirmasi('Hapus peserta ini beserta tiket & hotelnya?', async () => {
    const res = await fetch(API + '/peserta/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(muatDaftarTrip, 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
  });
}
window.hapusPeserta = hapusPeserta;

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
window.hitungPreviewPeserta = hitungPreviewPeserta;

// ── Status Lunas ─────────────────────────────────────────────────────────────────

let lunasStatusDipilih = 'lunas';
function pilihStatusModalLunas(status) {
  lunasStatusDipilih = status;
  document.querySelectorAll('.lunas-status-btn').forEach(b => b.classList.toggle('active', b.dataset.status === status));
  document.getElementById('lunas-jumlah-wrap').classList.toggle('hidden', status !== 'sebagian');
}
window.pilihStatusModalLunas = pilihStatusModalLunas;

function bukaModalLunas(pesertaId) {
  const r = currentRows.find(row => row.id === pesertaId);
  document.getElementById('lunas-peserta-id').value = pesertaId;
  document.getElementById('lunas-tanggal').value = new Date().toISOString().slice(0, 10);
  document.getElementById('lunas-dana-taktis-info').textContent = r ? rupiah(r.dana_taktis) : '-';
  document.getElementById('lunas-jumlah-disetor').value = '';
  pilihStatusModalLunas('lunas');
  openModal('modal-lunas');
}
window.bukaModalLunas = bukaModalLunas;

async function konfirmasiLunas() {
  const id = document.getElementById('lunas-peserta-id').value;
  const fd = new FormData();
  fd.append('aksi', lunasStatusDipilih);
  fd.append('tanggal_lunas', document.getElementById('lunas-tanggal').value);
  if (lunasStatusDipilih === 'sebagian') {
    fd.append('jumlah_disetor', unformatRibuan(document.getElementById('lunas-jumlah-disetor').value));
  }
  const res = await fetch(API + '/lunas/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-lunas'); setTimeout(muatDaftarTrip, 800); }
  else showToast(json.message || 'Gagal', 'error');
}
window.konfirmasiLunas = konfirmasiLunas;

function batalkanLunas(pesertaId) {
  tampilkanKonfirmasi('Batalkan status setoran? Pemasukan otomatis yang sudah tercatat akan ikut dihapus.', async () => {
    const fd = new FormData();
    fd.append('aksi', 'batal');
    const res = await fetch(API + '/lunas/' + pesertaId, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(muatDaftarTrip, 800); }
    else showToast(json.message || 'Gagal', 'error');
  });
}
window.batalkanLunas = batalkanLunas;

// ── Master Data Pegawai ──────────────────────────────────────────────────────────

async function muatDaftarPegawai() {
  const res = await fetch(API + '/pegawai');
  const json = await res.json();
  const tbody = document.getElementById('pegawai-tbody');
  tbody.innerHTML = '';
  (json.data || []).forEach(pg => {
    const tr = document.createElement('tr');
    renderBarisPegawai(tr, pg);
    tbody.appendChild(tr);
  });
}
window.muatDaftarPegawai = muatDaftarPegawai;

function renderBarisPegawai(tr, pg) {
  const pgJson = JSON.stringify(pg).replace(/'/g, "&#39;");
  tr.innerHTML = `<td>${escapeHtml(pg.nama)}</td><td>${escapeHtml(pg.nip || '-')}</td>` +
    `<td>${pg.aktif == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>'}</td>` +
    `<td class="text-center">
      <div class="flex items-center justify-center gap-1">
        <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" onclick='editBarisPegawai(this, ${pgJson})' title="Edit">${iconsax('edit-2', 'w-4 h-4')}</button>
        ${pg.aktif == 1
          ? `<button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" onclick="nonaktifkanPegawai(${pg.id})" title="Nonaktifkan">${iconsax('user-remove', 'w-4 h-4')}</button>`
          : `<button type="button" class="p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-slate-500 hover:text-emerald-600" onclick='aktifkanPegawai(${pgJson})' title="Aktifkan kembali">${iconsax('tick-circle', 'w-4 h-4')}</button>`}
      </div>
    </td>`;
}

function editBarisPegawai(btn, pg) {
  const tr = btn.closest('tr');
  tr.innerHTML = `
    <td><input type="text" class="form-control form-control-sm" value="${escapeHtml(pg.nama)}" id="edit-pegawai-nama-${pg.id}"></td>
    <td><input type="text" class="form-control form-control-sm" value="${escapeHtml(pg.nip || '')}" id="edit-pegawai-nip-${pg.id}"></td>
    <td>${pg.aktif == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>'}</td>
    <td class="text-center">
      <div class="flex items-center justify-center gap-1">
        <button type="button" class="p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-slate-500 hover:text-emerald-600" onclick="simpanEditPegawai(${pg.id}, ${pg.aktif})" title="Simpan">${iconsax('tick-circle', 'w-4 h-4')}</button>
        <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500" onclick="muatDaftarPegawai()" title="Batal">${iconsax('close-circle', 'w-4 h-4')}</button>
      </div>
    </td>`;
}
window.editBarisPegawai = editBarisPegawai;

async function simpanEditPegawai(id, aktifSaatIni) {
  const nama = document.getElementById('edit-pegawai-nama-' + id).value.trim();
  const nip  = document.getElementById('edit-pegawai-nip-' + id).value.trim();
  if (!nama) { showToast('Nama tidak boleh kosong', 'error'); return; }
  const fd = new FormData();
  fd.append('nama', nama);
  fd.append('nip', nip);
  fd.append('aktif', aktifSaatIni);
  const res = await fetch(API + '/pegawai/update/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
  else showToast(json.message || 'Gagal', 'error');
}
window.simpanEditPegawai = simpanEditPegawai;

function bukaModalPegawai() {
  openModal('modal-pegawai');
  muatDaftarPegawai();
}
window.bukaModalPegawai = bukaModalPegawai;

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
window.tambahPegawai = tambahPegawai;

async function nonaktifkanPegawai(id) {
  const res = await fetch(API + '/pegawai/delete/' + id, { method: 'POST' });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
}
window.nonaktifkanPegawai = nonaktifkanPegawai;

async function aktifkanPegawai(pg) {
  const fd = new FormData();
  fd.append('nama', pg.nama);
  fd.append('nip', pg.nip || '');
  fd.append('aktif', '1');
  const res = await fetch(API + '/pegawai/update/' + pg.id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast('Pegawai diaktifkan kembali', 'success'); await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
  else showToast(json.message || 'Gagal', 'error');
}
window.aktifkanPegawai = aktifkanPegawai;

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
})();
</script>
