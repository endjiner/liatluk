<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="mb-3 flex items-center justify-end gap-2">
  <button class="btn btn-outline btn-sm" onclick="bukaModalPegawai()">
    <?= iconsax('people', '') ?> Kelola Pegawai
  </button>
  <button class="btn btn-success btn-sm" onclick="bukaModalTrip()">
    <?= iconsax('add', '') ?> Perjalanan Dinas
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

<script>
(function() {
const BASE = '<?= base_url() ?>';
const API  = BASE + 'admin/perjalanan-dinas';
let tiketIdx = 0;
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

// ── Filter & Search (AJAX, tabel datar 1 baris = 1 peserta) ───────────────────────
let filterDebounceTimer = null;
let halamanTripSaatIni = null;
let currentRows = [];
/* Toggle Checkbox Status Perjalanan Dinas (Admin) */
function toggleStatusFilterAdmPd(changedType) {
  const chkBelum = document.getElementById('pd-adm-chk-belum');
  const chkLunas = document.getElementById('pd-adm-chk-lunas');
  if (!chkBelum || !chkLunas) return;

  if (!chkBelum.checked && !chkLunas.checked) {
    chkBelum.checked = true;
    chkLunas.checked = true;
  }

  const chipBelum = chkBelum.closest('.status-chip');
  const chipLunas = chkLunas.closest('.status-chip');
  if (chipBelum) chipBelum.classList.toggle('is-checked', chkBelum.checked);
  if (chipLunas) chipLunas.classList.toggle('is-checked', chkLunas.checked);

  let statusVal = '';
  if (chkBelum.checked && !chkLunas.checked) statusVal = 'belum';
  else if (!chkBelum.checked && chkLunas.checked) statusVal = 'lunas';
  else statusVal = '';

  const input = document.getElementById('filter-status-perjadin');
  if (input) input.value = statusVal;
  muatDaftarTrip(1);
}
window.toggleStatusFilterAdmPd = toggleStatusFilterAdmPd;

function jadwalkanMuatDaftar() {
  clearTimeout(filterDebounceTimer);
  filterDebounceTimer = setTimeout(() => muatDaftarTrip(1), 350);
}
window.jadwalkanMuatDaftar = jadwalkanMuatDaftar;

// Nomor urut request — kalau user ketik cepat, respons yang lebih lama (mis. dari huruf
// pertama) bisa balik BELAKANGAN dari respons huruf terakhir dan menimpa hasil yang lebih
// baru dengan yang basi. Cuma respons dari request PALING TERAKHIR yang boleh dirender.
let tripRequestSeq = 0;

async function muatDaftarTrip(page) {
  if (page !== undefined && page !== null) {
    halamanTripSaatIni = page;
  }
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
  if (halamanTripSaatIni) params.set('page', halamanTripSaatIni);

  const tbody = document.getElementById('pd-tbody-perjadin');
  const seq = ++tripRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="15" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res = await fetch(API + '/ajax?' + params.toString());
    const json = await res.json();
    if (seq !== tripRequestSeq) return; // ada request lebih baru yang menyusul, respons ini basi
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="15" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    halamanTripSaatIni = json.page;
    renderTripTable(json.data);
    renderPaginasiHalaman(document.getElementById('pd-pagination-wrap-perjadin'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'peserta', onPageChange: muatDaftarTrip,
    });
    document.getElementById('pd-total-perjadin').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== tripRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="15" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
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
    tbody.innerHTML = '<tr><td colspan="15" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  // Hitung jumlah peserta per perjalanan dinas untuk merge (rowspan)
  const tripCounts = {};
  rows.forEach(r => {
    tripCounts[r.perjalanan_dinas_id] = (tripCounts[r.perjalanan_dinas_id] || 0) + 1;
  });

  let lastTripId = null;
  let noTrip = 0;
  tbody.innerHTML = rows.map((r, idx) => {
    const tripBaru = r.perjalanan_dinas_id !== lastTripId;
    if (tripBaru) { lastTripId = r.perjalanan_dinas_id; noTrip++; }

    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const totalTiket = (r.tiket || []).reduce((s, t) => s + (parseFloat(t.harga_tiket) || 0), 0);
    const totalHotel = r.hotel ? ((parseFloat(r.hotel.total_bill) || 0) + (parseFloat(r.hotel.total_biaya_30persen) || 0)) : 0;
    const taktisVal = parseFloat(r.dana_taktis) || 0;
    const isZeroTaktis = taktisVal <= 0;
    const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
    const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
    const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
    const statusBadge = isZeroTaktis
      ? `<span class="badge badge-success" title="Tanpa tagihan dana taktis">Lunas</span>`
      : (isLunas
        ? `<span class="badge badge-success">Lunas</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
        : (isSebagian
          ? `<span class="badge badge-info" title="Disetor ${rupiah(r.jumlah_disetor)} dari ${rupiah(r.dana_taktis)}">Sebagian</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
          : '<span class="badge badge-warning">Belum Lunas</span>'));

    const aksiLunas = isZeroTaktis
      ? `<div class="text-[11px] text-slate-400 italic mt-0.5">Tanpa Taktis</div>`
      : (r.status_lunas === 'belum'
        ? `<button type="button" class="mt-1 p-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80" onclick="event.stopPropagation(); bukaModalLunas(${r.id})" title="Kelola Setoran">${iconsax('check', 'w-3.5 h-3.5')}</button>`
        : `<button type="button" class="block text-[11px] text-primary-600 hover:underline mt-0.5" onclick="event.stopPropagation(); bukaModalLunas(${r.id})">${r.status_lunas === 'sebagian' ? 'lanjutkan' : 'ubah'}</button>` +
          `<button type="button" class="block text-[11px] text-slate-400 hover:text-red-600 mt-0.5" onclick="event.stopPropagation(); batalkanLunas(${r.id})">batalkan</button>`);

    const count = tripCounts[r.perjalanan_dinas_id] || 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80 dark:border-amber-700/80 shadow-xs">${escapeHtml(r.no_pd)}</span>`
          : `<span class="text-sm font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(r.no_pd)}</span>`)
      : `<span class="text-xs text-slate-400 font-medium">${noTrip}</span>`;

    const selTrip = tripBaru ? `
      <td rowspan="${count}" class="align-top text-center bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">${nomorPdHtml}</td>
      <td rowspan="${count}" class="align-top bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">
        <div class="flex items-start gap-1">
          <div class="min-w-0">
            <p class="font-medium text-slate-800 dark:text-slate-100 break-words whitespace-normal" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 break-words">${escapeHtml(r.no_surat_tugas || '-')} &middot; ${tgl}</p>
            <p class="text-[11px] font-mono text-slate-600 dark:text-slate-300 mt-0.5 break-words"><span class="font-semibold text-slate-400 dark:text-slate-500">MAK:</span> ${escapeHtml(r.kode_mak || '-')}${r.no_spm ? ` &middot; <span class="font-semibold text-slate-400 dark:text-slate-500">SPM:</span> ${escapeHtml(r.no_spm)}` : ''}</p>
          </div>
          <div class="inline-flex items-center gap-0.5 shrink-0" onclick="event.stopPropagation()">
            <button type="button" class="w-6 h-6 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-primary-600 shrink-0" title="Tambah peserta" onclick="event.stopPropagation(); bukaModalPeserta(${r.perjalanan_dinas_id})">${iconsax('user-add', 'w-3.5 h-3.5 shrink-0')}</button>
            <button type="button" class="w-6 h-6 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-primary-600 shrink-0" title="Edit perjalanan dinas" onclick="event.stopPropagation(); editTripByIdx(${idx})">${iconsax('edit-2', 'w-3.5 h-3.5 shrink-0')}</button>
            <button type="button" class="w-6 h-6 inline-flex items-center justify-center rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-400 hover:text-red-600 shrink-0" title="Hapus perjalanan dinas" onclick="event.stopPropagation(); hapusTrip(${r.perjalanan_dinas_id})">${iconsax('trash', 'w-3.5 h-3.5 shrink-0')}</button>
          </div>
        </div>
      </td>` : '';

    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    return `<tr class="${tripBaru ? 'border-t-2 border-slate-200 dark:border-slate-700' : 'border-t border-slate-100 dark:border-slate-800/80'} ${rowBgClass} cursor-pointer transition-colors" onclick="showPerjadinModalAdm(${idx})">
      ${selTrip}
      <td class="font-medium text-slate-700 dark:text-slate-200 min-w-[140px] max-w-[220px] break-words whitespace-normal" title="${escapeHtml(r.nama_peserta)}">${escapeHtml(r.nama_peserta)}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_harian) > 0 ? rupiah(r.uang_harian) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullboard) > 0 ? rupiah(r.meeting_fullboard) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullday) > 0 ? rupiah(r.meeting_fullday) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_representasi) > 0 ? rupiah(r.uang_representasi) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.transport_lokal) > 0 ? rupiah(r.transport_lokal) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.bbm) > 0 ? rupiah(r.bbm) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${(r.tiket || []).length > 0 ? ((r.tiket.length) + 'x &middot; ' + rupiah(totalTiket)) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${r.hotel ? (escapeHtml(r.hotel.nama_hotel || 'Hotel') + '<br><span class="text-xs">' + rupiah(totalHotel) + '</span>') : '-'}</td>
      <td class="text-right text-currency font-semibold whitespace-nowrap">${rupiah(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600 whitespace-nowrap">${rupiah(r.dana_taktis)}</td>
      <td class="whitespace-nowrap min-w-[110px]" onclick="event.stopPropagation()">${statusBadge}${aksiLunas}</td>
      <td class="text-center whitespace-nowrap min-w-[90px]" onclick="event.stopPropagation()">
        <div class="inline-flex items-center justify-center gap-1.5">
          <button type="button" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600 shrink-0" title="Edit peserta" onclick="event.stopPropagation(); editPesertaByIdx(${idx})">${iconsax('edit-2', 'w-4 h-4 shrink-0')}</button>
          <button type="button" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600 shrink-0" title="Hapus peserta" onclick="event.stopPropagation(); hapusPeserta(${r.id})">${iconsax('trash', 'w-4 h-4 shrink-0')}</button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function showPerjadinModalAdm(idx) {
  const r = currentRows[idx];
  if (!r) return;
  const isUp = r.no_pd && r.no_pd.includes('UP');
  const noPdBadge = r.no_pd 
    ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${isUp ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}">No. PD: ${escapeHtml(r.no_pd)}</span>`
    : '';

  document.getElementById('perjadin-adm-detail-subtitle').innerHTML = `${noPdBadge} <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.nama_peserta || '')}</span>`;

  const taktisVal = parseFloat(r.dana_taktis) || 0;
  const isZeroTaktis = taktisVal <= 0;
  const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
  const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
  const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const statusBadge = isZeroTaktis
    ? `<span class="badge badge-success">Lunas (Tanpa Taktis)</span>`
    : (isLunas
      ? `<span class="badge badge-success">Lunas</span>${tglLunas ? ` <span class="text-xs text-slate-500">pada ${tglLunas}</span>` : ''}`
      : (isSebagian
        ? `<span class="badge badge-info">Sebagian (Disetor ${rupiah(r.jumlah_disetor)} dari ${rupiah(r.dana_taktis)})</span>`
        : '<span class="badge badge-warning">Belum Lunas</span>'));

  const tglST = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';

  // Tiket pesawat details
  let tiketHtml = '<span class="text-slate-400 italic">Tidak ada tiket pesawat</span>';
  if (r.tiket && r.tiket.length > 0) {
    tiketHtml = `<div class="space-y-2 mt-1">` + r.tiket.map(t => `
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs">
        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-200">
          <span>${escapeHtml(t.maskapai || 'Pesawat')} &middot; ${escapeHtml(t.nomor_penerbangan || '-')} (${escapeHtml(t.tipe || '-')})</span>
          <span class="text-emerald-600 font-semibold">${rupiah(t.harga_tiket)}</span>
        </div>
        <div class="text-slate-500 dark:text-slate-400 mt-1">
          Rute: ${escapeHtml(t.kota_asal || '-')} &rarr; ${escapeHtml(t.kota_tujuan || '-')} &middot; Tgl: ${t.tanggal_penerbangan ? new Date(t.tanggal_penerbangan).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'}
        </div>
        <div class="text-[11px] font-mono text-slate-400 dark:text-slate-500 mt-0.5">
          Tiket: ${escapeHtml(t.nomor_tiket || '-')} &middot; Booking: ${escapeHtml(t.kode_booking || '-')}
        </div>
      </div>
    `).join('') + `</div>`;
  }

  // Hotel details
  let hotelHtml = '<span class="text-slate-400 italic">Tidak ada penginapan / hotel</span>';
  if (r.hotel && (parseFloat(r.hotel.total_bill) > 0 || parseFloat(r.hotel.total_biaya_30persen) > 0 || r.hotel.nama_hotel)) {
    const totalH = (parseFloat(r.hotel.total_bill) || 0) + (parseFloat(r.hotel.total_biaya_30persen) || 0);
    hotelHtml = `
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs mt-1">
        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-200">
          <span>${escapeHtml(r.hotel.nama_hotel || 'Hotel')}</span>
          <span class="text-emerald-600 font-semibold">${rupiah(totalH)}</span>
        </div>
        <div class="text-slate-500 dark:text-slate-400 mt-1">
          Check-in: ${r.hotel.check_in ? new Date(r.hotel.check_in).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'} &middot; 
          Check-out: ${r.hotel.check_out ? new Date(r.hotel.check_out).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : '-'} &middot;
          No Kamar: ${escapeHtml(r.hotel.nomor_kamar || '-')}
        </div>
        ${r.hotel.alamat_telepon ? `<div class="text-slate-400 text-[11px] mt-0.5">${escapeHtml(r.hotel.alamat_telepon)}</div>` : ''}
      </div>
    `;
  }

  document.getElementById('perjadin-adm-detail-body').innerHTML = `
    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 space-y-2.5">
      <div>
        <div class="text-[11px] uppercase tracking-wide font-semibold text-slate-500 dark:text-slate-400">Maksud / Kegiatan Perjalanan Dinas</div>
        <div class="text-sm font-medium text-slate-800 dark:text-slate-100 mt-0.5">${escapeHtml(r.maksud || '-')}</div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
        <div><span class="text-slate-500">No. Surat Tugas:</span> <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_surat_tugas || '-')}</span></div>
        <div><span class="text-slate-500">Tgl. Surat Tugas:</span> <span class="font-medium text-slate-700 dark:text-slate-200">${tglST}</span></div>
        <div><span class="text-slate-500">Kode MAK:</span> <span class="font-mono font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.kode_mak || '-')}</span></div>
        <div><span class="text-slate-500">No. SPM:</span> <span class="font-mono font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_spm || '-')}</span></div>
      </div>
    </div>

    <div>
      <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">Rincian Komponen SPJ</h4>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Uang Harian</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.uang_harian)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullboard</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.meeting_fullboard)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullday</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.meeting_fullday)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Uang Representasi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.uang_representasi)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Transport Lokal / Taksi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.transport_lokal)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">BBM (Jalan Darat)</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${rupiah(r.bbm)}</div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Tiket Pesawat</h4>
        ${tiketHtml}
      </div>
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Penginapan / Hotel</h4>
        ${hotelHtml}
      </div>
    </div>

    <div class="p-3 rounded-xl bg-primary-50/60 dark:bg-primary-950/40 border border-primary-100 dark:border-primary-900/60 flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="text-xs text-primary-700 dark:text-primary-300 font-medium">Total SPJ Dibayarkan</div>
        <div class="text-lg font-bold text-primary-900 dark:text-primary-100">${rupiah(r.total_spj)}</div>
      </div>
      <div class="text-right">
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dana Taktis (10%)</div>
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${rupiah(r.dana_taktis)}</div>
        <div class="mt-1">${statusBadge}</div>
      </div>
    </div>
  `;

  let actionBtns = `
    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modal-detail-perjadin-adm'); editPesertaByIdx(${idx})">${iconsax('edit-2', 'w-3.5 h-3.5')} Edit Peserta</button>
    <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modal-detail-perjadin-adm'); editTripByIdx(${idx})">${iconsax('document-text', 'w-3.5 h-3.5')} Edit Perjadin</button>
  `;
  if (!isZeroTaktis) {
    actionBtns += `<button type="button" class="btn btn-primary btn-sm" onclick="closeModal('modal-detail-perjadin-adm'); bukaModalLunas(${r.id})">${iconsax('check', 'w-3.5 h-3.5')} Kelola Setoran</button>`;
  }
  document.getElementById('perjadin-adm-detail-actions').innerHTML = actionBtns;

  openModal('modal-detail-perjadin-adm');
}
window.showPerjadinModalAdm = showPerjadinModalAdm;

function editTripByIdx(idx) {
  const r = currentRows[idx];
  editTrip({ id: r.perjalanan_dinas_id, no_pd: r.no_pd, maksud: r.maksud, tanggal_surat_tugas: r.tanggal_surat_tugas, no_surat_tugas: r.no_surat_tugas, kode_mak: r.kode_mak, no_spm: r.no_spm });
}
window.editTripByIdx = editTripByIdx;
function editPesertaByIdx(idx) { editPeserta(currentRows[idx]); }
window.editPesertaByIdx = editPesertaByIdx;

// ── Trip (header) ──────────────────────────────────────────────────────────────

function bukaModalTrip() {
  document.getElementById('form-trip').reset();
  document.getElementById('trip-id').value = '';
  document.getElementById('trip-no-pd').value = '';
  document.getElementById('trip-modal-title').innerHTML = iconsax('airplane', 'w-5 h-5 text-primary-600') + ' Perjalanan Dinas Baru';
  openModal('modal-trip');
}
window.bukaModalTrip = bukaModalTrip;

function editTrip(trip) {
  document.getElementById('trip-id').value = trip.id;
  document.getElementById('trip-no-pd').value = trip.no_pd || '';
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
  document.getElementById('peserta-modal-title').innerHTML = iconsax('user-add', 'w-5 h-5 text-primary-600') + ' Tambah Pelaksana Perjalanan Dinas';
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
  document.getElementById('peserta-modal-title').innerHTML = iconsax('edit-2', 'w-5 h-5 text-primary-600') + ' Edit Pelaksana: ' + p.nama_peserta;
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

let semuaPegawaiCache = [];

async function muatDaftarPegawai() {
  const res = await fetch(API + '/pegawai');
  const json = await res.json();
  semuaPegawaiCache = json.data || [];
  renderTabelPegawai(semuaPegawaiCache);
}
window.muatDaftarPegawai = muatDaftarPegawai;

/** Daftar pegawai biasanya cuma puluhan baris, jadi pencariannya disaring langsung di
 *  browser dari cache yang sudah dimuat — tidak perlu fetch ulang tiap huruf. */
function filterPegawai() {
  const q = (document.getElementById('pegawai-search').value || '').toLowerCase().trim();
  if (!q) { renderTabelPegawai(semuaPegawaiCache); return; }
  renderTabelPegawai(semuaPegawaiCache.filter(pg =>
    pg.nama.toLowerCase().includes(q) || (pg.nip || '').toLowerCase().includes(q)
  ));
}
window.filterPegawai = filterPegawai;

function renderTabelPegawai(list) {
  const tbody = document.getElementById('pegawai-tbody');
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-slate-500">Tidak ada pegawai yang cocok.</td></tr>';
    return;
  }
  list.forEach(pg => {
    const tr = document.createElement('tr');
    renderBarisPegawai(tr, pg);
    tbody.appendChild(tr);
  });
}

function toggleFormTambahPegawai() {
  document.getElementById('form-tambah-pegawai').classList.toggle('hidden');
}
window.toggleFormTambahPegawai = toggleFormTambahPegawai;

function renderBarisPegawai(tr, pg) {
  const pgJson = JSON.stringify(pg).replace(/'/g, "&#39;");
  tr.innerHTML = `<td>${escapeHtml(pg.nama)}</td><td>${escapeHtml(pg.nip || '-')}</td>` +
    `<td>${pg.aktif == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>'}</td>` +
    `<td class="text-center">
      <div class="flex items-center justify-center gap-1">
        <button type="button" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" onclick='editBarisPegawai(this, ${pgJson})' title="Edit">${iconsax('edit-2', 'w-4 h-4')}</button>
        <button type="button" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" onclick="hapusPegawai(${pg.id}, '${escapeHtml(pg.nama)}')" title="Hapus">${iconsax('trash', 'w-4 h-4')}</button>
        ${pg.aktif == 1
          ? `<button type="button" class="p-1.5 rounded hover:bg-amber-50 dark:hover:bg-amber-900/30 text-slate-500 hover:text-amber-600" onclick="nonaktifkanPegawai(${pg.id})" title="Nonaktifkan">${iconsax('user-remove', 'w-4 h-4')}</button>`
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
  document.getElementById('pegawai-search').value = '';
  document.getElementById('form-tambah-pegawai').classList.add('hidden');
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
    document.getElementById('form-tambah-pegawai').classList.add('hidden');
    showToast(json.message, 'success');
    await muatDaftarPegawai();
    await segarkanOpsiPegawai();
  } else showToast(json.message || 'Gagal', 'error');
}
window.tambahPegawai = tambahPegawai;

async function nonaktifkanPegawai(id) {
  const res = await fetch(API + '/pegawai/nonaktifkan/' + id, { method: 'POST' });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
}
window.nonaktifkanPegawai = nonaktifkanPegawai;

function hapusPegawai(id, nama) {
  tampilkanKonfirmasi(`Hapus pegawai "${nama}"? Tindakan ini tidak bisa dibatalkan.`, async () => {
    const res = await fetch(API + '/pegawai/delete/' + id, { method: 'POST' });
    const json = await res.json();
    // Kalau pegawainya masih punya riwayat perjalanan dinas, backend menolak (bukan error) —
    // pesannya sudah menjelaskan alasannya & menyarankan nonaktifkan sebagai gantinya.
    showToast(json.message, json.success ? 'success' : 'error');
    if (json.success) { await muatDaftarPegawai(); await segarkanOpsiPegawai(); }
  });
}
window.hapusPegawai = hapusPegawai;

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
