<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-8 lg:py-10">

  <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Rekap Perjalanan Dinas</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rincian SPJ &amp; setoran Dana Taktis (10% Uang Harian) per perjalanan dinas</p>
    </div>
    <a href="<?= base_url('perjalanan-dinas/dana-taktis') ?>" class="btn btn-outline btn-sm">
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

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';
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
</script>
<?= $this->endSection() ?>
