<script>
(function() {
const BASE_URL = (function() {
  const cfg = '<?= rtrim(base_url(), '/') ?>/';
  return (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && cfg.includes('localhost'))
    ? (window.location.origin + '/')
    : cfg;
})();
const IS_SUPER_ADMIN = <?= $isSuperAdmin ? 'true' : 'false' ?>;

/* ── Helpers ── */
function openExportMenu() { openModal('modal-export'); }
function fmtRp(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(v) || 0)); }
function fmtDate(s) { if (!s) return '-'; const d = new Date(s); return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }); }
function statusBadge(s) {
  const map = { diterima: ['badge-success','Diterima'], sebagian: ['badge-warning','Sebagian'], belum_diterima: ['badge-danger','Belum Diterima'] };
  const [cls, label] = map[s] || ['badge-muted', s || '-'];
  return `<span class="badge ${cls}">${label}</span>`;
}
function buktiPreviewHTML(url, filename) {
  if (!filename) return '<div class="text-sm text-slate-500">Tidak ada bukti terlampir.</div>';
  const ext = (filename.split('.').pop() || '').toLowerCase();
  if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
    return `<a href="${url}" target="_blank" class="inline-block">
              <img src="${url}" alt="Bukti" class="max-h-64 rounded-lg border border-slate-200 shadow-soft">
              <div class="text-xs text-primary-600 hover:underline mt-1">Buka di tab baru</div>
            </a>`;
  }
  if (ext === 'pdf') {
    return `<a href="${url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border border-slate-200 hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition">
              ${iconsax('document-text', 'w-8 h-8 text-red-600')}
              <div>
                <div class="font-medium text-sm">${filename}</div>
                <div class="text-xs text-primary-600">Klik untuk buka PDF</div>
              </div>
            </a>`;
  }
  return `<a href="${url}" target="_blank" class="text-primary-600 hover:underline text-sm">${filename}</a>`;
}
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

/* ── Daftar Transaksi (AJAX + pagination), pola sama seperti dashboard publik ──
   Nomor baris selalu urut dari transaksi paling awal (dihitung backend). currentPage=null
   berarti "biarkan backend pilih default" — yaitu halaman TERAKHIR (transaksi terbaru). */
let currentPage = null;

// Nomor urut request — kalau user ketik cepat, respons yang lebih lama (mis. dari huruf
// pertama) bisa balik BELAKANGAN dari respons huruf terakhir dan menimpa hasil yang lebih
// baru dengan yang basi. Cuma respons dari request PALING TERAKHIR yang boleh dirender.
let txnRequestSeq = 0;
function refreshTxn() {
  const showP  = document.getElementById('filter-pemasukan').checked;
  const showE  = document.getElementById('filter-pengeluaran').checked;
  const perPage = document.getElementById('filter-perpage').value;
  const search = document.getElementById('filter-search').value;
  const bulan  = document.getElementById('filter-bulan').value;
  const tahun  = document.getElementById('filter-tahun').value;

  const params = new URLSearchParams({
    show_pemasukan: showP ? '1' : '0',
    show_pengeluaran: showE ? '1' : '0',
    per_page: perPage,
    search, bulan, tahun,
  });
  if (currentPage) params.set('page', currentPage);

  const colSpan = IS_SUPER_ADMIN ? 8 : 7;
  const tbody = document.getElementById('txn-tbody');
  const seq = ++txnRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = `<tr><td colspan="${colSpan}" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>`;
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  fetch(BASE_URL + 'admin/keuangan/ajax?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (seq !== txnRequestSeq) return; // ada request lebih baru yang menyusul, respons ini basi
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      currentPage = data.page || 1;
      renderTxn(data.data || []);
      document.getElementById('txn-total').textContent = new Intl.NumberFormat('id-ID').format(data.total || 0);
      renderPagination(data.total || 0, parseInt(perPage), currentPage);
      clearAllSelection();
    })
    .catch(() => {
      if (seq !== txnRequestSeq) return;
      tbody.classList.remove('opacity-40', 'pointer-events-none');
      document.getElementById('txn-tbody').innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>`;
    });
}
window.refreshTxn = refreshTxn;

function renderTxn(rows) {
  const tbody = document.getElementById('txn-tbody');
  const colSpan = IS_SUPER_ADMIN ? 8 : 7;
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>`;
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
    const editFn = isP ? 'editPemasukan' : 'editPengeluaran';
    const deleteFn = isP ? 'deletePemasukan' : 'deletePengeluaran';
    const selesaiBtn = (isP && (r.status_dana === 'sebagian' || r.status_dana === 'belum_diterima'))
      ? `<button type="button" onclick="event.stopPropagation(); tandaiSelesai(${r.id}, ${r.jumlah})" title="Tandai Selesai/Lunas" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-slate-500 hover:text-emerald-600 shrink-0">${iconsax('tick-circle', 'w-4 h-4 shrink-0')}</button>`
      : '';

    const checkboxTd = IS_SUPER_ADMIN
      ? `<td onclick="event.stopPropagation()"><input type="checkbox" class="row-checkbox row-checkbox-${r.tipe} form-checkbox" onclick="event.stopPropagation(); onRowCheck(this)"></td>`
      : '';

    const actionTd = IS_SUPER_ADMIN
      ? `<td class="text-center whitespace-nowrap min-w-[100px]" onclick="event.stopPropagation()">
          <div class="inline-flex items-center justify-center gap-1">
            <button type="button" onclick='event.stopPropagation(); showDetailRow(${rowJson})' title="Detail" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600 shrink-0">${iconsax('eye', 'w-4 h-4 shrink-0')}</button>
            <button type="button" onclick='event.stopPropagation(); ${editFn}(${rowJson})' title="Edit" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600 shrink-0">${iconsax('edit-2', 'w-4 h-4 shrink-0')}</button>
            ${selesaiBtn}
            <button type="button" onclick="event.stopPropagation(); ${deleteFn}(${r.id})" title="Hapus" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600 shrink-0">${iconsax('trash', 'w-4 h-4 shrink-0')}</button>
          </div>
        </td>`
      : `<td class="text-center whitespace-nowrap" onclick="event.stopPropagation()">
          <button type="button" onclick='event.stopPropagation(); showDetailRow(${rowJson})' title="Detail" class="w-7 h-7 inline-flex items-center justify-center rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600 shrink-0">${iconsax('eye', 'w-4 h-4 shrink-0')}</button>
        </td>`;

    return `<tr data-id="${r.id}" data-type="${r.tipe}" class="cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors" onclick='showDetailRow(${rowJson})'>
      ${checkboxTd}
      <td class="text-center text-slate-500">${r.nomor ?? '-'}</td>
      <td class="whitespace-nowrap">${dateFormatted}</td>
      <td class="min-w-[120px] max-w-[200px] break-words whitespace-normal">
        <div class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.kategori || '-')}</div>
        ${r.kode_mak ? `<div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${escapeHtml(r.kode_mak)}</div>` : ''}
      </td>
      <td class="min-w-[120px] max-w-[180px] break-words whitespace-normal">${escapeHtml(r.sumber || r.tujuan || '-')}</td>
      <td>${badge}</td>
      <td class="text-right font-medium text-currency ${isP ? 'text-income' : 'text-expense'}">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(nominal))}</td>
      ${actionTd}
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

/* ── Toggle tipe transaksi admin (styled button → sync hidden checkbox) ── */
const ADM_TOGGLE_ACTIVE = {
  pemasukan:   'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 shadow-sm',
  pengeluaran: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 shadow-sm',
};
const ADM_TOGGLE_INACTIVE = 'text-slate-500 dark:text-slate-400 hover:text-slate-700';
function admToggleTipeTxn(tipe) {
  const btn = document.getElementById('adm-toggle-' + tipe);
  const cb  = document.getElementById('filter-' + tipe);
  cb.checked = !cb.checked;
  const isActive = cb.checked;
  btn.className = 'adm-txn-toggle inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all ' +
    (isActive ? ADM_TOGGLE_ACTIVE[tipe] : ADM_TOGGLE_INACTIVE);
  currentPage = null;
  refreshTxn();
}
window.admToggleTipeTxn = admToggleTipeTxn;

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
function showDetailRow(row) {
  const isP = row.tipe === 'pemasukan';
  document.getElementById('detail-subtitle').textContent = isP ? 'Pemasukan' : 'Pengeluaran';
  const items = [
    ['Tanggal', fmtDate(row.tanggal)],
    ['Kategori', row.kategori ? escapeHtml(row.kategori) : '-'],
    ['Nominal', `<span class="font-semibold ${isP ? 'text-income' : 'text-expense'}">${fmtRp(row.jumlah)}</span>`],
  ];
  if (isP) {
    items.push(['Jumlah Diterima', fmtRp(row.jumlah_diterima)]);
    items.push(['Status Dana', statusBadge(row.status_dana)]);
    items.push(['Sumber', row.sumber ? escapeHtml(row.sumber) : '-']);
  } else {
    items.push(['Tujuan / Penerima', row.tujuan ? escapeHtml(row.tujuan) : '-']);
  }
  if (row.kode_mak) items.push(['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(row.kode_mak)}</span>`]);
  if (row.no_surat_tugas) items.push(['No. Surat Tugas', escapeHtml(row.no_surat_tugas)]);
  if (row.no_spm) items.push(['No. SPM', escapeHtml(row.no_spm)]);
  items.push(['Keterangan', row.keterangan ? escapeHtml(row.keterangan) : '-']);
  if (row.catatan_internal) items.push(['Catatan Internal', escapeHtml(row.catatan_internal)]);
  items.push(['Dibuat', row.created_at || '-']);

  document.getElementById('detail-list').innerHTML = items.map(([k, v]) =>
    `<div>
       <dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt>
       <dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd>
     </div>`
  ).join('');

  // Bukti preview
  const buktiWrap = document.getElementById('detail-bukti-wrap');
  if (row.file_bukti) {
    const url = BASE_URL + 'uploads/bukti/' + row.file_bukti;
    buktiWrap.innerHTML = `<div class="pt-4 border-t border-slate-200 dark:border-slate-700">
      <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Bukti Transaksi</div>
      ${buktiPreviewHTML(url, row.file_bukti)}
    </div>`;
  } else {
    buktiWrap.innerHTML = '';
  }
  openModal('modal-detail');
}
window.showDetailRow = showDetailRow;

/* ── Edit Pemasukan ── */
function editPemasukan(row) {
  document.getElementById('edit-p-id').value = row.id;
  document.getElementById('edit-p-tanggal').value = row.tanggal;
  document.getElementById('edit-p-kategori').value = row.kategori;
  // FIX BUG: parse DB decimal safely before setting into input-rupiah
  setRupiahValue(document.getElementById('edit-p-jumlah'), row.jumlah);
  setRupiahValue(document.getElementById('edit-p-diterima'), row.jumlah_diterima);
  document.getElementById('edit-p-status').value = row.status_dana;
  document.getElementById('edit-p-sumber').value = row.sumber || '';
  document.getElementById('edit-p-mak').value = row.kode_mak || '';
  document.getElementById('edit-p-st').value = row.no_surat_tugas || '';
  document.getElementById('edit-p-spm').value = row.no_spm || '';
  document.getElementById('edit-p-ket').value = row.keterangan || '';
  // Preview bukti
  const prev = document.getElementById('edit-p-bukti-preview');
  if (row.file_bukti) {
    const url = BASE_URL + 'uploads/bukti/' + row.file_bukti;
    prev.innerHTML = buktiPreviewHTML(url, row.file_bukti);
  } else {
    prev.innerHTML = '<div class="text-xs text-slate-500">Belum ada bukti terlampir.</div>';
  }
  openModal('modal-edit-pemasukan');
}
window.editPemasukan = editPemasukan;

async function submitEditPemasukan(e) {
  e.preventDefault();
  const id = document.getElementById('edit-p-id').value;
  const form = document.getElementById('form-edit-pemasukan');
  const res  = await fetch(BASE_URL + 'admin/keuangan/pemasukan/update/' + id, { method: 'POST', body: buildFormData(form) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-edit-pemasukan'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal update', 'error');
}
window.submitEditPemasukan = submitEditPemasukan;

/* ── Edit Pengeluaran ── */
function editPengeluaran(row) {
  document.getElementById('edit-e-id').value = row.id;
  document.getElementById('edit-e-tanggal').value = row.tanggal;
  document.getElementById('edit-e-kategori').value = row.kategori;
  setRupiahValue(document.getElementById('edit-e-jumlah'), row.jumlah);
  document.getElementById('edit-e-tujuan').value = row.tujuan || '';
  document.getElementById('edit-e-ket').value = row.keterangan || '';
  const prev = document.getElementById('edit-e-bukti-preview');
  if (row.file_bukti) {
    const url = BASE_URL + 'uploads/bukti/' + row.file_bukti;
    prev.innerHTML = buktiPreviewHTML(url, row.file_bukti);
  } else {
    prev.innerHTML = '<div class="text-xs text-slate-500">Belum ada bukti terlampir.</div>';
  }
  openModal('modal-edit-pengeluaran');
}
window.editPengeluaran = editPengeluaran;

async function submitEditPengeluaran(e) {
  e.preventDefault();
  const id = document.getElementById('edit-e-id').value;
  const form = document.getElementById('form-edit-pengeluaran');
  const res  = await fetch(BASE_URL + 'admin/keuangan/pengeluaran/update/' + id, { method: 'POST', body: buildFormData(form) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-edit-pengeluaran'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal update', 'error');
}
window.submitEditPengeluaran = submitEditPengeluaran;

/* ── Delete (single) ── */
let pendingDelete = null;

/* ── Tandai Selesai (shortcut: sebagian/belum diterima -> lunas sekaligus) ── */
function tandaiSelesai(id, jumlah) {
  tampilkanKonfirmasi('Tandai pemasukan ini sebagai sudah diterima penuh (Rp ' + new Intl.NumberFormat('id-ID').format(jumlah) + ')?', async () => {
    const fd = new FormData();
    fd.append('status_dana', 'diterima');
    fd.append('jumlah_diterima', jumlah);
    const res  = await fetch(BASE_URL + 'admin/keuangan/pemasukan/status/' + id, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal menandai selesai', 'error');
  });
}
window.tandaiSelesai = tandaiSelesai;

function deletePemasukan(id)   { pendingDelete = BASE_URL + 'admin/keuangan/pemasukan/delete/' + id;   openModal('modal-hapus'); }
function deletePengeluaran(id) { pendingDelete = BASE_URL + 'admin/keuangan/pengeluaran/delete/' + id; openModal('modal-hapus'); }
window.deletePemasukan = deletePemasukan;
window.deletePengeluaran = deletePengeluaran;

const btnHapus = document.getElementById('btn-confirm-hapus');
if (btnHapus) {
  btnHapus.onclick = async function() {
    if (!pendingDelete) return;
    const res = await fetch(pendingDelete, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); closeModal('modal-hapus'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
    pendingDelete = null;
  };
}

/* ── Bulk select (satu tabel gabungan, satu master checkbox) ── */
function toggleSelectAllTxn() {
  const master = document.getElementById('select-all-txn');
  document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.checked = master.checked;
    cb.closest('tr')?.classList.toggle('selected', cb.checked);
  });
  updateBulkBar();
}
window.toggleSelectAllTxn = toggleSelectAllTxn;

function onRowCheck(cb) {
  cb.closest('tr')?.classList.toggle('selected', cb.checked);
  updateBulkBar();
}
window.onRowCheck = onRowCheck;

function getSelectedIds() {
  const p = Array.from(document.querySelectorAll('.row-checkbox-pemasukan:checked')).map(cb => cb.closest('tr').dataset.id);
  const e = Array.from(document.querySelectorAll('.row-checkbox-pengeluaran:checked')).map(cb => cb.closest('tr').dataset.id);
  return { pemasukan: p, pengeluaran: e };
}

function updateBulkBar() {
  const { pemasukan, pengeluaran } = getSelectedIds();
  const count = pemasukan.length + pengeluaran.length;
  document.getElementById('bulk-count').textContent = count;
  document.getElementById('bulk-count-modal').textContent = count;
  document.getElementById('bulk-action-bar').classList.toggle('hidden', count === 0);
}

function clearAllSelection() {
  document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = false; cb.closest('tr')?.classList.remove('selected'); });
  const master = document.getElementById('select-all-txn');
  if (master) master.checked = false;
  updateBulkBar();
}
window.clearAllSelection = clearAllSelection;

const btnBulkHapus = document.getElementById('btn-confirm-bulk-hapus');
if (btnBulkHapus) {
  btnBulkHapus.onclick = async function() {
    const { pemasukan, pengeluaran } = getSelectedIds();
    if (pemasukan.length + pengeluaran.length === 0) return;
    const fd = new FormData();
    pemasukan.forEach(id => fd.append('pemasukan_ids[]', id));
    pengeluaran.forEach(id => fd.append('pengeluaran_ids[]', id));
    const res = await fetch(BASE_URL + 'admin/keuangan/bulk-delete', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); closeModal('modal-bulk-hapus'); setTimeout(() => location.reload(), 800); }
    else showToast(json.message || 'Gagal menghapus', 'error');
  };
}

/* ── Import ── */
async function submitImport(e) {
  e.preventDefault();
  const form = document.getElementById('form-import');
  const res = await fetch(BASE_URL + 'admin/keuangan/import', { method: 'POST', body: new FormData(form) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-import'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal import', 'error');
}
window.submitImport = submitImport;
window.openExportMenu = openExportMenu;

document.addEventListener('DOMContentLoaded', () => { refreshTxn(); });
})();
</script>
