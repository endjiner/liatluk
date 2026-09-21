<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="mb-3 flex items-center justify-end gap-2 flex-wrap">
  <button type="button" class="btn btn-outline btn-sm gap-1.5 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800" onclick="exportPerjadin('excel')">
    <?= iconsax('document-text', 'w-4 h-4 text-emerald-600') ?> Export Excel
  </button>
  <button type="button" class="btn btn-outline btn-sm gap-1.5 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 border-rose-300 dark:border-rose-800" onclick="exportPerjadin('pdf')">
    <?= iconsax('document-text', 'w-4 h-4 text-rose-600') ?> Export PDF
  </button>
  <button class="btn btn-outline btn-sm" onclick="bukaModalPegawai()">
    <?= iconsax('people', '') ?> Kelola Pegawai
  </button>
  <button class="btn btn-success btn-sm" onclick="bukaModalTrip()">
    <?= iconsax('add', '') ?> Tambah Perjalanan Dinas
  </button>
</div>

<!-- Filter -->
<div class="card mb-4">
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
        <input type="text" id="filter-search-perjadin" oninput="jadwalkanMuatDaftar()" placeholder="Nama, maksud, no surat tugas, MAK..." class="form-control form-control-sm pl-8 w-full">
      </div>
      <input type="hidden" id="filter-status-perjadin" value="">
      <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Perjalanan Dinas">
        <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
          <input type="checkbox" id="pd-adm-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterAdmPd('belum')">
          <span class="chip-box">
            <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
              <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
            </svg>
          </span>
          <span class="chip-dot bg-amber-500"></span>
          <span>Belum Lunas</span>
        </label>
        <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
          <input type="checkbox" id="pd-adm-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterAdmPd('lunas')">
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
      </div>
      <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
        <span>Tampilkan</span>
        <select id="filter-per-page-perjadin" onchange="muatDaftarTrip(1)" class="form-control form-control-sm w-auto">
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
          <th class="min-w-[110px] whitespace-nowrap">Status</th>
          <th class="w-24 min-w-[90px] text-center whitespace-nowrap">Aksi</th>
        </tr>
      </thead>
      <tbody id="pd-tbody-perjadin">
        <tr><td colspan="15" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
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
            <textarea name="maksud" id="trip-maksud" class="form-control" rows="2" required placeholder="Contoh: Perjalanan Dinas dalam Rangka Kegiatan Koordinasi ke Badan POM di Jakarta Pusat Selama 3 (tiga) hari pada tanggal 01 s/d 03 Januari 2026"></textarea></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label class="form-label">No. PD <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(misal: 151 atau 52 (UP))</span></label>
              <input type="text" name="no_pd" id="trip-no-pd" class="form-control" placeholder="Contoh: 151 atau 52 (UP)"></div>
            <div><label class="form-label">Tgl. Surat Tugas <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal_surat_tugas" id="trip-tanggal" class="form-control" required></div>
            <div><label class="form-label">No. Surat Tugas</label>
              <input type="text" name="no_surat_tugas" id="trip-no-surat" class="form-control" placeholder="HM.03.01.7B.01.26.01"></div>
            <div><label class="form-label">Kode MAK</label>
              <input type="text" name="kode_mak" id="trip-kode-mak" class="form-control" placeholder="6384.EBA.994.002.524111.R"></div>
            <div class="sm:col-span-2"><label class="form-label">No. SPM</label>
              <input type="text" name="no_spm" id="trip-no-spm" class="form-control" placeholder="No. SPM"></div>
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

<!-- Modal Tambah/Edit Peserta (Pelaksana Perjalanan Dinas) -->
<div id="modal-peserta" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-peserta')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg" style="max-width:52rem">
      <div class="modal-header">
        <div><h3 class="modal-title" id="peserta-modal-title"><?= iconsax('user-add', 'w-5 h-5 text-primary-600') ?> Tambah Pelaksana Perjalanan Dinas</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-peserta')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-peserta" onsubmit="submitPeserta(event)">
        <input type="hidden" id="peserta-id">
        <input type="hidden" name="perjalanan_dinas_id" id="peserta-trip-id">
        <div class="modal-body space-y-5" style="max-height:65vh;overflow-y:auto">

          <div>
            <label class="form-label">Nama Pelaksana Perjalanan Dinas <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(TANPA GELAR AKADEMIK)</span> <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <select name="pegawai_id" id="peserta-pegawai-id" class="form-control pegawai-select" required oninput="hitungPreviewPeserta()">
                <option value="">Pilih Nama Pelaksana Perjalanan Dinas...</option>
                <?php foreach ($pegawaiList as $pg): ?>
                <option value="<?= $pg['id'] ?>"><?= esc($pg['nama']) ?><?= $pg['nip'] ? ' — ' . esc($pg['nip']) : '' ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-outline btn-sm shrink-0" onclick="bukaModalPegawai()" title="Tambah pegawai baru"><?= iconsax('add', '') ?></button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label class="form-label text-xs">Uang Harian</label>
              <input type="text" inputmode="numeric" name="uang_harian" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
            <div>
              <label class="form-label text-xs">Biaya Paket Meeting Fullboard</label>
              <input type="text" inputmode="numeric" name="meeting_fullboard" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
            <div>
              <label class="form-label text-xs">Biaya Paket Meeting Fullday</label>
              <input type="text" inputmode="numeric" name="meeting_fullday" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
            <div>
              <label class="form-label text-xs">Uang Representasi (Eselon II)</label>
              <input type="text" inputmode="numeric" name="uang_representasi" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
            <div>
              <label class="form-label text-xs">Transportasi Lokal / Transportasi Luar Kota / Taksi</label>
              <input type="text" inputmode="numeric" name="transport_lokal" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
            <div>
              <label class="form-label text-xs">BBM (Jika Jalan Darat)</label>
              <input type="text" inputmode="numeric" name="bbm" class="form-control input-rupiah nominal-peserta" placeholder="0" oninput="hitungPreviewPeserta()">
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="form-label mb-0 font-semibold">Tiket Pesawat</label>
              <button type="button" class="btn btn-outline btn-sm" onclick="tambahBarisTiket()"><?= iconsax('add', '') ?> Tambah Tiket</button>
            </div>
            <div id="tiket-rows" class="space-y-2"></div>
          </div>

          <div>
            <label class="form-label font-semibold">Data Hotel (opsional)</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
              <div>
                <span class="form-hint text-[11px]">Nama Hotel</span>
                <input type="text" name="hotel[nama_hotel]" id="hotel-nama" class="form-control form-control-sm" placeholder="Nama Hotel">
              </div>
              <div class="sm:col-span-2">
                <span class="form-hint text-[11px]">Alamat Hotel</span>
                <input type="text" name="hotel[alamat_hotel]" class="form-control form-control-sm" placeholder="Alamat Hotel">
              </div>
              <div>
                <span class="form-hint text-[11px]">No. Telepon Hotel</span>
                <input type="text" name="hotel[telp_hotel]" class="form-control form-control-sm" placeholder="No. Telepon Hotel">
              </div>
              <div>
                <span class="form-hint text-[11px]">Tanggal Check-In Hotel (Arrival Date)</span>
                <input type="date" name="hotel[checkin]" class="form-control form-control-sm">
              </div>
              <div>
                <span class="form-hint text-[11px]">Tanggal Check-Out Hotel (Departure Date)</span>
                <input type="date" name="hotel[checkout]" class="form-control form-control-sm">
              </div>
              <div>
                <span class="form-hint text-[11px]">No Kamar (Room)</span>
                <input type="text" name="hotel[no_kamar]" class="form-control form-control-sm" placeholder="No Kamar (Room)">
              </div>
              <div>
                <span class="form-hint text-[11px]">No. Invoice Hotel</span>
                <input type="text" name="hotel[no_invoice]" class="form-control form-control-sm" placeholder="No. Invoice Hotel">
              </div>
              <div>
                <span class="form-hint text-[11px]">Total Bill Hotel Yang Dibayarkan</span>
                <input type="text" inputmode="numeric" name="hotel[total_bill]" class="form-control form-control-sm input-rupiah nominal-hotel" placeholder="0" oninput="hitungPreviewPeserta()">
              </div>
              <div>
                <span class="form-hint text-[11px]">Total Biaya Hotel (Jika 30%)</span>
                <input type="text" inputmode="numeric" name="hotel[total_biaya_30persen]" class="form-control form-control-sm input-rupiah nominal-hotel" placeholder="0" oninput="hitungPreviewPeserta()">
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 text-xs border border-slate-200 dark:border-slate-700">
            <div>
              <div class="text-slate-500 dark:text-slate-400 font-medium">TOTAL SPJ YANG DIBAYARKAN OLEH BENDAHARA PENGELUARAN</div>
              <div class="font-bold text-base text-slate-800 dark:text-slate-100" id="preview-total-spj">Rp 0</div>
            </div>
            <div>
              <div class="text-slate-500 dark:text-slate-400 font-medium">TAKTIS (10% DARI UANG HARIAN)</div>
              <div class="font-bold text-base text-emerald-600" id="preview-dana-taktis">Rp 0</div>
            </div>
          </div>

          <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-700">
            <label class="form-label mb-2 font-semibold">Status Setoran Dana Taktis (LUNAS)</label>
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
          <button type="submit" class="btn btn-primary"><?= iconsax('save-2', '') ?> Simpan Pelaksana</button>
        </div>
      </form>
    </div>
  </div>
</div>

<template id="tiket-row-template">
  <div class="tiket-row grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-lg border border-slate-200 dark:border-slate-700 relative bg-slate-50/50 dark:bg-slate-900/30">
    <button type="button" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-rose-100 hover:bg-rose-200 text-rose-600 border border-rose-200 flex items-center justify-center text-xs font-bold transition shrink-0" onclick="this.closest('.tiket-row').remove(); hitungPreviewPeserta()">&times;</button>
    <div>
      <span class="form-hint text-[11px]">Maskapai</span>
      <input type="text" name="tiket[__IDX__][maskapai]" class="form-control form-control-sm" placeholder="Maskapai">
    </div>
    <div>
      <span class="form-hint text-[11px]">Pergi/Pulang</span>
      <select name="tiket[__IDX__][arah]" class="form-control form-control-sm">
        <option value="pergi">Pergi</option>
        <option value="pulang">Pulang</option>
      </select>
    </div>
    <div>
      <span class="form-hint text-[11px]">No Tiket</span>
      <input type="text" name="tiket[__IDX__][no_tiket]" class="form-control form-control-sm" placeholder="No Tiket">
    </div>
    <div>
      <span class="form-hint text-[11px]">Kode Booking</span>
      <input type="text" name="tiket[__IDX__][kode_booking]" class="form-control form-control-sm" placeholder="Kode Booking">
    </div>
    <div>
      <span class="form-hint text-[11px]">No Penerbangan</span>
      <input type="text" name="tiket[__IDX__][no_penerbangan]" class="form-control form-control-sm" placeholder="No Penerbangan">
    </div>
    <div>
      <span class="form-hint text-[11px]">Tempat Asal</span>
      <input type="text" name="tiket[__IDX__][tempat_asal]" class="form-control form-control-sm" placeholder="Tempat Asal">
    </div>
    <div>
      <span class="form-hint text-[11px]">Tempat Tujuan</span>
      <input type="text" name="tiket[__IDX__][tempat_tujuan]" class="form-control form-control-sm" placeholder="Tempat Tujuan">
    </div>
    <div>
      <span class="form-hint text-[11px]">Tanggal Terbang</span>
      <input type="date" name="tiket[__IDX__][tanggal_terbang]" class="form-control form-control-sm">
    </div>
    <div class="col-span-2 sm:col-span-4">
      <span class="form-hint text-[11px]">Harga Tiket (Rp)</span>
      <input type="text" inputmode="numeric" name="tiket[__IDX__][harga_tiket]" class="form-control form-control-sm input-rupiah nominal-tiket" placeholder="0" oninput="hitungPreviewPeserta()">
    </div>
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
        <div class="flex items-center gap-2 mb-3">
          <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?></span>
            <input type="text" id="pegawai-search" oninput="filterPegawai()" placeholder="Cari nama / NIP pegawai..." class="form-control form-control-sm pl-8">
          </div>
          <button type="button" class="btn btn-outline btn-sm" onclick="toggleFormTambahPegawai()"><?= iconsax('add', '') ?> Tambah</button>
        </div>
        <form id="form-tambah-pegawai" onsubmit="tambahPegawai(event)" class="hidden flex flex-wrap gap-2 mb-3 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700">
          <input type="text" id="pegawai-baru-nama" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="Nama pegawai" required>
          <input type="text" id="pegawai-baru-nip" class="form-control form-control-sm flex-1 min-w-[140px]" placeholder="NIP (opsional)">
          <button type="submit" class="btn btn-primary btn-sm"><?= iconsax('add', '') ?> Simpan</button>
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
<!-- Modal Detail Perjalanan Dinas (Admin) -->
<div id="modal-detail-perjadin-adm" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-detail-perjadin-adm')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg" style="max-width:50rem">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('airplane', 'w-5 h-5 text-primary-600') ?> Detail Perjalanan Dinas</h3>
          <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap" id="perjadin-adm-detail-subtitle"></div>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-detail-perjadin-adm')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body space-y-4" id="perjadin-adm-detail-body" style="max-height:75vh;overflow-y:auto"></div>
      <div class="modal-footer flex items-center justify-between">
        <div id="perjadin-adm-detail-actions" class="flex items-center gap-2"></div>
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-detail-perjadin-adm')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?= $this->include('admin/_partials/tab_perjadin_scripts') ?>