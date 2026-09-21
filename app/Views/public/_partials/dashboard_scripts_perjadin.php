/* ── Tab: Transaksi Keuangan / Perjalanan Dinas / Dana Taktis ── */
let perjadinSudahDimuat = false;
let danaTaktisSudahDimuat = false;
function aktifkanTab(nama, scroll = false) {
  ['transaksi', 'perjadin', 'dana-taktis'].forEach(n => {
    const p = document.getElementById('panel-' + n);
    const b = document.getElementById('tab-btn-' + n);
    if (p) p.classList.toggle('hidden', n !== nama);
    if (b) b.classList.toggle('active', n === nama);
  });
  if (nama === 'perjadin' && !perjadinSudahDimuat) {
    perjadinSudahDimuat = true;
    muatDaftarTripPerjadin();
  }
  if (nama === 'dana-taktis' && !danaTaktisSudahDimuat) {
    danaTaktisSudahDimuat = true;
    muatDaftarDanaTaktisPub();
  }
  if (scroll) {
    setTimeout(() => {
      const target = document.getElementById('segment-tabs') || document.getElementById('tab-btn-' + nama) || document.getElementById('panel-' + nama);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 50);
  }
}
function scrollKeDanaTaktis(e) {
  if (e) e.preventDefault();
  aktifkanTab('dana-taktis', true);
  if (history.replaceState) {
    history.replaceState(null, '', '#dana-taktis');
  }
}
window.aktifkanTab = aktifkanTab;
window.scrollKeDanaTaktis = scrollKeDanaTaktis;

/* ── Handler Toggle Checkbox Filter Status (Non-default style chip) ── */
function toggleStatusFilterPub(prefix, changedType) {
  const chkBelum = document.getElementById(prefix + '-chk-belum');
  const chkLunas = document.getElementById(prefix + '-chk-lunas');
  if (!chkBelum || !chkLunas) return;

  // Jika kedua checkbox dicoba di-uncheck, jangan biarkan kosong: reset keduanya jadi checked (semua)
  if (!chkBelum.checked && !chkLunas.checked) {
    chkBelum.checked = true;
    chkLunas.checked = true;
  }

  // Sinkronkan class styling .is-checked
  const chipBelum = chkBelum.closest('.status-chip');
  const chipLunas = chkLunas.closest('.status-chip');
  if (chipBelum) chipBelum.classList.toggle('is-checked', chkBelum.checked);
  if (chipLunas) chipLunas.classList.toggle('is-checked', chkLunas.checked);

  // Tentukan parameter status
  let statusVal = '';
  if (chkBelum.checked && !chkLunas.checked) statusVal = 'belum';
  else if (!chkBelum.checked && chkLunas.checked) statusVal = 'lunas';
  else statusVal = ''; // Keduanya checked = Semua Status

  if (prefix === 'dt-pub') {
    const input = document.getElementById('dt-filter-status-pub');
    if (input) input.value = statusVal;
    muatDaftarDanaTaktisPub(1);
  } else if (prefix === 'pd-pub') {
    const input = document.getElementById('filter-status-perjadin');
    if (input) input.value = statusVal;
    muatDaftarTripPerjadin(1);
  }
}
window.toggleStatusFilterPub = toggleStatusFilterPub;

/* ── Perjalanan Dinas (tabel datar 1 baris = 1 peserta, filter & pagination via AJAX) ── */
const rupiahPd = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
let filterDebouncePerjadin = null;
let halamanPerjadinSaatIni = 1;
function jadwalkanMuatDaftarPerjadin() {
  clearTimeout(filterDebouncePerjadin);
  filterDebouncePerjadin = setTimeout(() => muatDaftarTripPerjadin(1), 350);
}
// Nomor urut request — kalau user ketik cepat, respons yang lebih lama (mis. dari huruf
// pertama) bisa balik BELAKANGAN dari respons huruf terakhir dan menimpa hasil yang lebih
// baru dengan yang basi. Cuma respons dari request PALING TERAKHIR yang boleh dirender.
let tripPerjadinRequestSeq = 0;
let perjadinHalamanPub = null;

async function muatDaftarTripPerjadin(page) {
  if (page !== undefined && page !== null) {
    perjadinHalamanPub = page;
  }
  const params = new URLSearchParams();
  const search = document.getElementById('filter-search-perjadin')?.value || '';
  const status = document.getElementById('filter-status-perjadin')?.value || '';
  const tahun  = document.getElementById('filter-tahun-perjadin')?.value || '';
  const bulan  = document.getElementById('filter-bulan-perjadin')?.value || '';
  const perPage = document.getElementById('filter-per-page-perjadin')?.value || '10';

  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (tahun) params.set('tahun', tahun);
  if (bulan) params.set('bulan', bulan);
  params.set('per_page', perPage);
  if (perjadinHalamanPub) params.set('page', perjadinHalamanPub);

  const tbody = document.getElementById('pd-tbody-perjadin');
  const seq = ++tripPerjadinRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="14" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res = await fetch(BASE_URL + 'perjalanan-dinas/ajax?' + params.toString());
    const json = await res.json();
    if (seq !== tripPerjadinRequestSeq) return; // ada request lebih baru yang menyusul, respons ini basi
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    perjadinHalamanPub = json.page;
    renderTripTablePerjadin(json.data);
    renderPaginasiHalaman(document.getElementById('pd-pagination-wrap-perjadin'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'peserta', onPageChange: muatDaftarTripPerjadin,
    });
    document.getElementById('pd-total-perjadin').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== tripPerjadinRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}

function renderTripTablePerjadin(rows) {
  const tbody = document.getElementById('pd-tbody-perjadin');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="14" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  // Hitung jumlah peserta per perjalanan dinas untuk merge (rowspan)
  const tripCounts = {};
  rows.forEach(r => {
    tripCounts[r.perjalanan_dinas_id] = (tripCounts[r.perjalanan_dinas_id] || 0) + 1;
  });

  let lastTripId = null;
  let noTrip = 0;
  tbody.innerHTML = rows.map(r => {
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
          ? `<span class="badge badge-info" title="Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)}">Sebagian</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
          : '<span class="badge badge-warning">Belum Lunas</span>'));

    const count = tripCounts[r.perjalanan_dinas_id] || 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80 dark:border-amber-700/80 shadow-xs">${escapeHtml(r.no_pd)}</span>`
          : `<span class="text-sm font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(r.no_pd)}</span>`)
      : `<span class="text-xs text-slate-400 font-medium">${noTrip}</span>`;

    const selTrip = tripBaru ? `
      <td rowspan="${count}" class="align-top text-center bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">${nomorPdHtml}</td>
      <td rowspan="${count}" class="align-top min-w-[200px] max-w-[320px] bg-slate-50/70 dark:bg-slate-900/60 border-r border-slate-200 dark:border-slate-700/80 p-3">
        <p class="font-medium text-slate-800 dark:text-slate-100 break-words whitespace-normal" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 break-words">${escapeHtml(r.no_surat_tugas || '-')} &middot; ${tgl}</p>
        <p class="text-[11px] font-mono text-slate-600 dark:text-slate-300 mt-0.5 break-words"><span class="font-semibold text-slate-400 dark:text-slate-500">MAK:</span> ${escapeHtml(r.kode_mak || '-')}${r.no_spm ? ` &middot; <span class="font-semibold text-slate-400 dark:text-slate-500">SPM:</span> ${escapeHtml(r.no_spm)}` : ''}</p>
      </td>` : '';

    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    const rowEncoded = encodeURIComponent(JSON.stringify(r));
    return `<tr class="${tripBaru ? 'border-t-2 border-slate-200 dark:border-slate-700' : 'border-t border-slate-100 dark:border-slate-800/80'} ${rowBgClass} cursor-pointer transition-colors" onclick="showPerjadinModalPub('${rowEncoded}')">
      ${selTrip}
      <td class="font-medium text-slate-700 dark:text-slate-200 min-w-[140px] max-w-[220px] break-words whitespace-normal" title="${escapeHtml(r.nama_peserta)}">${escapeHtml(r.nama_peserta)}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_harian) > 0 ? rupiahPd(r.uang_harian) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullboard) > 0 ? rupiahPd(r.meeting_fullboard) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.meeting_fullday) > 0 ? rupiahPd(r.meeting_fullday) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.uang_representasi) > 0 ? rupiahPd(r.uang_representasi) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.transport_lokal) > 0 ? rupiahPd(r.transport_lokal) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${parseFloat(r.bbm) > 0 ? rupiahPd(r.bbm) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${(r.tiket || []).length > 0 ? ((r.tiket.length) + 'x &middot; ' + rupiahPd(totalTiket)) : '-'}</td>
      <td class="text-right text-currency whitespace-nowrap">${r.hotel ? (escapeHtml(r.hotel.nama_hotel || 'Hotel') + '<br><span class="text-xs">' + rupiahPd(totalHotel) + '</span>') : '-'}</td>
      <td class="text-right text-currency font-semibold whitespace-nowrap">${rupiahPd(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600 whitespace-nowrap">${rupiahPd(r.dana_taktis)}</td>
      <td class="text-center whitespace-nowrap">${statusBadge}</td>
    </tr>`;
  }).join('');
}


/* ── Dana Taktis (tabel datar semua pegawai, filter & pagination via AJAX) ── */
let dtPubFilterDebounce = null;
function dtPubJadwalkanMuat() {
  clearTimeout(dtPubFilterDebounce);
  dtPubFilterDebounce = setTimeout(() => muatDaftarDanaTaktisPub(1), 350);
}

/* Autocomplete suggestions untuk search dana taktis */
let dtSuggestionCache = []; // [{label, value}] — diisi saat data masuk
function dtShowSuggestions() {
  const q = document.getElementById('dt-filter-search-pub').value.trim().toLowerCase();
  const ul = document.getElementById('dt-suggestions');
  if (!q || q.length < 2 || !dtSuggestionCache.length) { ul.classList.add('hidden'); return; }
  const matches = dtSuggestionCache.filter(s => s.toLowerCase().includes(q)).slice(0, 8);
  if (!matches.length) { ul.classList.add('hidden'); return; }
  ul.innerHTML = matches.map(s =>
    `<li class="px-3 py-2 cursor-pointer hover:bg-primary-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
         onmousedown="dtPickSuggestion('${escapeHtml(s).replace(/'/g, "&#39;")}')">${escapeHtml(s)}</li>`
  ).join('');
  ul.classList.remove('hidden');
}
function dtHideSuggestions() { document.getElementById('dt-suggestions').classList.add('hidden'); }
function dtPickSuggestion(val) {
  document.getElementById('dt-filter-search-pub').value = val;
  dtHideSuggestions();
  muatDaftarDanaTaktisPub(1);
}

let dtPubRequestSeq = 0;
let dtHalamanPub = null;

async function muatDaftarDanaTaktisPub(page) {
  if (page !== undefined && page !== null) {
    dtHalamanPub = page;
  }
  const params = new URLSearchParams();
  const search = document.getElementById('dt-filter-search-pub').value;
  const status = document.getElementById('dt-filter-status-pub').value;
  const bulan = document.getElementById('dt-filter-bulan-pub').value;
  const tahun = document.getElementById('dt-filter-tahun-pub').value;
  const perPage = document.getElementById('dt-filter-perpage-pub').value;
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (bulan) params.set('bulan', bulan);
  if (tahun) params.set('tahun', tahun);
  params.set('per_page', perPage);
  if (dtHalamanPub) params.set('page', dtHalamanPub);

  const tbody = document.getElementById('dt-tbody-pub');
  const seq = ++dtPubRequestSeq;
  if (!tbody.hasChildNodes() || tbody.querySelector('.loading-placeholder')) {
    tbody.innerHTML = '<tr><td colspan="9" class="loading-placeholder text-center py-8 text-slate-500">Memuat data...</td></tr>';
  } else {
    tbody.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200');
  }

  try {
    const res = await fetch(BASE_URL + 'perjalanan-dinas/dana-taktis/ajax?' + params.toString());
    const json = await res.json();
    if (seq !== dtPubRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    dtHalamanPub = json.page;
    renderDanaTaktisTablePub(json.data, json.page, json.per_page);
    dtUpdateSummary(json.summary, json.data);
    renderPaginasiHalaman(document.getElementById('dt-pagination-wrap-pub'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'baris', onPageChange: muatDaftarDanaTaktisPub,
    });
    document.getElementById('dt-total-pub').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    if (seq !== dtPubRequestSeq) return;
    tbody.classList.remove('opacity-40', 'pointer-events-none');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}

function dtUpdateSummary(summary, rows) {
  if (summary) {
    document.getElementById('dt-sum-uang-harian').textContent = fmtRp(summary.sum_uang_harian);
    document.getElementById('dt-sum-spj').textContent         = fmtRp(summary.sum_total_spj);
    document.getElementById('dt-sum-taktis').textContent      = fmtRp(summary.sum_dana_taktis);
    document.getElementById('dt-sum-belum').textContent       = fmtRp(summary.sum_belum_dibayar);
    return;
  }
  let sumUH = 0, sumSpj = 0, sumTaktis = 0, sumBelum = 0;
  (rows || []).forEach(r => {
    sumUH     += parseFloat(r.uang_harian)  || 0;
    sumSpj    += parseFloat(r.total_spj)    || 0;
    sumTaktis += parseFloat(r.dana_taktis)  || 0;
    const isLunas = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
    if (!isLunas) {
      const setor = parseFloat(r.jumlah_disetor) || 0;
      sumBelum += (parseFloat(r.dana_taktis) || 0) - setor;
    }
  });
  document.getElementById('dt-sum-uang-harian').textContent = fmtRp(sumUH);
  document.getElementById('dt-sum-spj').textContent = fmtRp(sumSpj);
  document.getElementById('dt-sum-taktis').textContent = fmtRp(sumTaktis);
  document.getElementById('dt-sum-belum').textContent = fmtRp(sumBelum);
}

function renderDanaTaktisTablePub(rows, page, perPage) {
  // Update suggestion cache dari nama, no. surat tugas & MAK
  const namaSet = new Set(dtSuggestionCache);
  rows.forEach(r => {
    if (r.nama_peserta)   namaSet.add(r.nama_peserta);
    if (r.no_surat_tugas) namaSet.add(r.no_surat_tugas);
    if (r.kode_mak)       namaSet.add(r.kode_mak);
  });
  dtSuggestionCache = [...namaSet];

  const tbody = document.getElementById('dt-tbody-pub');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  const curPage = page || dtHalamanPub || 1;
  const curPerPage = perPage || 10;
  tbody.innerHTML = rows.map((r, idx) => {
    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const isLunas = r.status_lunas === 'lunas' || parseFloat(r.dana_taktis) === 0;
    const isSebagian = r.status_lunas === 'sebagian';
    const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
    const statusBadge = isLunas
      ? `<span class="badge badge-success">Lunas</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
      : isSebagian
      ? `<span class="badge badge-info" title="Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)}">Sebagian</span>${tglLunas ? `<div class="text-[11px] text-slate-500 mt-1 whitespace-nowrap"><i class="ti ti-calendar-check text-emerald-500"></i> ${tglLunas}</div>` : ''}`
      : '<span class="badge badge-warning">Belum Lunas</span>';
    const rowBgClass = isLunas
      ? 'bg-emerald-50/75 dark:bg-emerald-950/30 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/40'
      : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

    const rowNum = (curPage - 1) * curPerPage + idx + 1;
    const isUp = r.no_pd && r.no_pd.includes('UP');
    const nomorPdHtml = r.no_pd
      ? (isUp
          ? `<span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300/80">${escapeHtml(r.no_pd)}</span>`
          : `<span class="text-xs font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(r.no_pd)}</span>`)
      : '<span class="text-xs text-slate-400">-</span>';

    const rowEncoded = encodeURIComponent(JSON.stringify(r));
    return `<tr class="${rowBgClass} cursor-pointer transition-colors" onclick="showDtModalPub('${rowEncoded}')">
      <td class="text-center text-xs text-slate-500">${rowNum}</td>
      <td class="text-center font-medium">${nomorPdHtml}</td>
      <td class="font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap">${escapeHtml(r.nama_peserta)}</td>
      <td class="min-w-[180px] max-w-[300px] break-words whitespace-normal" title="${escapeHtml(r.maksud)}">${escapeHtml(r.maksud)}</td>
      <td class="text-xs">
        <div class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.no_surat_tugas || '-')}</div>
        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">MAK: ${escapeHtml(r.kode_mak || '-')}</div>
        <div class="text-[11px] text-slate-400">${tgl}</div>
      </td>
      <td class="text-right text-currency">${fmtRp(r.uang_harian)}</td>
      <td class="text-right text-currency">${fmtRp(r.total_spj)}</td>
      <td class="text-right text-currency font-semibold text-emerald-600">${fmtRp(r.dana_taktis)}</td>
      <td class="text-center whitespace-nowrap">${statusBadge}</td>
    </tr>`;
  }).join('');
}

/* ── Modal Detail Perjalanan Dinas (Public) ── */
function showPerjadinModalPub(rowOrEncoded) {
  const r = typeof rowOrEncoded === 'string' ? JSON.parse(decodeURIComponent(rowOrEncoded)) : rowOrEncoded;
  const isUp = r.no_pd && r.no_pd.includes('UP');
  const noPdBadge = r.no_pd 
    ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${isUp ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}">No. PD: ${escapeHtml(r.no_pd)}</span>`
    : '';
  
  document.getElementById('perjadin-pub-subtitle').innerHTML = `${noPdBadge} <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.nama_peserta || '')}</span>`;

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
        ? `<span class="badge badge-info">Sebagian (Disetor ${fmtRp(r.jumlah_disetor)} dari ${fmtRp(r.dana_taktis)})</span>`
        : '<span class="badge badge-warning">Belum Lunas</span>'));

  const tglST = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';

  // Tiket pesawat details
  let tiketHtml = '<span class="text-slate-400 italic">Tidak ada tiket pesawat</span>';
  if (r.tiket && r.tiket.length > 0) {
    tiketHtml = `<div class="space-y-2 mt-1">` + r.tiket.map(t => `
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs">
        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-200">
          <span>${escapeHtml(t.maskapai || 'Pesawat')} &middot; ${escapeHtml(t.nomor_penerbangan || '-')} (${escapeHtml(t.tipe || '-')})</span>
          <span class="text-emerald-600 font-semibold">${fmtRp(t.harga_tiket)}</span>
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
          <span class="text-emerald-600 font-semibold">${fmtRp(totalH)}</span>
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

  document.getElementById('perjadin-pub-body').innerHTML = `
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
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.uang_harian)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullboard</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.meeting_fullboard)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Meeting Fullday</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.meeting_fullday)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Uang Representasi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.uang_representasi)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">Transport Lokal / Taksi</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.transport_lokal)}</div>
        </div>
        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
          <div class="text-slate-500">BBM (Jalan Darat)</div>
          <div class="font-semibold text-slate-800 dark:text-slate-100 mt-0.5">${fmtRp(r.bbm)}</div>
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
        <div class="text-lg font-bold text-primary-900 dark:text-primary-100">${fmtRp(r.total_spj)}</div>
      </div>
      <div class="text-right">
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dana Taktis (10%)</div>
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${fmtRp(r.dana_taktis)}</div>
        <div class="mt-1">${statusBadge}</div>
      </div>
    </div>
  `;

  document.getElementById('modal-detail-perjadin-pub').classList.remove('hidden');
}
function closePerjadinModalPub() { document.getElementById('modal-detail-perjadin-pub').classList.add('hidden'); }
window.showPerjadinModalPub = showPerjadinModalPub;
window.closePerjadinModalPub = closePerjadinModalPub;

/* ── Modal Detail Dana Taktis (Public) ── */
function showDtModalPub(rowOrEncoded) {
  const r = typeof rowOrEncoded === 'string' ? JSON.parse(decodeURIComponent(rowOrEncoded)) : rowOrEncoded;
  const isUp = r.no_pd && r.no_pd.includes('UP');
  const noPdBadge = r.no_pd 
    ? `<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${isUp ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}">No. PD: ${escapeHtml(r.no_pd)}</span>`
    : '';

  document.getElementById('dt-pub-subtitle').innerHTML = `${noPdBadge} <span class="font-medium text-slate-700 dark:text-slate-200">${escapeHtml(r.nama_peserta || '')}</span>`;

  const taktisVal = parseFloat(r.dana_taktis) || 0;
  const isZeroTaktis = taktisVal <= 0;
  const isLunas = r.status_lunas === 'lunas' || isZeroTaktis;
  const isSebagian = !isZeroTaktis && r.status_lunas === 'sebagian';
  const tglLunas = r.tanggal_lunas ? new Date(r.tanggal_lunas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
  const statusLabel = isZeroTaktis ? 'Lunas (Tanpa Taktis)' : (isLunas ? 'Lunas' : isSebagian ? 'Sebagian' : 'Belum Lunas');
  const badgeClass = isLunas ? 'badge-success' : isSebagian ? 'badge-info' : 'badge-warning';

  const items = [
    ['Nama Pelaksana Perjalanan Dinas', escapeHtml(r.nama_peserta || '-')],
    ['Maksud Perjalanan Dinas', escapeHtml(r.maksud || '-')],
    ['Kode MAK', `<span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(r.kode_mak || '-')}</span>`],
    ['No. Surat Tugas', escapeHtml(r.no_surat_tugas || '-')],
    ['No. SPM', escapeHtml(r.no_spm || '-')],
    ['Tgl. Surat Tugas', r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'],
    ['Uang Harian', fmtRp(r.uang_harian)],
    ['Total SPJ Yang Dibayarkan', fmtRp(r.total_spj)],
    ['Dana Taktis (10%)', `<span class="font-semibold text-emerald-600">${fmtRp(r.dana_taktis)}</span>`],
    ['Status Setoran', `<span class="badge ${badgeClass}">${statusLabel}</span>`],
  ];

  if (isSebagian) {
    items.push(['Sudah Disetor', fmtRp(r.jumlah_disetor)]);
    items.push(['Sisa Kurang', fmtRp(taktisVal - (parseFloat(r.jumlah_disetor) || 0))]);
  }
  if (r.tanggal_lunas) {
    items.push(['Tanggal Setor / Lunas', tglLunas]);
  }

  document.getElementById('dt-pub-body').innerHTML = `
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
      ${items.map(([k, v]) => `<div><dt class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">${k}</dt><dd class="text-sm text-slate-800 dark:text-slate-100 mt-0.5">${v}</dd></div>`).join('')}
    </dl>
  `;

  document.getElementById('modal-detail-dt-pub').classList.remove('hidden');
}
function closeDtModalPub() { document.getElementById('modal-detail-dt-pub').classList.add('hidden'); }
window.showDtModalPub = showDtModalPub;
window.closeDtModalPub = closeDtModalPub;

/* ── Animasi angka KPI "count up" saat dimuat — bikin dashboard kerasa lebih hidup ── */
function animateCountUp(el, duration = 900) {
  const raw = el.textContent;
  const match = raw.match(/[\d.,]+/);
  if (!match) return;
  const target = parseFloat(match[0].replace(/\./g, '').replace(',', '.'));
  if (!isFinite(target)) return;
  const prefix = raw.slice(0, match.index);
  const suffix = raw.slice(match.index + match[0].length);
  const start = performance.now();
  const ease = t => 1 - Math.pow(1 - t, 3);
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const val = Math.round(target * ease(p));
    el.textContent = prefix + val.toLocaleString('id-ID') + suffix;
    if (p < 1) requestAnimationFrame(tick);
    else el.textContent = raw;
  }
  requestAnimationFrame(tick);
}
window.animateCountUp = animateCountUp;
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.kpi-value').forEach(el => animateCountUp(el));
});

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  buildKat();
  loadTren(<?= date('Y') ?>);
  refreshTxn();
  if (location.hash === '#perjadin') aktifkanTab('perjadin', true);
  else if (location.hash === '#dana-taktis') aktifkanTab('dana-taktis', true);
});