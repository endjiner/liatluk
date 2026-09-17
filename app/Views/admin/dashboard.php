<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$tahunSekarang = (int)date('Y');
$bulanSekarang = (int)date('m');
$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tahunOpsi = range($tahunSekarang, $tahunSekarang - 5);
?>

<!-- Header -->
<div class="mb-6 flex items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Dashboard</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan pengelolaan keuangan internal</p>
  </div>
  <button onclick="openMainDrawer()" class="btn btn-primary">
    <?= iconsax('add', '') ?> <span class="hidden sm:inline">Input Baru</span>
  </button>
</div>

<!-- KPI Cards: 4 kartu sejajar (2 kolom di mobile, 4 kolom di desktop) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 mb-6">

  <!-- Saldo Akhir -->
  <div class="kpi p-3 sm:p-5">
    <div class="kpi-label">
      <img src="<?= icons8('wallet') ?>" alt="" class="w-5 h-5"> <span class="truncate">Saldo Akhir</span>
    </div>
    <div class="kpi-value text-lg sm:text-2xl text-primary-700 dark:text-primary-300 text-currency">
      Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
    </div>
    <div class="text-xs text-slate-500 dark:text-slate-400">
      Total pemasukan − pengeluaran keseluruhan
    </div>
  </div>

  <!-- Dana Taktis Belum Dibayar -->
  <div class="kpi p-3 sm:p-5 bg-amber-50/70 dark:bg-amber-900/20">
    <div class="kpi-label">
      <?= iconsax('moneys', 'w-5 h-5 text-amber-600') ?> <span class="truncate">Dana Taktis Belum Dibayar</span>
    </div>
    <div class="kpi-value text-lg sm:text-2xl text-amber-700 dark:text-amber-400 text-currency">
      Rp <?= number_format($danaTaktisBelumDisetor['total'], 0, ',', '.') ?>
    </div>
    <div class="text-xs text-slate-500 dark:text-slate-400">
      <?= $danaTaktisBelumDisetor['jumlah'] ?> peserta belum menyetor &middot;
      <a href="#dana-taktis" onclick="scrollKeDanaTaktis(event)" class="text-amber-600 hover:underline cursor-pointer">Lihat detail</a>
    </div>
  </div>

  <!-- Total Pemasukan dengan period picker -->
  <div class="kpi p-3 sm:p-5" data-kpi="pemasukan">
    <div class="flex items-start justify-between gap-2">
      <div class="kpi-label">
        <img src="<?= icons8('bullish') ?>" alt="" class="w-5 h-5"> <span class="truncate">Total Pemasukan</span>
      </div>
    </div>
    <div class="kpi-value text-lg sm:text-2xl text-emerald-700 dark:text-emerald-400 text-currency" id="kpi-pemasukan-value">
      Rp <?= number_format($totalPemasukan, 0, ',', '.') ?>
    </div>
    <?= view('admin/_partials/kpi_period_picker', ['id' => 'pemasukan']) ?>
  </div>

  <!-- Total Pengeluaran dengan period picker -->
  <div class="kpi p-3 sm:p-5" data-kpi="pengeluaran">
    <div class="flex items-start justify-between gap-2">
      <div class="kpi-label">
        <img src="<?= icons8('bearish') ?>" alt="" class="w-5 h-5"> <span class="truncate">Total Pengeluaran</span>
      </div>
    </div>
    <div class="kpi-value text-lg sm:text-2xl text-red-700 dark:text-red-400 text-currency" id="kpi-pengeluaran-value">
      Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?>
    </div>
    <?= view('admin/_partials/kpi_period_picker', ['id' => 'pengeluaran']) ?>
  </div>

</div>

<!-- Kartu Tambahan (dinamis, hanya muncul kalau nilainya > 0; style disamakan dengan KPI di atas) -->
<?php
  $extraCards = [];
  if ($danaBelumDiterima > 0) {
    $extraCards[] = ['icon' => 'high-priority', 'color' => 'amber', 'label' => 'Dana Belum Diterima', 'value' => $danaBelumDiterima, 'caption' => 'Total pemasukan yang belum masuk ke kas'];
  }
  if ($totalRencanaPemasukan > 0) {
    $extraCards[] = ['icon' => 'budget', 'color' => 'emerald', 'label' => 'Rencana Pemasukan', 'value' => $totalRencanaPemasukan, 'caption' => 'Total rencana pemasukan aktif'];
  }
  if ($totalRencanaPengeluaran > 0) {
    $extraCards[] = ['icon' => 'budget', 'color' => 'primary', 'label' => 'Rencana Pengeluaran', 'value' => $totalRencanaPengeluaran, 'caption' => 'Total rencana pengeluaran aktif'];
  }
?>
<?php if (!empty($extraCards)): ?>
<div class="grid grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3 mb-6">
  <?php foreach ($extraCards as $c): ?>
  <div class="kpi p-3 sm:p-5">
    <div class="kpi-label">
      <img src="<?= icons8($c['icon']) ?>" alt="" class="w-5 h-5"> <span class="truncate"><?= $c['label'] ?></span>
    </div>
    <div class="kpi-value text-lg sm:text-2xl text-<?= $c['color'] ?>-700 dark:text-<?= $c['color'] ?>-400 text-currency">
      Rp <?= number_format($c['value'], 0, ',', '.') ?>
    </div>
    <div class="text-xs text-slate-500 dark:text-slate-400">
      <?= $c['caption'] ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 items-start">
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Arus Kas Bulanan</h3>
      <select id="arus-kas-tahun" class="form-control form-control-sm w-24">
        <?php foreach ($tahunOpsi as $t): ?>
        <option value="<?= $t ?>" <?= $t === $tahunSekarang ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="card-body">
      <canvas id="chart-arus-kas" class="chart-canvas"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kategori Pengeluaran</h3>
    </div>
    <div class="card-body">
      <div id="chart-kategori-wrap" class="flex flex-col items-center">
        <canvas id="chart-kategori" style="max-height:260px"></canvas>
      </div>
      <div id="chart-kategori-empty" class="hidden flex-col items-center justify-center text-center text-slate-400 dark:text-slate-500 py-10">
        <img src="<?= icons8('pie-chart', '3d-fluency', 96) ?>" alt="" class="w-12 h-12 mb-2 opacity-80">
        <p class="text-sm">Belum ada data pengeluaran.</p>
      </div>
    </div>
  </div>
</div>

<!-- Rencana ringkas -->
<?php if (!empty($rencanaAktif)): ?>
<div class="card mb-6">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('calendar-tick', 'w-4 h-4 text-primary-600') ?> Rencana Keuangan Aktif
    </h3>
    <a href="<?= base_url('admin/rencana') ?>" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Lihat semua</a>
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

<!-- Tab: Transaksi Keuangan / Perjalanan Dinas / Dana Taktis -->
<div id="segment-tabs" class="segment w-full scroll-mt-24">
  <button type="button" id="tab-btn-transaksi" class="segment-btn active flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('transaksi')">
    <?= iconsax('receipt-item', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Transaksi</span>
  </button>
  <button type="button" id="tab-btn-perjadin" class="segment-btn flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('perjadin')">
    <?= iconsax('airplane', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Perjalanan Dinas</span>
  </button>
  <button type="button" id="tab-btn-dana-taktis" class="segment-btn flex-1 flex items-center justify-center gap-1.5" onclick="aktifkanTab('dana-taktis')">
    <?= iconsax('moneys', 'w-3.5 h-3.5 shrink-0') ?> <span class="truncate">Dana Taktis</span>
  </button>
</div>

<div id="panel-transaksi" class="mt-4">
  <?= $this->include('admin/_partials/tab_transaksi') ?>
</div>

<div id="panel-perjadin" class="mt-4 hidden">
  <?= $this->include('admin/_partials/tab_perjadin') ?>
</div>

<div id="panel-dana-taktis" class="mt-4 hidden">
  <?= $this->include('admin/_partials/tab_dana_taktis') ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const ADMIN_BASE_URL = '<?= base_url() ?>';
const ADMIN_CHART_TREN_URL = '<?= base_url('admin/dashboard/chart-tren') ?>';

let chartArus, chartKat;
let lastArusP = [], lastArusE = [];

function chartColors() {
  const dark = document.documentElement.classList.contains('dark');
  return {
    text: dark ? '#cbd5e1' : '#475569',
    grid: dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.2)',
  };
}

function buildArus(pemasukan, pengeluaran) {
  const colors = chartColors();
  if (chartArus) chartArus.destroy();
  chartArus = new Chart(document.getElementById('chart-arus-kas'), {
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
  const labels = data.map(d => d.kategori);
  const values = data.map(d => d.total);
  if (chartKat) chartKat.destroy();
  chartKat = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: ['#1e5fbe', '#059669', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#65a30d', '#c026d3']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 11 }, padding: 12, boxWidth: 14 } } }
    }
  });
}

async function loadArus(tahun) {
  const res = await fetch(ADMIN_CHART_TREN_URL + '?tahun=' + tahun);
  const data = await res.json();
  lastArusP = data.pemasukan || [];
  lastArusE = data.pengeluaran || [];
  buildArus(lastArusP, lastArusE);
}

document.getElementById('arus-kas-tahun').addEventListener('change', e => loadArus(e.target.value));

function updateChartColors() { buildKat(); buildArus(lastArusP, lastArusE); }
window.updateChartColors = updateChartColors;

document.addEventListener('DOMContentLoaded', () => {
  loadArus(<?= $tahunSekarang ?>);
  buildKat();
});

/* ── KPI Period Picker (advanced: pick specific year/month) ── */
(function() {
  const KPI_ENDPOINT = '<?= base_url('admin/dashboard/kpi-periode') ?>';

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
        // Show/hide year & month selectors
        if (yearSel) yearSel.parentElement.classList.toggle('hidden', scope === 'semua');
        if (monthSel) monthSel.parentElement.classList.toggle('hidden', scope !== 'bulan');
        await refreshKpi(kpiId, valueEl, scope, yearSel?.value, monthSel?.value);
      });
    });
    if (yearSel) yearSel.addEventListener('change', () => reloadCurrent(card, kpiId, valueEl));
    if (monthSel) monthSel.addEventListener('change', () => reloadCurrent(card, kpiId, valueEl));
  });

  function reloadCurrent(card, kpiId, valueEl) {
    const activeTab = card.querySelector('.kpi-tab.active');
    const scope = activeTab?.dataset.scope || 'semua';
    const y = card.querySelector('.kpi-year')?.value;
    const m = card.querySelector('.kpi-month')?.value;
    refreshKpi(kpiId, valueEl, scope, y, m);
  }

  async function refreshKpi(kpiId, valueEl, scope, tahun, bulan) {
    valueEl.style.opacity = '0.5';
    try {
      const url = KPI_ENDPOINT + '?scope=' + scope + '&tahun=' + (tahun || new Date().getFullYear()) + '&bulan=' + (bulan || (new Date().getMonth() + 1));
      const res = await fetch(url);
      const data = await res.json();
      if (data.success) {
        const total = kpiId === 'pemasukan' ? data.total_pemasukan : data.total_pengeluaran;
        valueEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
      }
    } finally {
      valueEl.style.opacity = '1';
    }
  }
})();

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
    muatDaftarTrip();
  }
  if (nama === 'dana-taktis' && !danaTaktisSudahDimuat) {
    danaTaktisSudahDimuat = true;
    muatDaftarDanaTaktis();
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

document.addEventListener('DOMContentLoaded', () => {
  if (location.hash === '#perjadin') aktifkanTab('perjadin', true);
  else if (location.hash === '#dana-taktis') aktifkanTab('dana-taktis', true);
});
</script>
<?= $this->endSection() ?>
