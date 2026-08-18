<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Semua Notifikasi</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Riwayat notifikasi sistem</p>
  </div>
  <button onclick="markAllReadPage()" class="btn btn-outline btn-sm">
    <i data-lucide="check-check"></i> <span class="hidden sm:inline">Tandai Semua Dibaca</span>
  </button>
</div>

<!-- Filter -->
<form id="filter-form" method="get" class="card p-3 mb-4">
  <div class="flex flex-wrap items-center gap-2">
    <div class="segment">
      <button type="button" class="segment-btn <?= $periode === 'tahun' ? 'active' : '' ?>" onclick="setPeriode('tahun')">Per Tahun</button>
      <button type="button" class="segment-btn <?= $periode === 'semester' ? 'active' : '' ?>" onclick="setPeriode('semester')">6 Bulan</button>
      <button type="button" class="segment-btn <?= $periode === 'bulan' ? 'active' : '' ?>" onclick="setPeriode('bulan')">Per Bulan</button>
    </div>
    <input type="hidden" name="periode" id="periode-input" value="<?= esc($periode) ?>">

    <select name="tahun" class="form-control form-control-sm w-auto <?= $periode === 'semester' ? 'hidden' : '' ?>" onchange="document.getElementById('filter-form').submit()">
      <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
      <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>

    <select name="bulan" class="form-control form-control-sm w-auto <?= $periode !== 'bulan' ? 'hidden' : '' ?>" onchange="document.getElementById('filter-form').submit()">
      <?php for ($m = 1; $m <= 12; $m++): ?>
      <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
      <?php endfor; ?>
    </select>

    <select name="jenis" class="form-control form-control-sm w-auto" onchange="document.getElementById('filter-form').submit()">
      <option value="semua" <?= $jenis === 'semua' ? 'selected' : '' ?>>Semua Jenis</option>
      <option value="pemasukan" <?= $jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
      <option value="pengeluaran" <?= $jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
      <option value="rencana" <?= $jenis === 'rencana' ? 'selected' : '' ?>>Pengingat Rencana</option>
      <option value="sistem" <?= $jenis === 'sistem' ? 'selected' : '' ?>>Sistem</option>
    </select>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <i data-lucide="bell" class="w-4 h-4"></i> Daftar Notifikasi
    </h3>
    <span class="text-xs text-slate-500"><?= $total ?> notifikasi</span>
  </div>
  <div>
    <?php if (empty($notifikasi)): ?>
    <div class="text-center py-12 text-slate-500">
      <i data-lucide="bell-off" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
      Tidak ada notifikasi pada periode &amp; jenis yang dipilih
    </div>
    <?php else:
      $kategoriIcon  = ['pemasukan' => 'arrow-down-left', 'pengeluaran' => 'arrow-up-right', 'rencana' => 'calendar-clock', 'sistem' => 'settings'];
      $kategoriBadge = ['pemasukan' => 'badge-success', 'pengeluaran' => 'badge-danger', 'rencana' => 'badge-warning', 'sistem' => 'badge-muted'];
      $kategoriLabel = ['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran', 'rencana' => 'Rencana', 'sistem' => 'Sistem'];
    ?>
    <?php foreach ($notifikasi as $n): ?>
    <div id="notif-row-<?= $n['id'] ?>" onclick="markReadPage(<?= $n['id'] ?>)"
         class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700 last:border-b-0 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 <?= $n['is_read'] == 0 ? 'bg-primary-50/40 dark:bg-primary-900/20' : '' ?>">
      <div class="badge <?= $kategoriBadge[$n['kategori']] ?? 'badge-muted' ?> w-8 h-8 rounded-full justify-center shrink-0 p-0">
        <i data-lucide="<?= $kategoriIcon[$n['kategori']] ?? 'bell' ?>" class="w-4 h-4"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm text-slate-800 dark:text-slate-100"><?= esc($n['pesan']) ?></div>
        <div class="flex items-center gap-2 mt-1 flex-wrap">
          <span class="badge <?= $kategoriBadge[$n['kategori']] ?? 'badge-muted' ?>"><?= $kategoriLabel[$n['kategori']] ?? 'Lainnya' ?></span>
          <span class="text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
        </div>
      </div>
      <?php if ($n['is_read'] == 0): ?>
      <span class="w-2 h-2 rounded-full bg-primary-600 shrink-0 mt-2 notif-dot"></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($totalPage > 1): ?>
  <div class="flex items-center justify-center gap-1 p-3 border-t border-slate-200 dark:border-slate-700">
    <?php
      $qs = $_GET;
      for ($p = 1; $p <= $totalPage; $p++):
        $qs['page'] = $p;
        $url = current_url() . '?' . http_build_query($qs);
    ?>
    <a href="<?= $url ?>" class="px-3 py-1.5 text-xs rounded-md <?= $p == $currentPage ? 'bg-primary-600 text-white font-medium' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';
function setPeriode(p) {
  document.getElementById('periode-input').value = p;
  document.getElementById('filter-form').submit();
}
async function markReadPage(id) {
  const row = document.getElementById('notif-row-' + id);
  if (!row.querySelector('.notif-dot')) return;
  await fetch(BASE + 'admin/notifikasi/read/' + id, { method: 'POST' });
  row.classList.remove('bg-primary-50/40', 'dark:bg-primary-900/20');
  row.querySelector('.notif-dot')?.remove();
}
async function markAllReadPage() {
  await fetch(BASE + 'admin/notifikasi/read-all', { method: 'POST' });
  document.querySelectorAll('[id^="notif-row-"]').forEach(el => {
    el.classList.remove('bg-primary-50/40', 'dark:bg-primary-900/20');
    el.querySelector('.notif-dot')?.remove();
  });
  showToast('Semua notifikasi ditandai dibaca', 'success');
}
</script>
<?= $this->endSection() ?>
