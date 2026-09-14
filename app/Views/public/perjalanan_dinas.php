<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-8 lg:py-10">

  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Rekap Perjalanan Dinas</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rincian SPJ &amp; setoran Dana Taktis (10% Uang Harian) per perjalanan dinas</p>
    </div>
    <a href="#dana-taktis" class="btn btn-outline btn-sm">
      <i data-lucide="piggy-bank"></i> Cek Dana Taktis Saya
    </a>
  </div>

  <form id="form-filter" class="card mb-4" onsubmit="return false">
    <div class="card-body flex flex-wrap items-end gap-3 py-3">
      <div>
        <label class="form-label">Tahun</label>
        <select name="tahun" id="filter-tahun" class="form-control form-control-sm" onchange="muatDaftarTrip()">
          <option value="">Semua Tahun</option>
          <?php $tahunSaatIni = (int)date('Y'); $daftarTahun = $tahunList; if (!in_array($tahunSaatIni, $daftarTahun)) $daftarTahun[] = $tahunSaatIni; rsort($daftarTahun); ?>
          <?php foreach ($daftarTahun as $th): ?>
          <option value="<?= $th ?>" <?= (string)($filters['tahun'] ?? '') === (string)$th ? 'selected' : '' ?>><?= $th ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Bulan</label>
        <select name="bulan" id="filter-bulan" class="form-control form-control-sm" onchange="muatDaftarTrip()">
          <option value="">Semua Bulan</option>
          <?php for ($b = 1; $b <= 12; $b++): ?>
          <option value="<?= $b ?>" <?= (int)($filters['bulan'] ?? 0) === $b ? 'selected' : '' ?>><?= $namaBulan[$b] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="form-label">Cari</label>
        <input type="text" name="search" id="filter-search" value="<?= esc($filters['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="Maksud, no surat tugas, kode MAK..." oninput="jadwalkanMuatDaftar()">
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="muatDaftarTrip()"><i data-lucide="search"></i> Filter</button>
    </div>
  </form>

  <div id="daftar-trip">
<?= view('public/perjalanan_dinas_list', ['trips' => $trips]) ?>
  </div>

  <div id="dana-taktis" class="mt-10 pt-6 border-t border-slate-200 dark:border-slate-700 scroll-mt-20">
    <div class="mb-4">
      <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Cek Dana Taktis Saya</h2>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rekap setoran Dana Taktis (10% Uang Harian) atas nama Anda</p>
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
      <i data-lucide="piggy-bank" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
      Pilih nama pegawai di atas untuk melihat rekap Dana Taktis-nya.
    </div></div>
  </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));
let filterDebounceTimer = null;
function jadwalkanMuatDaftar() {
  clearTimeout(filterDebounceTimer);
  filterDebounceTimer = setTimeout(muatDaftarTrip, 400);
}
async function muatDaftarTrip() {
  const params = new URLSearchParams();
  const tahun = document.getElementById('filter-tahun').value;
  const bulan = document.getElementById('filter-bulan').value;
  const search = document.getElementById('filter-search').value;
  if (tahun) params.set('tahun', tahun);
  if (bulan) params.set('bulan', bulan);
  if (search) params.set('search', search);

  const wrap = document.getElementById('daftar-trip');
  wrap.style.opacity = '0.5';
  try {
    const res = await fetch(BASE + 'perjalanan-dinas/ajax?' + params.toString());
    wrap.innerHTML = await res.text();
    lucide.createIcons();
    history.replaceState(null, '', BASE + 'perjalanan-dinas' + (params.toString() ? '?' + params.toString() : ''));
  } finally {
    wrap.style.opacity = '1';
  }
}

// ── Dana Taktis Saya (bagian bawah halaman) ───────────────────────────────────────
async function muatRekap() {
  const id = document.getElementById('pegawai-select').value;
  if (!id) {
    document.getElementById('rekap-wrap').classList.add('hidden');
    document.getElementById('rekap-empty').classList.remove('hidden');
    return;
  }
  const res = await fetch(BASE + 'perjalanan-dinas/dana-taktis/data/' + id);
  const json = await res.json();
  if (!json.success) { alert(json.message || 'Gagal memuat rekap'); return; }

  document.getElementById('rekap-empty').classList.add('hidden');
  document.getElementById('rekap-wrap').classList.remove('hidden');
  document.getElementById('rekap-uang-harian').textContent = rupiah(json.total_uang_harian);
  document.getElementById('rekap-total-spj').textContent = rupiah(json.total_spj);
  document.getElementById('rekap-dana-taktis').textContent = rupiah(json.total_dana_taktis);
  document.getElementById('rekap-belum-dibayar').textContent = rupiah(json.belum_dibayar);

  const tbody = document.getElementById('rekap-tbody');
  tbody.innerHTML = '';
  if (!json.rows || json.rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500">Belum pernah ikut perjalanan dinas.</td></tr>';
    return;
  }
  json.rows.forEach(r => {
    const tgl = r.tanggal_surat_tugas ? new Date(r.tanggal_surat_tugas).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
    const statusBadge = r.status_lunas === 'lunas'
      ? '<span class="badge badge-success">Lunas</span>'
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
</script>
<?= $this->endSection() ?>
