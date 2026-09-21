  <div id="panel-perjadin" class="hidden">
    <?php $namaBulanPerjadin = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

    <div class="card">
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
            <input type="text" id="filter-search-perjadin" oninput="jadwalkanMuatDaftarPerjadin()" placeholder="Nama, maksud, no surat tugas..." class="form-control form-control-sm pl-8 w-full">
          </div>
          <input type="hidden" id="filter-status-perjadin" value="">
          <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Perjalanan Dinas">
            <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
              <input type="checkbox" id="pd-pub-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterPub('pd-pub', 'belum')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-amber-500"></span>
              <span>Belum Lunas</span>
            </label>
            <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
              <input type="checkbox" id="pd-pub-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterPub('pd-pub', 'lunas')">
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
            <select id="filter-bulan-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Bulan</option>
              <?php for ($b = 1; $b <= 12; $b++): ?>
              <option value="<?= $b ?>"><?= $namaBulanPerjadin[$b] ?></option>
              <?php endfor; ?>
            </select>
            <select id="filter-tahun-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Tahun</option>
              <?php $tahunSaatIni = (int)date('Y'); $daftarTahunPerjadin = $tahunListPerjadin; if (!in_array($tahunSaatIni, $daftarTahunPerjadin)) $daftarTahunPerjadin[] = $tahunSaatIni; rsort($daftarTahunPerjadin); ?>
              <?php foreach ($daftarTahunPerjadin as $th): ?>
              <option value="<?= $th ?>"><?= $th ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
            <span>Tampilkan</span>
            <select id="filter-per-page-perjadin" onchange="muatDaftarTripPerjadin(1)" class="form-control form-control-sm w-auto">
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
              <th class="whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody id="pd-tbody-perjadin">
            <tr><td colspan="14" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>

      <div id="pd-pagination-wrap-perjadin" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
    </div>
  </div><!-- /panel-perjadin -->