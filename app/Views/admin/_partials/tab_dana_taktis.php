<div class="card mb-4">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
      Daftar Setoran Dana Taktis <span class="text-slate-400 font-normal">(semua pegawai)</span>
    </h3>
    <span class="text-xs text-slate-500">Total: <span id="dt-total">-</span></span>
  </div>

  <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[160px] max-w-xs">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
          <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
        </span>
        <input type="text" id="dt-filter-search" oninput="dtJadwalkanMuat()" placeholder="Cari nama / maksud perjalanan..." class="form-control form-control-sm pl-8">
      </div>
      <select id="dt-filter-status" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Status</option>
        <option value="belum">Belum Lunas</option>
        <option value="lunas">Lunas</option>
      </select>
      <select id="dt-filter-bulan" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Bulan</option>
        <?php $namaBulanDt = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>
        <?php for ($b = 1; $b <= 12; $b++): ?>
        <option value="<?= $b ?>"><?= $namaBulanDt[$b] ?></option>
        <?php endfor; ?>
      </select>
      <select id="dt-filter-tahun" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
        <option value="">Semua Tahun</option>
        <?php $tahunSekarangDt = (int)date('Y'); foreach (range($tahunSekarangDt, $tahunSekarangDt - 5) as $th): ?>
        <option value="<?= $th ?>"><?= $th ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex items-center gap-1.5 pl-3 border-l border-slate-200 dark:border-slate-700">
        <span class="text-xs text-slate-600 dark:text-slate-400">Tampilkan</span>
        <select id="dt-filter-perpage" onchange="muatDaftarDanaTaktis(1)" class="form-control form-control-sm w-auto">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-xs text-slate-600 dark:text-slate-400">baris</span>
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
          <th>Nama Pegawai</th>
          <th>Perjalanan Dinas</th>
          <th>No. Surat Tugas / Tgl</th>
          <th class="text-right">Dana Taktis</th>
          <th>Status</th>
          <th class="w-24 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody id="dt-tbody">
        <tr><td colspan="6" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>

  <div id="dt-pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
</div>

<!-- Modal Tandai Lunas (dipakai tombol Aksi di tabel atas) -->
<div id="modal-lunas-dt" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-lunas-dt')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('tick-circle', 'w-5 h-5 text-emerald-600') ?> Kelola Setoran Dana Taktis</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-lunas-dt')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="dt-lunas-peserta-id">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Dana Taktis: <span class="font-semibold text-slate-800 dark:text-slate-100" id="dt-lunas-dana-taktis-info">Rp 0</span>. Setoran akan otomatis tercatat sebagai Pemasukan (kategori "Setoran Taktis Pegawai").</p>
        <div class="segment w-full mb-3">
          <button type="button" data-status="sebagian" class="segment-btn dt-lunas-status-btn flex-1" onclick="dtPilihStatusLunas('sebagian')">Bayar Sebagian</button>
          <button type="button" data-status="lunas" class="segment-btn dt-lunas-status-btn active flex-1" onclick="dtPilihStatusLunas('lunas')">Lunas</button>
        </div>
        <div id="dt-lunas-jumlah-wrap" class="hidden mb-3">
          <label class="form-label">Jumlah Disetor</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
            <input type="text" inputmode="numeric" id="dt-lunas-jumlah-disetor" class="form-control input-rupiah pl-9" placeholder="0">
          </div>
        </div>
        <label class="form-label">Tanggal Setoran</label>
        <input type="date" id="dt-lunas-tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-lunas-dt')">Batal</button>
        <button class="btn btn-success" onclick="dtKonfirmasiLunas()"><?= iconsax('check', '') ?> Simpan Setoran</button>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body py-4">
    <label class="form-label">Pilih Nama Pegawai</label>
    <select id="pegawai-select" class="form-control max-w-md" onchange="muatRekap()">
      <option value="">Pilih pegawai...</option>
      <?php foreach ($pegawaiList as $pg): ?>
      <option value="<?= $pg['id'] ?>"><?= esc($pg['nama']) ?><?= $pg['nip'] ? ' — ' . esc($pg['nip']) : '' ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div id="rekap-wrap" class="hidden">
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
    <div class="card"><div class="card-body">
      <p class="text-xs text-slate-500 dark:text-slate-400">Total Uang Harian</p>
      <p class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1" id="rekap-uang-harian">Rp 0</p>
    </div></div>
    <div class="card"><div class="card-body">
      <p class="text-xs text-slate-500 dark:text-slate-400">Total SPJ</p>
      <p class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1" id="rekap-total-spj">Rp 0</p>
    </div></div>
    <div class="card"><div class="card-body">
      <p class="text-xs text-slate-500 dark:text-slate-400">Total Dana Taktis</p>
      <p class="text-lg font-bold text-emerald-600 mt-1" id="rekap-dana-taktis">Rp 0</p>
    </div></div>
    <div class="card"><div class="card-body">
      <p class="text-xs text-slate-500 dark:text-slate-400">Dana Taktis Belum Dibayar</p>
      <p class="text-lg font-bold text-amber-600 mt-1" id="rekap-belum-dibayar">Rp 0</p>
    </div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Rincian per Perjalanan Dinas</h3>
    </div>
    <div class="sm:hidden flex items-center gap-1.5 px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">
    <?= iconsax('arrow-swap-horizontal', 'w-3 h-3') ?> Geser tabel untuk melihat kolom lainnya
  </div>
  <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>Maksud Perjalanan Dinas</th>
            <th>No. Surat Tugas / Tgl</th>
            <th>Kode MAK</th>
            <th class="text-right">Uang Harian</th>
            <th class="text-right">Total SPJ</th>
            <th class="text-right">Dana Taktis</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="rekap-tbody"></tbody>
      </table>
    </div>
  </div>
</div>

<div id="rekap-empty" class="card"><div class="card-body text-center py-12 text-slate-500">
  <img src="<?= icons8('wallet', '3d-fluency', 96) ?>" alt="" class="w-14 h-14 mx-auto mb-3 opacity-80">
  Pilih nama pegawai di atas untuk melihat rekap Dana Taktis-nya.
</div></div>

<script>
(function() {
const BASE = '<?= base_url() ?>';
const API_DT = BASE + 'admin/perjalanan-dinas';
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
const escapeHtmlDt = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);

/* ── Daftar Dana Taktis (semua pegawai, dipaginasi & difilter) ── */
let dtFilterDebounce = null;
function dtJadwalkanMuat() {
  clearTimeout(dtFilterDebounce);
  dtFilterDebounce = setTimeout(() => muatDaftarDanaTaktis(1), 400);
}
window.dtJadwalkanMuat = dtJadwalkanMuat;

async function muatDaftarDanaTaktis(page) {
  const params = new URLSearchParams();
  const search = document.getElementById('dt-filter-search').value;
  const status = document.getElementById('dt-filter-status').value;
  const bulan = document.getElementById('dt-filter-bulan').value;
  const tahun = document.getElementById('dt-filter-tahun').value;
  const perPage = document.getElementById('dt-filter-perpage').value;
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (bulan) params.set('bulan', bulan);
  if (tahun) params.set('tahun', tahun);
  params.set('per_page', perPage);
  params.set('page', page || 1);

  const tbody = document.getElementById('dt-tbody');
  try {
    const res = await fetch(API_DT + '/dana-taktis/list?' + params.toString());
    const json = await res.json();
    if (!json.success) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>'; return; }
    renderDanaTaktisTable(json.data);
    renderPaginasiHalaman(document.getElementById('dt-pagination-wrap'), {
      total: json.total, perPage: json.per_page, page: json.page,
      itemLabel: 'baris', onPageChange: muatDaftarDanaTaktis,
    });
    document.getElementById('dt-total').textContent = new Intl.NumberFormat('id-ID').format(json.total);
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
  }
}
window.muatDaftarDanaTaktis = muatDaftarDanaTaktis;

function renderDanaTaktisTable(rows) {
  const tbody = document.getElementById('dt-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map(r => {
    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const statusBadge = r.status_lunas === 'lunas'
      ? '<span class="badge badge-success">Lunas</span>'
      : r.status_lunas === 'sebagian'
      ? `<span class="badge badge-info" title="Disetor ${rupiah(r.jumlah_disetor)} dari ${rupiah(r.dana_taktis)}">Sebagian</span>`
      : '<span class="badge badge-warning">Belum Lunas</span>';
    const aksi = r.status_lunas === 'belum'
      ? `<button type="button" class="btn btn-success btn-sm" onclick="dtBukaModalLunas(${r.id}, ${r.dana_taktis})">${iconsax('check', 'w-3.5 h-3.5')}</button>`
      : `<button type="button" class="block text-xs text-primary-600 hover:underline" onclick="dtBukaModalLunas(${r.id}, ${r.dana_taktis})">${r.status_lunas === 'sebagian' ? 'Lanjutkan' : 'Ubah'}</button>` +
        `<button type="button" class="block text-xs text-slate-400 hover:text-red-600" onclick="dtBatalkanLunas(${r.id})">Batalkan</button>`;
    return `<tr>
      <td class="font-medium text-slate-700 dark:text-slate-200">${escapeHtmlDt(r.nama_peserta)}</td>
      <td class="max-w-[280px] truncate" title="${escapeHtmlDt(r.maksud)}">${escapeHtmlDt(r.maksud)}</td>
      <td class="whitespace-nowrap text-xs">${escapeHtmlDt(r.no_surat_tugas || '-')}<br>${tgl}</td>
      <td class="text-right text-currency font-semibold text-emerald-600">${rupiah(r.dana_taktis)}</td>
      <td>${statusBadge}</td>
      <td class="text-center">${aksi}</td>
    </tr>`;
  }).join('');
}

let dtLunasStatusDipilih = 'lunas';
function dtPilihStatusLunas(status) {
  dtLunasStatusDipilih = status;
  document.querySelectorAll('.dt-lunas-status-btn').forEach(b => b.classList.toggle('active', b.dataset.status === status));
  document.getElementById('dt-lunas-jumlah-wrap').classList.toggle('hidden', status !== 'sebagian');
}
window.dtPilihStatusLunas = dtPilihStatusLunas;

function dtBukaModalLunas(pesertaId, danaTaktis) {
  document.getElementById('dt-lunas-peserta-id').value = pesertaId;
  document.getElementById('dt-lunas-tanggal').value = new Date().toISOString().slice(0, 10);
  document.getElementById('dt-lunas-dana-taktis-info').textContent = rupiah(danaTaktis);
  document.getElementById('dt-lunas-jumlah-disetor').value = '';
  dtPilihStatusLunas('lunas');
  openModal('modal-lunas-dt');
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
  const res = await fetch(API_DT + '/lunas/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-lunas-dt'); muatDaftarDanaTaktis(); }
  else showToast(json.message || 'Gagal', 'error');
}
window.dtKonfirmasiLunas = dtKonfirmasiLunas;

function dtBatalkanLunas(pesertaId) {
  tampilkanKonfirmasi('Batalkan status setoran? Pemasukan otomatis yang sudah tercatat akan ikut dihapus.', async () => {
    const fd = new FormData();
    fd.append('aksi', 'batal');
    const res = await fetch(API_DT + '/lunas/' + pesertaId, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); muatDaftarDanaTaktis(); }
    else showToast(json.message || 'Gagal', 'error');
  });
}
window.dtBatalkanLunas = dtBatalkanLunas;

async function muatRekap() {
  const id = document.getElementById('pegawai-select').value;
  if (!id) {
    document.getElementById('rekap-wrap').classList.add('hidden');
    document.getElementById('rekap-empty').classList.remove('hidden');
    return;
  }
  const res = await fetch(BASE + 'admin/perjalanan-dinas/dana-taktis/data/' + id);
  const json = await res.json();
  if (!json.success) { showToast(json.message || 'Gagal memuat rekap', 'error'); return; }

  document.getElementById('rekap-empty').classList.add('hidden');
  document.getElementById('rekap-wrap').classList.remove('hidden');
  document.getElementById('rekap-uang-harian').textContent = rupiah(json.total_uang_harian);
  document.getElementById('rekap-total-spj').textContent = rupiah(json.total_spj);
  document.getElementById('rekap-dana-taktis').textContent = rupiah(json.total_dana_taktis);
  document.getElementById('rekap-belum-dibayar').textContent = rupiah(json.belum_dibayar);

  const tbody = document.getElementById('rekap-tbody');
  tbody.innerHTML = '';
  if (!json.rows || json.rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Belum pernah ikut perjalanan dinas.</td></tr>';
    return;
  }
  json.rows.forEach(r => {
    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const statusBadge = r.status_lunas === 'lunas'
      ? '<span class="badge badge-success">Lunas</span>'
      : r.status_lunas === 'sebagian'
      ? `<span class="badge badge-info" title="Disetor ${rupiah(r.jumlah_disetor)} dari ${rupiah(r.dana_taktis)}">Sebagian</span>`
      : '<span class="badge badge-warning">Belum Lunas</span>';
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="max-w-[280px] truncate" title="${(r.maksud || '').replace(/"/g, '&quot;')}">${r.maksud || '-'}</td>` +
      `<td class="whitespace-nowrap text-xs">${r.no_surat_tugas || '-'}<br>${tgl}</td>` +
      `<td>${r.kode_mak || '-'}</td>` +
      `<td class="text-right text-currency">${rupiah(r.uang_harian)}</td>` +
      `<td class="text-right text-currency">${rupiah(r.total_spj)}</td>` +
      `<td class="text-right text-currency font-semibold text-emerald-600">${rupiah(r.dana_taktis)}</td>` +
      `<td>${statusBadge}</td>`;
    tbody.appendChild(tr);
  });
}
window.muatRekap = muatRekap;
})();
</script>
