<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<?php
$tahunSekarang = (int)date('Y');
$tahunOpsi = range($tahunSekarang, $tahunSekarang - 5);
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>

<!-- Hero -->
<section class="bg-gradient-to-r from-primary-900 via-primary-800 to-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 lg:px-6 py-8 lg:py-10">
    <div class="max-w-3xl">
      <h1 class="text-2xl lg:text-3xl font-bold leading-tight">Pengelolaan Keuangan Internal</h1>
      <p class="text-primary-100 mt-2 text-sm lg:text-base">
        Publikasi arus kas dan rencana anggaran Balai Besar POM di Pangkal Pinang secara terbuka.
      </p>
    </div>
  </div>
</section>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-6 space-y-6">

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <!-- Saldo -->
    <div class="kpi">
      <div class="kpi-label"><i data-lucide="wallet" class="w-4 h-4"></i> Saldo Kas</div>
      <div class="kpi-value text-primary-700 dark:text-primary-300 text-currency">
        Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400">Selisih total pemasukan dan pengeluaran</div>
    </div>

    <!-- Pemasukan with period picker -->
    <div class="kpi" data-kpi="pemasukan">
      <div class="kpi-label"><i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i> Total Pemasukan</div>
      <div class="kpi-value text-emerald-700 dark:text-emerald-400 text-currency" id="kpi-pemasukan-value">
        Rp <?= number_format($totalPemasukan, 0, ',', '.') ?>
      </div>
      <?= view('public/_partials/kpi_period_picker', ['id' => 'pemasukan']) ?>
    </div>

    <!-- Pengeluaran with period picker -->
    <div class="kpi" data-kpi="pengeluaran">
      <div class="kpi-label"><i data-lucide="trending-down" class="w-4 h-4 text-red-600"></i> Total Pengeluaran</div>
      <div class="kpi-value text-red-700 dark:text-red-400 text-currency" id="kpi-pengeluaran-value">
        Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?>
      </div>
      <?= view('public/_partials/kpi_period_picker', ['id' => 'pengeluaran']) ?>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-2">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tren Keuangan Bulanan</h3>
        <select id="tren-tahun" class="form-control form-control-sm w-24">
          <?php foreach ($tahunOpsi as $t): ?>
          <option value="<?= $t ?>" <?= $t === $tahunSekarang ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="card-body">
        <canvas id="chart-tren" class="chart-canvas"></canvas>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kategori Pengeluaran</h3>
      </div>
      <div class="card-body">
        <canvas id="chart-kategori" class="chart-canvas"></canvas>
      </div>
    </div>
  </div>

  <!-- Rencana ringkas -->
  <?php if (!empty($rencanaAktif)): ?>
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <i data-lucide="calendar-clock" class="w-4 h-4 text-primary-600"></i> Rencana Keuangan Aktif
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr><th>Tanggal Rencana</th><th>Kategori</th><th>Tipe</th><th class="text-right">Nominal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rencanaAktif as $r): ?>
          <tr>
            <td class="whitespace-nowrap"><?= date('d M Y', strtotime($r['tanggal_rencana'])) ?></td>
            <td><?= esc($r['kategori']) ?></td>
            <td>
              <?php if ($r['tipe'] === 'pemasukan'): ?>
                <span class="badge badge-info">Pemasukan</span>
              <?php else: ?>
                <span class="badge badge-warning">Pengeluaran</span>
              <?php endif; ?>
            </td>
            <td class="text-right font-medium text-currency">Rp <?= number_format($r['jumlah_rencana'], 0, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Daftar Transaksi -->
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <i data-lucide="list" class="w-4 h-4"></i> Daftar Transaksi
      </h3>
      <span class="text-xs text-slate-500">Total: <span id="txn-total">-</span></span>
    </div>

    <!-- Filter compact (single row) -->
    <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
      <div class="flex flex-wrap items-center gap-2">
        <!-- Tipe checkbox -->
        <div class="flex items-center gap-3 pr-3 border-r border-slate-200 dark:border-slate-700">
          <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
            <input type="checkbox" id="filter-pemasukan" checked class="form-checkbox text-emerald-600">
            <span class="text-slate-700 dark:text-slate-300">Pemasukan</span>
          </label>
          <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
            <input type="checkbox" id="filter-pengeluaran" checked class="form-checkbox text-red-600">
            <span class="text-slate-700 dark:text-slate-300">Pengeluaran</span>
          </label>
        </div>

        <!-- Search -->
        <div class="relative flex-1 min-w-[160px] max-w-xs">
          <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
          </span>
          <input type="text" id="filter-search" placeholder="Cari kategori/keterangan..."
                 class="form-control form-control-sm pl-8">
        </div>

        <!-- Bulan -->
        <select id="filter-bulan" class="form-control form-control-sm w-auto">
          <option value="">Semua Bulan</option>
          <?php foreach ($namaBulan as $n => $nm): ?>
          <option value="<?= $n ?>"><?= $nm ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Tahun -->
        <select id="filter-tahun" class="form-control form-control-sm w-auto">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunOpsi as $t): ?>
          <option value="<?= $t ?>"><?= $t ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Per page -->
        <div class="flex items-center gap-1.5 pl-3 border-l border-slate-200 dark:border-slate-700">
          <span class="text-xs text-slate-600 dark:text-slate-400">Tampilkan</span>
          <select id="filter-perpage" class="form-control form-control-sm w-auto">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="text-xs text-slate-600 dark:text-slate-400">baris</span>
        </div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th class="w-12 text-center">No</th>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Tipe</th>
            <th class="text-right">Nominal</th>
            <th class="w-16 text-center">Detail</th>
          </tr>
        </thead>
        <tbody id="txn-tbody">
          <tr><td colspan="6" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div id="pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
  </div>

</div>

<!-- ═══════ MODAL: DETAIL TRANSAKSI ═══════ -->
<div id="modal-detail-txn" class="hidden">
  <div class="modal-backdrop" onclick="closeDetailModal()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><i data-lucide="file-text" class="w-5 h-5 text-primary-600"></i> Detail Transaksi</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="detail-subtitle-pub"></p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeDetailModal()"><i data-lucide="x"></i></button>
      </div>
      <div class="modal-body">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm" id="detail-list-pub"></dl>
        <div class="mt-4" id="detail-bukti-pub"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeDetailModal()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE_URL = '<?= base_url() ?>';
const KPI_ENDPOINT_PUB = '<?= base_url('kpi-periode') ?>';

/* ── Chart ── */
let chartTren, chartKat;
function chartColors() {
  const dark = document.documentElement.classList.contains('dark');
  return { text: dark ? '#cbd5e1' : '#475569', grid: dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.2)' };
}
function buildTren(pemasukan, pengeluaran) {
  const colors = chartColors();
  if (chartTren) chartTren.destroy();
  chartTren = new Chart(document.getElementById('chart-tren'), {
    type: 'bar',
    data: {
      labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
      datasets: [
        { label: 'Pemasukan', data: pemasukan, backgroundColor: '#059669', borderRadius: 6 },
        { label: 'Pengeluaran', data: pengeluaran, backgroundColor: '#dc2626', borderRadius: 6 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 11 } } } },
      scales: {
        y: { ticks: { color: colors.text, callback: v => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v) }, grid: { color: colors.grid } },
        x: { ticks: { color: colors.text }, grid: { display: false } }
      }
    }
  });
}
function buildKat() {
  const colors = chartColors();
  const data = <?= $pieData ?>;
  if (chartKat) chartKat.destroy();
  chartKat = new Chart(document.getElementById('chart-kategori'), {
    type: 'doughnut',
    data: {
      labels: data.map(d => d.kategori),
      datasets: [{ data: data.map(d => d.total), backgroundColor: ['#1e5fbe','#059669','#dc2626','#d97706','#7c3aed','#0891b2','#65a30d','#c026d3'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 10 }, padding: 8 } } } }
  });
}

let lastTrenPemasukan = [], lastTrenPengeluaran = [];

async function loadTren(tahun) {
  const res = await fetch(BASE_URL + 'chart-tren?tahun=' + tahun);
  const data = await res.json();
  lastTrenPemasukan = data.pemasukan || [];
  lastTrenPengeluaran = data.pengeluaran || [];
  buildTren(lastTrenPemasukan, lastTrenPengeluaran);
}

document.getElementById('tren-tahun').addEventListener('change', e => loadTren(e.target.value));

// Ganti tema cukup gambar ulang chart dari data yang sudah ada di memori, tidak perlu
// fetch ulang ke server, datanya tidak berubah, yang berubah cuma warnanya.
function updateChartColors() {
  buildKat();
  buildTren(lastTrenPemasukan, lastTrenPengeluaran);
}
window.updateChartColors = updateChartColors;

/* ── KPI Period picker (public) ── */
document.querySelectorAll('[data-kpi]').forEach(card => {
  const kpiId = card.dataset.kpi;
  const valueEl = document.getElementById('kpi-' + kpiId + '-value');
  const tabs = card.querySelectorAll('.kpi-tab');
  const yearSel = card.querySelector('.kpi-year');
  const monthSel = card.querySelector('.kpi-month');
  tabs.forEach(tab => {
    tab.addEventListener('click', async () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const scope = tab.dataset.scope;
      if (yearSel) yearSel.parentElement.classList.toggle('hidden', scope === 'semua');
      if (monthSel) monthSel.parentElement.classList.toggle('hidden', scope !== 'bulan');
      await refreshKpi(kpiId, valueEl, scope, yearSel?.value, monthSel?.value);
    });
  });
  if (yearSel) yearSel.addEventListener('change', () => reload(card, kpiId, valueEl));
  if (monthSel) monthSel.addEventListener('change', () => reload(card, kpiId, valueEl));
});
function reload(card, kpiId, valueEl) {
  const scope = card.querySelector('.kpi-tab.active')?.dataset.scope || 'semua';
  refreshKpi(kpiId, valueEl, scope, card.querySelector('.kpi-year')?.value, card.querySelector('.kpi-month')?.value);
}
async function refreshKpi(kpiId, valueEl, scope, tahun, bulan) {
  valueEl.style.opacity = '0.5';
  try {
    const url = KPI_ENDPOINT_PUB + '?scope=' + scope + '&tahun=' + (tahun || new Date().getFullYear()) + '&bulan=' + (bulan || (new Date().getMonth()+1));
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
      const total = kpiId === 'pemasukan' ? data.total_pemasukan : data.total_pengeluaran;
      valueEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }
  } finally { valueEl.style.opacity = '1'; }
}

/* ── Transaksi (dengan pagination) ──
   Nomor baris selalu urut dari transaksi paling awal (dihitung backend). currentPage=null
   berarti "biarkan backend pilih default" — yaitu halaman TERAKHIR (transaksi terbaru),
   supaya begitu dibuka tetap yang paling baru yang terlihat duluan. */
let currentPage = null;

function refreshTxn() {
  const showP = document.getElementById('filter-pemasukan').checked;
  const showE = document.getElementById('filter-pengeluaran').checked;
  const perPage = document.getElementById('filter-perpage').value;
  const search = document.getElementById('filter-search').value;
  const bulan = document.getElementById('filter-bulan').value;
  const tahun = document.getElementById('filter-tahun').value;

  const params = new URLSearchParams({
    show_pemasukan: showP ? '1' : '0',
    show_pengeluaran: showE ? '1' : '0',
    per_page: perPage,
    search, bulan, tahun,
  });
  if (currentPage) params.set('page', currentPage);

  document.getElementById('txn-tbody').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500">Memuat...</td></tr>';

  fetch(BASE_URL + 'transaksi/ajax?' + params.toString())
    .then(r => r.json())
    .then(data => {
      currentPage = data.page || 1;
      renderTxn(data.data || []);
      document.getElementById('txn-total').textContent = new Intl.NumberFormat('id-ID').format(data.total || 0);
      renderPagination(data.total || 0, parseInt(perPage), currentPage);
    })
    .catch(() => {
      document.getElementById('txn-tbody').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
    });
}

function renderTxn(rows) {
  const tbody = document.getElementById('txn-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500">Tidak ada data yang cocok.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(r => {
    const isP = r.tipe === 'pemasukan';
    const nominal = isP ? (parseFloat(r.jumlah_diterima) || parseFloat(r.jumlah)) : parseFloat(r.jumlah);
    const dateFormatted = new Date(r.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const badge = isP
      ? '<span class="badge badge-success"><i data-lucide="arrow-up-right" class="w-3 h-3"></i>Pemasukan</span>'
      : '<span class="badge badge-danger"><i data-lucide="arrow-down-right" class="w-3 h-3"></i>Pengeluaran</span>';
    return `<tr>
      <td class="text-center text-slate-500">${r.nomor ?? '-'}</td>
      <td class="whitespace-nowrap">${dateFormatted}</td>
      <td class="truncate max-w-[200px]">${escapeHtml(r.kategori || '-')}</td>
      <td>${badge}</td>
      <td class="text-right font-medium text-currency ${isP ? 'text-emerald-600' : 'text-red-600'}">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(nominal))}</td>
      <td class="text-center">
        <button onclick='showDetailPub(${JSON.stringify(r).replace(/'/g, "&#39;")})' class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Detail">
          <i data-lucide="eye" class="w-4 h-4"></i>
        </button>
      </td>
    </tr>`;
  }).join('');
  lucide.createIcons({ props: { search: tbody } });
}

function renderPagination(total, perPage, page) {
  const wrap = document.getElementById('pagination-wrap');
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (total === 0) { wrap.innerHTML = ''; return; }

  const from = (page - 1) * perPage + 1;
  const to = Math.min(total, page * perPage);

  let btns = '';
  const pageBtn = (p, label, active = false, disabled = false) => {
    if (disabled) return `<button disabled class="px-3 py-1.5 text-xs rounded-md text-slate-400 cursor-not-allowed">${label}</button>`;
    if (active)   return `<button class="px-3 py-1.5 text-xs rounded-md bg-primary-600 text-white font-medium">${label}</button>`;
    return `<button onclick="gotoPage(${p})" class="px-3 py-1.5 text-xs rounded-md text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">${label}</button>`;
  };

  btns += pageBtn(page - 1, '‹ Sebelumnya', false, page <= 1);

  // Show page numbers with smart truncation
  const pages = getPageNumbers(page, totalPages);
  pages.forEach(p => {
    if (p === '...') {
      btns += '<span class="px-2 text-xs text-slate-400">…</span>';
    } else {
      btns += pageBtn(p, p, p === page);
    }
  });

  btns += pageBtn(page + 1, 'Selanjutnya ›', false, page >= totalPages);

  wrap.innerHTML = `
    <div class="text-xs text-slate-600 dark:text-slate-400">Menampilkan ${from}–${to} dari ${new Intl.NumberFormat('id-ID').format(total)} baris</div>
    <div class="flex items-center gap-1">${btns}</div>
  `;
}

function getPageNumbers(current, total) {
  if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
}

function gotoPage(p) {
  currentPage = p;
  refreshTxn();
  document.getElementById('txn-tbody').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
window.gotoPage = gotoPage;

/* ── Debounced filter changes ── */
let filterTimer;
function scheduleFilterReset() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => { currentPage = null; refreshTxn(); }, 300);
}
['filter-pemasukan','filter-pengeluaran','filter-bulan','filter-tahun','filter-perpage'].forEach(id => {
  document.getElementById(id).addEventListener('change', scheduleFilterReset);
});
document.getElementById('filter-search').addEventListener('input', scheduleFilterReset);

/* ── Detail modal ── */
function fmtRp(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(v) || 0)); }
function fmtDate(s) { if (!s) return '-'; return new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); }
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

function statusBadgePub(s) {
  const map = { diterima: ['badge-success','Diterima'], sebagian: ['badge-warning','Sebagian'], belum_diterima: ['badge-danger','Belum Diterima'] };
  const [cls, label] = map[s] || ['badge-muted', s || '-'];
  return `<span class="badge ${cls}">${label}</span>`;
}
function buktiHTML(url, filename) {
  if (!filename) return '<div class="text-sm text-slate-500">Tidak ada bukti terlampir.</div>';
  const ext = (filename.split('.').pop() || '').toLowerCase();
  if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
    return `<a href="${url}" target="_blank"><img src="${url}" alt="Bukti" class="max-h-64 rounded-lg border border-slate-200 shadow-soft"><div class="text-xs text-primary-600 hover:underline mt-1">Buka di tab baru</div></a>`;
  }
  if (ext === 'pdf') {
    return `<a href="${url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border border-slate-200 hover:border-primary-500 hover:bg-primary-50/50 transition">
              <i data-lucide="file-text" class="w-8 h-8 text-red-600"></i>
              <div><div class="font-medium text-sm">${escapeHtml(filename)}</div><div class="text-xs text-primary-600">Klik untuk buka PDF</div></div>
            </a>`;
  }
  return `<a href="${url}" target="_blank" class="text-primary-600 hover:underline text-sm">${escapeHtml(filename)}</a>`;
}

function showDetailPub(row) {
  const isP = row.tipe === 'pemasukan';
  document.getElementById('detail-subtitle-pub').textContent = isP ? 'Pemasukan' : 'Pengeluaran';
  const items = [
    ['Tanggal', fmtDate(row.tanggal)],
    ['Kategori', row.kategori ? escapeHtml(row.kategori) : '-'],
    ['Nominal', `<span class="font-semibold ${isP ? 'text-emerald-600' : 'text-red-600'}">${fmtRp(row.jumlah)}</span>`],
  ];
  if (isP) {
    items.push(['Jumlah Diterima', fmtRp(row.jumlah_diterima)]);
    items.push(['Status Dana', statusBadgePub(row.status_dana)]);
    if (row.sumber) items.push(['Sumber', escapeHtml(row.sumber)]);
  } else {
    if (row.tujuan) items.push(['Tujuan / Penerima', escapeHtml(row.tujuan)]);
  }
  if (row.keterangan) items.push(['Keterangan', escapeHtml(row.keterangan)]);

  document.getElementById('detail-list-pub').innerHTML = items.map(([k, v]) =>
    `<div>
       <dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt>
       <dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd>
     </div>`
  ).join('');

  const buktiWrap = document.getElementById('detail-bukti-pub');
  if (row.file_bukti) {
    const url = BASE_URL + 'uploads/bukti/' + row.file_bukti;
    buktiWrap.innerHTML = `<div class="pt-4 border-t border-slate-200 dark:border-slate-700">
      <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Bukti Transaksi</div>
      ${buktiHTML(url, row.file_bukti)}
    </div>`;
  } else {
    buktiWrap.innerHTML = '';
  }

  document.getElementById('modal-detail-txn').classList.remove('hidden');
  lucide.createIcons({ props: { search: document.getElementById('modal-detail-txn') } });
}
window.showDetailPub = showDetailPub;
function closeDetailModal() { document.getElementById('modal-detail-txn').classList.add('hidden'); }
window.closeDetailModal = closeDetailModal;

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  buildKat();
  loadTren(<?= $tahunSekarang ?>);
  refreshTxn();
});
</script>
<?= $this->endSection() ?>
