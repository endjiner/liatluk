const BASE_URL = (function() {
  const cfg = '<?= rtrim(base_url(), '/') ?>/';
  return (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && cfg.includes('localhost'))
    ? (window.location.origin + '/')
    : cfg;
})();
const KPI_ENDPOINT_PUB = BASE_URL + 'kpi-periode';

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
  const wrap = document.getElementById('chart-kategori-wrap');
  const canvas = document.getElementById('chart-kategori');
  const empty = document.getElementById('chart-kategori-empty');
  if (!data.length) {
    if (wrap) wrap.classList.add('hidden');
    empty.classList.remove('hidden');
    empty.classList.add('flex');
    if (chartKat) { chartKat.destroy(); chartKat = null; }
    return;
  }
  if (wrap) wrap.classList.remove('hidden');
  empty.classList.add('hidden');
  empty.classList.remove('flex');
  if (chartKat) chartKat.destroy();
  chartKat = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: data.map(d => d.kategori),
      datasets: [{ data: data.map(d => d.total), backgroundColor: ['#1e5fbe','#059669','#dc2626','#d97706','#7c3aed','#0891b2','#65a30d','#c026d3'] }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 11 }, padding: 12, boxWidth: 14 } } }
    }
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

/* ── Toggle tipe transaksi (styled button, sync ke hidden checkbox) ── */
const TXN_TOGGLE_ACTIVE = {
  pemasukan:    'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 shadow-sm',
  pengeluaran:  'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 shadow-sm',
};
const TXN_TOGGLE_INACTIVE = 'text-slate-500 dark:text-slate-400 hover:text-slate-700';

function toggleTipeTxn(tipe) {
  const btn = document.getElementById('toggle-' + tipe);
  const cb  = document.getElementById('filter-' + tipe);
  const active = !cb.checked; // toggle
  cb.checked = active;
  btn.className = btn.className
    .replace(TXN_TOGGLE_ACTIVE[tipe], '').replace(TXN_TOGGLE_INACTIVE, '').trim();
  btn.className += ' txn-toggle-btn active-' + tipe + ' inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all ' +
    (active ? TXN_TOGGLE_ACTIVE[tipe] : TXN_TOGGLE_INACTIVE);
  currentPage = null;
  refreshTxn();
}

// Init toggle state on load
document.addEventListener('DOMContentLoaded', () => {
  ['pemasukan','pengeluaran'].forEach(t => {
    const btn = document.getElementById('toggle-' + t);
    if (btn) btn.className = 'txn-toggle-btn active-' + t + ' inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all ' + TXN_TOGGLE_ACTIVE[t];
  });
});

/* ── Transaksi (dengan pagination) ──
   Nomor baris selalu urut dari transaksi paling awal (dihitung backend). currentPage=null
   berarti "biarkan backend pilih default" — yaitu halaman TERAKHIR (transaksi terbaru),
   supaya begitu dibuka tetap yang paling baru yang terlihat duluan. */
let currentPage = null;
let txnRequestSeq = 0;

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

  const tbody = document.getElementById('txn-tbody');
  const seq = ++txnRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="7" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  fetch(BASE_URL + 'transaksi/ajax?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (seq !== txnRequestSeq) return;
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      currentPage = data.page || 1;
      renderTxn(data.data || []);
      document.getElementById('txn-total').textContent = new Intl.NumberFormat('id-ID').format(data.total || 0);
      renderPagination(data.total || 0, parseInt(perPage), currentPage);
    })
    .catch((e) => {
      console.error('Gagal memuat Daftar Transaksi:', e);
      if (seq !== txnRequestSeq) return;
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
    });
}

function renderTxn(rows) {
  const tbody = document.getElementById('txn-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(r => {
    const isP = r.tipe === 'pemasukan';
    const nominal = isP ? (parseFloat(r.jumlah_diterima) || parseFloat(r.jumlah)) : parseFloat(r.jumlah);
    const dateFormatted = new Date(r.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const badge = isP
      ? '<span class="badge badge-success">' + iconsax('trend-up', 'w-3 h-3') + 'Pemasukan</span>'
      : '<span class="badge badge-danger">' + iconsax('trend-down', 'w-3 h-3') + 'Pengeluaran</span>';
    const rowJson = JSON.stringify(r).replace(/'/g, "&#39;");
    return `<tr class="cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors" onclick='showDetailPub(${rowJson})'>
      <td class="text-center text-slate-500">${r.nomor ?? '-'}</td>
      <td class="whitespace-nowrap">${dateFormatted}</td>
      <td class="min-w-[120px] max-w-[200px] break-words whitespace-normal">
        <div class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.kategori || '-')}</div>
        ${r.kode_mak ? `<div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${escapeHtml(r.kode_mak)}</div>` : ''}
      </td>
      <td class="min-w-[120px] max-w-[180px] break-words whitespace-normal">${escapeHtml(r.sumber || r.tujuan || '-')}</td>
      <td>${badge}</td>
      <td class="text-right font-medium text-currency ${isP ? 'text-income' : 'text-expense'}">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(nominal))}</td>
      <td class="text-center" onclick="event.stopPropagation()">
        <button type="button" onclick='event.stopPropagation(); showDetailPub(${rowJson})' class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Detail">
          ${iconsax('eye', 'w-4 h-4')}
        </button>
      </td>
    </tr>`;
  }).join('');
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
  filterTimer = setTimeout(() => { currentPage = null; refreshTxn(); }, 350);
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
              ${iconsax('document-text', 'w-8 h-8 text-red-600')}
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
    ['Nominal', `<span class="font-semibold ${isP ? 'text-income' : 'text-expense'}">${fmtRp(row.jumlah)}</span>`],
  ];
  if (isP) {
    items.push(['Jumlah Diterima', fmtRp(row.jumlah_diterima)]);
    items.push(['Status Dana', statusBadgePub(row.status_dana)]);
    if (row.sumber) items.push(['Sumber', escapeHtml(row.sumber)]);
  } else {
    if (row.tujuan) items.push(['Tujuan / Penerima', escapeHtml(row.tujuan)]);
  }
  if (row.kode_mak) items.push(['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(row.kode_mak)}</span>`]);
  if (row.no_surat_tugas) items.push(['No. Surat Tugas', escapeHtml(row.no_surat_tugas)]);
  if (row.no_spm) items.push(['No. SPM', escapeHtml(row.no_spm)]);
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
}
window.showDetailPub = showDetailPub;
function closeDetailModal() { document.getElementById('modal-detail-txn').classList.add('hidden'); }
window.closeDetailModal = closeDetailModal;
