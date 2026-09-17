<?php
$namaBulanDt = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tahunSekarangDt = (int)date('Y');
?>

<!-- Summary Bar Dana Taktis -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-3">
  <div class="kpi p-3 sm:p-4">
    <div class="kpi-label text-[11px]"><?= iconsax('money', 'w-4 h-4') ?> Total Uang Harian</div>
    <div class="kpi-value text-base sm:text-lg text-slate-700 dark:text-slate-200 text-currency" id="dt-sum-uh">Rp 0</div>
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

<div class="card mb-4">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
      Daftar Setoran Dana Taktis <span class="text-slate-400 font-normal">(semua pegawai)</span>
    </h3>
    <span class="text-xs text-slate-500">Total: <span id="dt-total">-</span></span>
  </div>

  <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
    <!-- Baris 1: Search + Status -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[200px]" id="dt-adm-search-wrap">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
          <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
        </span>
        <input type="text" id="dt-filter-search"
          oninput="dtJadwalkanMuat(); dtAdmShowSuggestions()"
          onfocus="dtAdmShowSuggestions()" onblur="setTimeout(dtAdmHideSuggestions, 150)"
          placeholder="Cari nama, MAK, atau no. surat tugas..."
          class="form-control form-control-sm pl-8 w-full" autocomplete="off">
        <ul id="dt-adm-suggestions" class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden text-xs"></ul>
      </div>
      <input type="hidden" id="dt-filter-status" value="">
      <div class="status-filter-group shrink-0" role="group" aria-label="Filter Status Dana Taktis">
        <label class="status-chip chip-belum is-checked" title="Tampilkan Belum Lunas">
          <input type="checkbox" id="dt-adm-chk-belum" value="belum" class="sr-only" checked onchange="toggleStatusFilterAdmDt('belum')">
          <span class="chip-box">
            <svg class="chip-check" viewBox="0 0 16 16" fill="currentColor">
              <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.739a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
            </svg>
          </span>
          <span class="chip-dot bg-amber-500"></span>
          <span>Belum Lunas</span>
        </label>
        <label class="status-chip chip-lunas is-checked" title="Tampilkan Lunas">
          <input type="checkbox" id="dt-adm-chk-lunas" value="lunas" class="sr-only" checked onchange="toggleStatusFilterAdmDt('lunas')">
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
        <select id="dt-filter-bulan" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
          <option value="">Semua Bulan</option>
          <?php for ($b = 1; $b <= 12; $b++): ?>
          <option value="<?= $b ?>"><?= $namaBulanDt[$b] ?></option>
          <?php endfor; ?>
        </select>
        <select id="dt-filter-tahun" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
          <option value="">Semua Tahun</option>
          <?php foreach (range($tahunSekarangDt, $tahunSekarangDt - 5) as $th): ?>
          <option value="<?= $th ?>"><?= $th ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
        <span>Tampilkan</span>
        <select id="dt-filter-perpage" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
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
      <tbody id="dt-tbody">
        <tr><td colspan="9" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>
  <div id="dt-pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
</div>

<!-- Modal Detail + Aksi (klik baris) -->
<div id="modal-dt-detail" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-dt-detail')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-md">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('moneys', 'w-5 h-5 text-emerald-600') ?> Detail Dana Taktis</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" id="dt-detail-subtitle"></p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-dt-detail')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm" id="dt-detail-list"></dl>
        <div id="dt-detail-form-lunas" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 space-y-3 hidden">
          <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Catat Setoran</p>
          <div class="segment w-full">
            <button type="button" data-status="sebagian" class="segment-btn dt-lunas-status-btn flex-1" onclick="dtPilihStatusLunas('sebagian')">Bayar Sebagian</button>
            <button type="button" data-status="lunas" class="segment-btn dt-lunas-status-btn active flex-1" onclick="dtPilihStatusLunas('lunas')">Lunas Penuh</button>
          </div>
          <div id="dt-lunas-jumlah-wrap" class="hidden">
            <label class="form-label">Jumlah Disetor</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
              <input type="text" inputmode="numeric" id="dt-lunas-jumlah-disetor" class="form-control input-rupiah pl-9" placeholder="0">
            </div>
          </div>
          <div>
            <label class="form-label">Tanggal Setoran</label>
            <input type="date" id="dt-lunas-tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div id="dt-detail-btn-batal-wrap">
          <button type="button" class="btn btn-danger btn-sm" onclick="dtBatalkanLunasFromDetail()">
            <?= iconsax('trash', 'w-4 h-4') ?> Hapus Setoran
          </button>
        </div>
        <div class="flex items-center gap-2 ml-auto">
          <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('modal-dt-detail')">Kembali</button>
          <button type="button" class="btn btn-outline btn-sm hidden" id="dt-detail-btn-edit" onclick="dtToggleEditForm()">
            <?= iconsax('edit-2', 'w-4 h-4') ?> Edit
          </button>
          <button type="button" class="btn btn-success btn-sm hidden" id="dt-detail-btn-simpan" onclick="dtKonfirmasiLunas()">
            <?= iconsax('check', 'w-4 h-4') ?> Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hidden fields dipakai oleh dtKonfirmasiLunas -->
<input type="hidden" id="dt-lunas-peserta-id">

<script>
(function() {
const BASE = '<?= base_url() ?>';
const API_DT = BASE + 'admin/perjalanan-dinas';
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
const fmtTgl = (s) => s ? new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';

/* Autocomplete */
let dtAdmSuggestionCache = [];
function dtAdmShowSuggestions() {
  const q = document.getElementById('dt-filter-search').value.trim().toLowerCase();
  const ul = document.getElementById('dt-adm-suggestions');
  if (!q || q.length < 2 || !dtAdmSuggestionCache.length) { ul.classList.add('hidden'); return; }
  const matches = dtAdmSuggestionCache.filter(s => s.toLowerCase().includes(q)).slice(0, 8);
  if (!matches.length) { ul.classList.add('hidden'); return; }
  ul.innerHTML = matches.map(s =>
    `<li class="px-3 py-2 cursor-pointer hover:bg-primary-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200" onmousedown="dtAdmPickSuggestion('${esc(s).replace(/'/g,"&#39;")}')"> ${esc(s)}</li>`
  ).join('');
  ul.classList.remove('hidden');
}
function dtAdmHideSuggestions() { document.getElementById('dt-adm-suggestions').classList.add('hidden'); }
function dtAdmPickSuggestion(val) {
  document.getElementById('dt-filter-search').value = val;
  dtAdmHideSuggestions();
  muatDaftarDanaTaktis(1);
}
window.dtAdmShowSuggestions = dtAdmShowSuggestions;
window.dtAdmHideSuggestions = dtAdmHideSuggestions;
window.dtAdmPickSuggestion   = dtAdmPickSuggestion;

/* Summary */
function dtAdmUpdateSummary(rows) {
  let sumUH = 0, sumSpj = 0, sumTaktis = 0, sumBelum = 0;
  rows.forEach(r => {
    sumUH     += parseFloat(r.uang_harian)  || 0;
    sumSpj    += parseFloat(r.total_spj)    || 0;
    sumTaktis += parseFloat(r.dana_taktis)  || 0;
    if (r.status_lunas !== 'lunas') {
      sumBelum += (parseFloat(r.dana_taktis) || 0) - (parseFloat(r.jumlah_disetor) || 0);
    }
  });
  document.getElementById('dt-sum-uh').textContent     = rupiah(sumUH);
  document.getElementById('dt-sum-spj').textContent    = rupiah(sumSpj);
  document.getElementById('dt-sum-taktis').textContent = rupiah(sumTaktis);
  document.getElementById('dt-sum-belum').textContent  = rupiah(sumBelum);
}

/* Toggle Checkbox Status Dana Taktis (Admin) */
function toggleStatusFilterAdmDt(changedType) {
  const chkBelum = document.getElementById('dt-adm-chk-belum');
  const chkLunas = document.getElementById('dt-adm-chk-lunas');
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

  const input = document.getElementById('dt-filter-status');
  if (input) input.value = statusVal;
  muatDaftarDanaTaktis(1);
}
window.toggleStatusFilterAdmDt = toggleStatusFilterAdmDt;

/* Daftar */
let dtFilterDebounce = null;
function dtJadwalkanMuat() {
  clearTimeout(dtFilterDebounce);
  dtFilterDebounce = setTimeout(() => muatDaftarDanaTaktis(1), 350);
}
window.dtJadwalkanMuat = dtJadwalkanMuat;

let dtRequestSeq = 0;
let dtHalamanSaatIni = null;

async function muatDaftarDanaTaktis(page) {
  if (page !== undefined && page !== null) {
    dtHalamanSaatIni = page;
  }
  const params = new URLSearchParams();
  const search  = document.getElementById('dt-filter-search').value;
  const status  = document.getElementById('dt-filter-status').value;
  const bulan   = document.getElementById('dt-filter-bulan').value;
  const tahun   = document.getElementById('dt-filter-tahun').value;
  const perPage = document.getElementById('dt-filter-perpage').value;
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (bulan)  params.set('bulan', bulan);
  if (tahun)  params.set('tahun', tahun);
  params.set('per_page', perPage);
  if (dtHalamanSaatIni) params.set('page', dtHalamanSaatIni);

  const tbody = document.getElementById('dt-tbody');
  const seq = ++dtRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="9" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res  = await fetch(API_DT + '/dana-taktis/list?' + params.toString());
    const json = await res.json();
    if (seq !== dtRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    const namaSet = new Set(dtAdmSuggestionCache);
    json.data.forEach(r => {
      if (r.nama_peserta)   namaSet.add(r.nama_peserta);
      if (r.no_surat_tugas) namaSet.add(r.no_surat_tugas);
      if (r.kode_mak)       namaSet.add(r.kode_mak);
    });
    dtAdmSuggestionCache = [...namaSet];
    dtAdmUpdateSummary(json.data);
    dtHalamanSaatIni = json.page;
    renderDanaTaktisTable(json.data, json.page, json.per_page);
    renderPaginasiHalaman(document.getElementById('dt-pagination-wrap'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'baris', onPageChange: muatDaftarDanaTaktis,
    });
    document.getElementById('dt-total').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== dtRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}
window.muatDaftarDanaTaktis = muatDaftarDanaTaktis;

function renderDanaTaktisTable(rows, page, perPage) {
  const tbody = document.getElementById('dt-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  const curPage = page || dtHalamanSaatIni || 1;
  const curPerPage = perPage || 10;

  // Berapa baris berturut-turut punya nama_peserta yang sama persis -- dipakai
  // supaya kolom Nama Pegawai tampil sekali saja (rowspan) untuk satu orang,
  // sementara No, No. PD, dan kolom lain tetap satu per baris.
  const rowspanNama = new Array(rows.length).fill(0);
  for (let i = 0; i < rows.length; i++) {
    if (i > 0 && rows[i].nama_peserta === rows[i - 1].nama_peserta) continue;
    let span = 1;
    while (i + span < rows.length && rows[i + span].nama_peserta === rows[i].nama_peserta) span++;
    rowspanNama[i] = span;
  }

  tbody.innerHTML = rows.map((r, idx) => {
    const tgl = fmtTgl(r.tanggal_surat_tugas);
    const isLunas = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
    const isSebagian = r.status_lunas === 'sebagian';
    const tglLunasHtml = r.tanggal_lunas
      ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${fmtTgl(r.tanggal_lunas)}</div>`
      : '';
    const statusBadge = isLunas
      ? `<span class="badge badge-success">Lunas</span>${tglLunasHtml}`
      : isSebagian
        ? `<span class="badge badge-info" title="Disetor ${rupiah(r.jumlah_disetor)}">Sebagian</span>${tglLunasHtml}`
        : '<span class="badge badge-warning">Belum Lunas</span>';
    const rowData = encodeURIComponent(JSON.stringify(r));
    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    const rowNum = (curPage - 1) * curPerPage + idx + 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80 shadow-xs">${esc(r.no_pd)}</span>`
          : `<span class="text-xs font-semibold text-slate-700 dark:text-slate-200">${esc(r.no_pd)}</span>`)
      : '<span class="text-xs text-slate-400">-</span>';

    const namaCellHtml = rowspanNama[idx] > 0
      ? `<td class="font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap align-top" rowspan="${rowspanNama[idx]}">${esc(r.nama_peserta)}</td>`
      : '';

    return `<tr class="cursor-pointer ${rowBgClass} transition-colors" onclick="dtBukaDetail('${rowData.replace(/'/g,"&#39;")}')">
      <td class="text-center text-xs text-slate-500">${rowNum}</td>
      <td class="text-center font-medium">${nomorPdHtml}</td>
      ${namaCellHtml}
      <td class="min-w-[180px] max-w-[300px] break-words whitespace-normal" title="${esc(r.maksud)}">${esc(r.maksud)}</td>
      <td class="text-xs">
        <div class="font-medium text-slate-700 dark:text-slate-200">${esc(r.no_surat_tugas || '-')}</div>
        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${esc(r.kode_mak || '-')}</div>
        <div class="text-[11px] text-slate-400">${tgl}</div>
      </td>
      <td class="text-right text-currency">${rupiah(r.uang_harian)}</td>
      <td class="text-right text-currency">${rupiah(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600">${rupiah(r.dana_taktis)}</td>
      <td>${statusBadge}</td>
    </tr>`;
  }).join('');
}

/* Modal Detail */
let dtDetailRow = null;
let dtLunasStatusDipilih = 'lunas';

function dtBukaDetail(rowEnc) {
  const r = JSON.parse(decodeURIComponent(rowEnc));
  dtDetailRow = r;
  const isLunas    = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
  const isSebagian = r.status_lunas === 'sebagian';
  document.getElementById('dt-detail-subtitle').textContent = (r.nama_peserta || '') + ' · ' + (r.maksud || '');
  const statusLabel = isLunas ? 'Lunas' : isSebagian ? 'Sebagian' : 'Belum Lunas';
  const items = [];
  if (r.no_pd) {
    items.push(['No. PD (Spreadsheet)', `<span class="font-semibold ${r.no_pd.includes('UP') ? 'text-amber-600 dark:text-amber-400 font-bold' : ''}">${esc(r.no_pd)}</span>`]);
  }
  items.push(
    ['Nama Pelaksana Perjalanan Dinas', esc(r.nama_peserta)],
    ['Maksud Perjalanan Dinas', esc(r.maksud)],
    ['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${esc(r.kode_mak || '-')}</span>`],
    ['No. Surat Tugas', esc(r.no_surat_tugas || '-')],
    ['No. SPM', esc(r.no_spm || '-')],
    ['Tgl. Surat Tugas', fmtTgl(r.tanggal_surat_tugas)],
    ['Uang Harian', rupiah(r.uang_harian)],
    ['Total SPJ Yang Dibayarkan', rupiah(r.total_spj)],
    ['Taktis (10%)', `<span class="font-semibold text-emerald-600">${rupiah(r.dana_taktis)}</span>`],
    ['Status Setoran', `<span class="badge ${isLunas ? 'badge-success' : isSebagian ? 'badge-info' : 'badge-warning'}">${statusLabel}</span>`],
  );
  if (isSebagian) {
    items.push(['Sudah Disetor', rupiah(r.jumlah_disetor)]);
    items.push(['Sisa Kurang', rupiah((parseFloat(r.dana_taktis)||0)-(parseFloat(r.jumlah_disetor)||0))]);
  }
  if (r.tanggal_lunas) items.push(['Tanggal Lunas', fmtTgl(r.tanggal_lunas)]);
  document.getElementById('dt-detail-list').innerHTML = items.map(([k, v]) =>
    `<div><dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt><dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd></div>`
  ).join('');

  const canEdit   = parseFloat(r.dana_taktis) > 0;
  const showBatal = r.status_lunas !== 'belum' && parseFloat(r.dana_taktis) > 0;
  document.getElementById('dt-detail-btn-batal-wrap').classList.toggle('hidden', !showBatal);
  document.getElementById('dt-detail-btn-edit').classList.toggle('hidden', !canEdit);
  document.getElementById('dt-detail-form-lunas').classList.add('hidden');
  document.getElementById('dt-detail-btn-simpan').classList.add('hidden');

  document.getElementById('dt-lunas-peserta-id').value = r.id;
  document.getElementById('dt-lunas-tanggal').value = r.tanggal_lunas ? r.tanggal_lunas.slice(0, 10) : new Date().toISOString().slice(0, 10);
  document.getElementById('dt-lunas-jumlah-disetor').value = r.jumlah_disetor ? new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(r.jumlah_disetor))) : '';
  dtPilihStatusLunas(r.status_lunas === 'sebagian' ? 'sebagian' : 'lunas');
  openModal('modal-dt-detail');
}
window.dtBukaDetail = dtBukaDetail;

function dtToggleEditForm() {
  const form = document.getElementById('dt-detail-form-lunas');
  const isHidden = form.classList.contains('hidden');
  form.classList.toggle('hidden', !isHidden);
  document.getElementById('dt-detail-btn-simpan').classList.toggle('hidden', !isHidden);
  document.getElementById('dt-detail-btn-edit').classList.toggle('hidden', isHidden);
}
window.dtToggleEditForm = dtToggleEditForm;

function dtPilihStatusLunas(status) {
  dtLunasStatusDipilih = status;
  document.querySelectorAll('.dt-lunas-status-btn').forEach(b => b.classList.toggle('active', b.dataset.status === status));
  document.getElementById('dt-lunas-jumlah-wrap').classList.toggle('hidden', status !== 'sebagian');
}
window.dtPilihStatusLunas = dtPilihStatusLunas;

function dtBukaModalLunas(pesertaId, danaTaktis) {
  document.getElementById('dt-lunas-peserta-id').value = pesertaId;
  document.getElementById('dt-lunas-tanggal').value = new Date().toISOString().slice(0, 10);
  document.getElementById('dt-lunas-jumlah-disetor').value = '';
  dtPilihStatusLunas('lunas');
  openModal('modal-dt-detail');
}
window.dtBukaModalLunas = dtBukaModalLunas;

async function dtKonfirmasiLunas() {
  const id = document.getElementById('dt-lunas-peserta-id').value;
  const fd = new FormData();
  fd.append('aksi', dtLunasStatusDipilih);
  fd.append('tanggal_lunas', document.getElementById('dt-lunas-tanggal').value);
  if (dtLunasStatusDipilih === 'sebagian') {
    fd.append('jumlah_disetor', unformatRibuan(document.getElementById('dt-lunas-jumlah-disetor').value));
  }
  const res  = await fetch(API_DT + '/lunas/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-dt-detail'); muatDaftarDanaTaktis(); }
  else showToast(json.message || 'Gagal', 'error');
}
window.dtKonfirmasiLunas = dtKonfirmasiLunas;

function dtBatalkanLunasFromDetail() {
  const id = dtDetailRow?.id;
  if (!id) return;
  tampilkanKonfirmasi('Batalkan status setoran? Pemasukan otomatis yang sudah tercatat akan ikut dihapus.', async () => {
    const fd = new FormData(); fd.append('aksi', 'batal');
    const res = await fetch(API_DT + '/lunas/' + id, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); closeModal('modal-dt-detail'); muatDaftarDanaTaktis(); }
    else showToast(json.message || 'Gagal', 'error');
  });
}
window.dtBatalkanLunasFromDetail = dtBatalkanLunasFromDetail;

function dtBatalkanLunas(pesertaId) {
  tampilkanKonfirmasi('Batalkan status setoran?', async () => {
    const fd = new FormData(); fd.append('aksi', 'batal');
    const res = await fetch(API_DT + '/lunas/' + pesertaId, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); muatDaftarDanaTaktis(); }
    else showToast(json.message || 'Gagal', 'error');
  });
}
window.dtBatalkanLunas = dtBatalkanLunas;

})();
</script>
