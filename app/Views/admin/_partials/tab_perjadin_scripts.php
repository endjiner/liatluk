<script>
(function() {
const BASE = (function() {
  const cfg = '<?= rtrim(base_url(), '/') ?>/';
  return (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && cfg.includes('localhost'))
    ? (window.location.origin + '/')
    : cfg;
})();
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

// ── Export Perjalanan Dinas (Excel / PDF) ──────────────────────────────────
function exportPerjadin(format) {

  const tahun  = document.getElementById('filter-tahun-perjadin')?.value || '';
  const bulan  = document.getElementById('filter-bulan-perjadin')?.value || '';
  const status = document.getElementById('filter-status-perjadin')?.value || '';
  const search = document.getElementById('filter-search-perjadin')?.value || '';

  const params = new URLSearchParams();
  params.set('tipe', 'perjadin');
  if (tahun)  params.set('tahun', tahun);
  if (bulan)  params.set('bulan', bulan);
  if (status) params.set('status', status);
  if (search) params.set('search', search);

  const endpoint = format === 'pdf' ? 'admin/laporan/export-pdf' : 'admin/laporan/export-excel';
  const url = BASE + endpoint + '?' + params.toString();

  if (format === 'pdf') {
    window.open(url, '_blank');
  } else {
    window.location.href = url;
  }
}
window.exportPerjadin = exportPerjadin;

})();
</script>
