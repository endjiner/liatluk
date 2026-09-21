  <div id="panel-dana-taktis" class="hidden">
    <?php $namaBulanDt = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

    <!-- Summary bar Dana Taktis (dihitung dari data yang sedang tampil) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-3">
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('money', 'w-4 h-4') ?> Total Uang Harian</div>
        <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency" id="dt-sum-uang-harian">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('receipt-2', 'w-4 h-4') ?> Total SPJ</div>
        <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency" id="dt-sum-spj">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4">
        <div class="kpi-label text-[11px]"><?= iconsax('moneys', 'w-4 h-4 text-emerald-600') ?> Dana Taktis</div>
        <div class="kpi-value text-base sm:text-lg text-emerald-700 dark:text-emerald-400 text-currency" id="dt-sum-taktis">Rp 0</div>
      </div>
      <div class="kpi p-3 sm:p-4 bg-amber-50/70 dark:bg-amber-900/20">
        <div class="kpi-label text-[11px]"><?= iconsax('warning-2', 'w-4 h-4 text-amber-600') ?> Belum Dibayar</div>
        <div class="kpi-value text-base sm:text-lg text-amber-700 dark:text-amber-400 text-currency" id="dt-sum-belum">Rp 0</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar Dana Taktis</h3>
        <span class="text-xs text-slate-500">Total: <span id="dt-total-pub">-</span></span>
      </div>
      <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
        <!-- Baris 1: Search dengan autocomplete -->
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative flex-1 min-w-[200px]" id="dt-search-wrap">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
            </span>
            <input type="text" id="dt-filter-search-pub" aria-label="Cari dana taktis" oninput="dtPubJadwalkanMuat(); dtShowSuggestions()"
              onfocus="dtShowSuggestions()" onblur="setTimeout(dtHideSuggestions, 150)"
              placeholder="Cari nama, MAK, atau no. surat tugas..." class="form-control form-control-sm pl-8 w-full" autocomplete="off">
            <ul id="dt-suggestions" class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden text-xs"></ul>
          </div>
          <input type="hidden" id="dt-filter-status-pub" value="">
          <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Dana Taktis">
            <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
              <input type="checkbox" id="dt-pub-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterPub('dt-pub', 'belum')">
              <span class="chip-box">
                <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                </svg>
              </span>
              <span class="chip-dot bg-amber-500"></span>
              <span>Belum Lunas</span>
            </label>
            <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
              <input type="checkbox" id="dt-pub-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterPub('dt-pub', 'lunas')">
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
            <select id="dt-filter-bulan-pub" aria-label="Filter bulan" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Bulan</option>
              <?php for ($b = 1; $b <= 12; $b++): ?>
              <option value="<?= $b ?>"><?= $namaBulanDt[$b] ?></option>
              <?php endfor; ?>
            </select>
            <select id="dt-filter-tahun-pub" aria-label="Filter tahun" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
              <option value="">Semua Tahun</option>
              <?php $tahunSaatIniDtPub = (int)date('Y'); $daftarTahunDtPub = $tahunListPerjadin; if (!in_array($tahunSaatIniDtPub, $daftarTahunDtPub)) $daftarTahunDtPub[] = $tahunSaatIniDtPub; rsort($daftarTahunDtPub); ?>
              <?php foreach ($daftarTahunDtPub as $th): ?>
              <option value="<?= $th ?>"><?= $th ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
            <span>Tampilkan</span>
            <select id="dt-filter-perpage-pub" aria-label="Jumlah baris per halaman" onchange="muatDaftarDanaTaktisPub(1)" class="form-control form-control-sm w-auto">
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
              <th class="w-12 text-center">No</th>
              <th class="w-20 text-center">No. PD</th>
              <th>Nama Pegawai</th>
              <th>Perjalanan Dinas</th>
              <th>No. ST / MAK / Tgl</th>
              <th class="text-right">Uang Harian</th>
              <th class="text-right">Total SPJ</th>
              <th class="text-right">Dana Taktis</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="dt-tbody-pub">
            <tr><td colspan="9" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>

      <div id="dt-pagination-wrap-pub" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
    </div>
  </div><!-- /panel-dana-taktis -->
