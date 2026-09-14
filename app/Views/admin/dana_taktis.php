<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Dana Taktis</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rekap setoran Dana Taktis (10% Uang Harian) per pegawai</p>
  </div>
  <a href="<?= base_url('admin/perjalanan-dinas') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i> <span class="hidden sm:inline">Perjalanan Dinas</span>
  </a>
</div>

<div class="card mb-4">
  <div class="card-header flex items-center justify-between">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
      Rekap Belum Dibayar <span class="text-slate-400 font-normal">(semua pegawai)</span>
    </h3>
    <span class="badge badge-warning">Total: <?= 'Rp ' . number_format($totalBelumDibayar, 0, ',', '.') ?></span>
  </div>
  <?php if (empty($belumDibayar)): ?>
  <div class="card-body text-center py-8 text-slate-500">
    <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto mb-2 text-emerald-500 opacity-70"></i>
    Semua setoran Dana Taktis sudah lunas.
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table">
      <thead>
        <tr>
          <th>Nama Pegawai</th>
          <th>Perjalanan Dinas</th>
          <th>No. Surat Tugas / Tgl</th>
          <th class="text-right">Dana Taktis</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($belumDibayar as $r): ?>
        <tr>
          <td class="font-medium text-slate-700 dark:text-slate-200"><?= esc($r['nama_peserta']) ?></td>
          <td class="max-w-[280px] truncate" title="<?= esc($r['maksud']) ?>"><?= esc($r['maksud']) ?></td>
          <td class="whitespace-nowrap text-xs"><?= esc($r['no_surat_tugas'] ?: '-') ?><br><?= date('d M Y', strtotime($r['tanggal_surat_tugas'])) ?></td>
          <td class="text-right text-currency font-semibold text-amber-600">Rp <?= number_format($r['dana_taktis'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body py-3">
    <a href="<?= base_url('admin/perjalanan-dinas') ?>" class="text-sm text-primary-600 hover:underline">
      <i data-lucide="arrow-right" class="w-3.5 h-3.5 inline"></i> Buka halaman Perjalanan Dinas untuk menandai lunas
    </a>
  </div>
  <?php endif; ?>
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';
const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(n) || 0));

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
