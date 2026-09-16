<?php
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahunSekarang = (int)date('Y');
$tahunOpsi = range($tahunSekarang, $tahunSekarang - 5);
?>

<div class="mb-3 flex items-center justify-end gap-2">
  <button onclick="openModal('modal-import')" class="btn btn-outline btn-sm">
    <?= iconsax('document-upload', '') ?> <span class="hidden sm:inline">Import CSV</span>
  </button>
  <button onclick="openExportMenu()" class="btn btn-outline btn-sm">
    <?= iconsax('document-download', '') ?> <span class="hidden sm:inline">Export</span>
  </button>
</div>

<!-- Bulk action bar -->
<div id="bulk-action-bar" class="hidden mb-3 flex items-center justify-between gap-3 px-4 py-2.5 rounded-lg bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800">
  <div class="text-sm text-primary-800 dark:text-primary-200">
    <span id="bulk-count">0</span> baris terpilih
  </div>
  <div class="flex items-center gap-2">
    <button type="button" onclick="clearAllSelection()" class="btn btn-ghost btn-sm">Batal</button>
    <button type="button" onclick="openModal('modal-bulk-hapus')" class="btn btn-danger btn-sm">
      <?= iconsax('trash', '') ?> Hapus Terpilih
    </button>
  </div>
</div>

<!-- Tabel Gabungan -->
<div class="card">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('task-square', 'w-4 h-4') ?> Daftar Transaksi
    </h3>
    <span class="text-xs text-slate-500">Total: <span id="txn-total">-</span></span>
  </div>

  <!-- Filter compact -->
  <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
    <div class="flex flex-wrap items-center gap-2">
      <!-- Tipe checkbox -->
      <div class="flex items-center gap-3 pr-3 border-r border-slate-200 dark:border-slate-700">
        <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
          <input type="checkbox" id="filter-pemasukan" checked class="form-checkbox text-emerald-600">
          <span class="text-slate-700 dark:text-slate-300">Pemasukan</span>
        </label>
        <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
          <input type="checkbox" id="filter-pengeluaran" checked class="form-checkbox text-red-600">
          <span class="text-slate-700 dark:text-slate-300">Pengeluaran</span>
        </label>
      </div>

      <!-- Search -->
      <div class="relative flex-1 min-w-[160px] max-w-xs">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
          <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
        </span>
        <input type="text" id="filter-search" placeholder="Cari kategori/keterangan..."
               class="form-control form-control-sm pl-8">
      </div>

      <!-- Bulan -->
      <select id="filter-bulan" class="form-control form-control-sm w-auto">
        <option value="">Semua Bulan</option>
        <?php foreach ($namaBulan as $n => $nm): ?>
        <option value="<?= $n ?>"><?= $nm ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Tahun -->
      <select id="filter-tahun" class="form-control form-control-sm w-auto">
        <option value="">Semua Tahun</option>
        <?php foreach ($tahunOpsi as $t): ?>
        <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Per page -->
      <div class="flex items-center gap-1.5 pl-3 border-l border-slate-200 dark:border-slate-700">
        <span class="text-xs text-slate-600 dark:text-slate-400">Tampilkan</span>
        <select id="filter-perpage" class="form-control form-control-sm w-auto">
          <option value="10">10</option>
          <option value="15" selected>15</option>
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
          <th class="w-10">
            <input type="checkbox" id="select-all-txn" onclick="toggleSelectAllTxn()" class="form-checkbox">
          </th>
          <th class="w-12 text-center">No</th>
          <th>Tanggal</th>
          <th>Kategori</th>
          <th>Sumber</th>
          <th>Tipe</th>
          <th class="text-right">Nominal</th>
          <th class="w-28 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody id="txn-tbody">
        <tr><td colspan="8" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div id="pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
</div>

<!-- ═══════ MODAL: EDIT PEMASUKAN ═══════ -->
<div id="modal-edit-pemasukan" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-edit-pemasukan')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('edit-2', 'w-5 h-5 text-primary-600') ?> Edit Pemasukan</h3>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-edit-pemasukan')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-edit-pemasukan" onsubmit="submitEditPemasukan(event)" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="edit-p-id" name="id">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal" id="edit-p-tanggal" class="form-control" required>
            </div>
            <div>
              <label class="form-label">Kategori <span class="text-red-500">*</span></label>
              <select name="kategori" id="edit-p-kategori" class="form-control" required>
                <?php foreach (\App\Config\Kategori::$pemasukan as $k): ?>
                <option value="<?= $k ?>"><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Nominal <span class="text-red-500">*</span></label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah" id="edit-p-jumlah" class="form-control input-rupiah pl-9" required>
              </div>
            </div>
            <div>
              <label class="form-label">Jumlah Diterima</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah_diterima" id="edit-p-diterima" class="form-control input-rupiah pl-9">
              </div>
            </div>
            <div>
              <label class="form-label">Status Dana</label>
              <select name="status_dana" id="edit-p-status" class="form-control">
                <option value="diterima">Sudah Diterima</option>
                <option value="sebagian">Sebagian</option>
                <option value="belum_diterima">Belum Diterima</option>
              </select>
            </div>
            <div>
              <label class="form-label">Sumber</label>
              <input type="text" name="sumber" id="edit-p-sumber" class="form-control">
            </div>
          </div>
          <div class="mt-4">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" id="edit-p-ket" class="form-control">
          </div>
          <!-- Preview bukti + upload baru -->
          <div class="mt-4">
            <label class="form-label">Bukti Transaksi</label>
            <div id="edit-p-bukti-preview" class="mb-2"></div>
            <input type="file" name="bukti" accept="image/*,.pdf" class="text-sm">
            <p class="form-hint">Kosongkan jika tidak ingin mengubah bukti.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-pemasukan')">Batal</button>
          <button type="submit" class="btn btn-primary"><?= iconsax('save-2', '') ?> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: EDIT PENGELUARAN ═══════ -->
<div id="modal-edit-pengeluaran" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-edit-pengeluaran')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('edit-2', 'w-5 h-5 text-primary-600') ?> Edit Pengeluaran</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-edit-pengeluaran')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-edit-pengeluaran" onsubmit="submitEditPengeluaran(event)" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="edit-e-id" name="id">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal" id="edit-e-tanggal" class="form-control" required>
            </div>
            <div>
              <label class="form-label">Kategori <span class="text-red-500">*</span></label>
              <select name="kategori" id="edit-e-kategori" class="form-control" required>
                <?php foreach (\App\Config\Kategori::$pengeluaran as $k): ?>
                <option value="<?= $k ?>"><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Nominal <span class="text-red-500">*</span></label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah" id="edit-e-jumlah" class="form-control input-rupiah pl-9" required>
              </div>
            </div>
            <div>
              <label class="form-label">Tujuan / Penerima</label>
              <input type="text" name="tujuan" id="edit-e-tujuan" class="form-control">
            </div>
          </div>
          <div class="mt-4">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" id="edit-e-ket" class="form-control">
          </div>
          <div class="mt-4">
            <label class="form-label">Bukti Transaksi</label>
            <div id="edit-e-bukti-preview" class="mb-2"></div>
            <input type="file" name="bukti" accept="image/*,.pdf" class="text-sm">
            <p class="form-hint">Kosongkan jika tidak ingin mengubah bukti.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-pengeluaran')">Batal</button>
          <button type="submit" class="btn btn-primary"><?= iconsax('save-2', '') ?> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: DETAIL ═══════ -->
<div id="modal-detail" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-detail')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('document-text', 'w-5 h-5 text-primary-600') ?> Detail Transaksi</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="detail-subtitle"></p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-detail')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm" id="detail-list"></dl>
        <div class="mt-4" id="detail-bukti-wrap"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-detail')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: HAPUS (single) ═══════ -->
<div id="modal-hapus" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-hapus')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center">
            <?= iconsax('warning-2', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Hapus Data?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Tindakan ini tidak dapat dibatalkan</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          Data dan bukti transaksi yang terlampir akan dihapus permanen dari sistem.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-hapus')">Batal</button>
        <button type="button" class="btn btn-danger" id="btn-confirm-hapus">
          <?= iconsax('trash', '') ?> Ya, Hapus
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: BULK HAPUS ═══════ -->
<div id="modal-bulk-hapus" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-bulk-hapus')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center">
            <?= iconsax('warning-2', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Hapus Data Terpilih?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Data pemasukan &amp; pengeluaran yang tercentang</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          <span id="bulk-count-modal" class="font-semibold text-red-600">0</span> baris akan dihapus permanen beserta bukti terlampir.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-bulk-hapus')">Batal</button>
        <button type="button" class="btn btn-danger" id="btn-confirm-bulk-hapus">
          <?= iconsax('trash', '') ?> Ya, Hapus Semua
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: IMPORT CSV ═══════ -->
<div id="modal-import" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-import')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('document-upload', 'w-5 h-5 text-primary-600') ?> Import CSV</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-import')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-import" onsubmit="submitImport(event)" enctype="multipart/form-data">
        <input type="hidden" name="tipe" value="gabungan">
        <div class="modal-body space-y-4">
          <div>
            <label class="form-label">File Excel / CSV</label>
            <input type="file" name="import_file" accept=".csv,.xlsx,.xls" class="form-control" required>
            <p class="form-hint">
              Hanya file <strong>.xlsx</strong> atau <strong>.csv</strong> yang didukung (max 5 MB). Kolom: tanggal, kategori,
              pemasukan, pengeluaran, jumlah_diterima, status_dana, sumber_tujuan, keterangan — isi hanya salah satu kolom
              pemasukan/pengeluaran per baris. Pakai template di bawah supaya formatnya pasti sesuai.
            </p>
          </div>
          <div class="flex gap-2 flex-wrap">
            <a href="<?= base_url('admin/keuangan/template-universal-excel') ?>" class="btn btn-outline btn-sm">Template Gabungan (Excel)</a>
            <a href="<?= base_url('admin/keuangan/template-universal-csv') ?>" class="btn btn-outline btn-sm">Template Gabungan (CSV)</a>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-import')">Batal</button>
          <button type="submit" class="btn btn-primary"><?= iconsax('document-upload', '') ?> Proses</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: EXPORT ═══════ -->
<div id="modal-export" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-export')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('document-download', 'w-5 h-5 text-primary-600') ?> Export Data</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-export')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Pilih format export data gabungan (pemasukan &amp; pengeluaran).</p>
        <div class="space-y-2">
          <a href="<?= base_url('admin/laporan/export-excel') ?>" class="btn btn-outline w-full justify-start">
            <?= iconsax('export-square', 'text-emerald-600') ?> Export Excel
          </a>
          <a href="<?= base_url('admin/laporan/export-pdf') ?>" class="btn btn-outline w-full justify-start">
            <?= iconsax('document-text', 'text-red-600') ?> Export PDF
          </a>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-export')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
const BASE_URL = '<?= base_url() ?>';

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

  document.getElementById('txn-tbody').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-slate-500">Memuat...</td></tr>';

  fetch(BASE_URL + 'admin/keuangan/ajax?' + params.toString())
    .then(r => r.json())
    .then(data => {
      currentPage = data.page || 1;
      renderTxn(data.data || []);
      document.getElementById('txn-total').textContent = new Intl.NumberFormat('id-ID').format(data.total || 0);
      renderPagination(data.total || 0, parseInt(perPage), currentPage);
      clearAllSelection();
    })
    .catch(() => {
      document.getElementById('txn-tbody').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-500">Gagal memuat data.</td></tr>';
    });
}
window.refreshTxn = refreshTxn;

function renderTxn(rows) {
  const tbody = document.getElementById('txn-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-slate-500"><img src="https://img.icons8.com/3d-fluency/64/empty-box.png" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Tidak ada data yang cocok.</td></tr>';
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
      ? `<button onclick="tandaiSelesai(${r.id}, ${r.jumlah})" title="Tandai Selesai/Lunas" class="p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-slate-500 hover:text-emerald-600">${iconsax('tick-circle', 'w-4 h-4')}</button>`
      : '';
    return `<tr data-id="${r.id}" data-type="${r.tipe}">
      <td><input type="checkbox" class="row-checkbox row-checkbox-${r.tipe} form-checkbox" onclick="onRowCheck(this)"></td>
      <td class="text-center text-slate-500">${r.nomor ?? '-'}</td>
      <td class="whitespace-nowrap">${dateFormatted}</td>
      <td class="truncate max-w-[180px]">${escapeHtml(r.kategori || '-')}</td>
      <td class="truncate max-w-[150px]">${escapeHtml(r.sumber || r.tujuan || '-')}</td>
      <td>${badge}</td>
      <td class="text-right font-medium text-currency ${isP ? 'text-emerald-600' : 'text-red-600'}">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(nominal))}</td>
      <td>
        <div class="flex items-center justify-center gap-1">
          <button onclick='showDetailRow(${rowJson})' title="Detail" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600">${iconsax('eye', 'w-4 h-4')}</button>
          <button onclick='${editFn}(${rowJson})' title="Edit" class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600">${iconsax('edit-2', 'w-4 h-4')}</button>
          ${selesaiBtn}
          <button onclick="${deleteFn}(${r.id})" title="Hapus" class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600">${iconsax('trash', 'w-4 h-4')}</button>
        </div>
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

let filterTimer;
function scheduleFilterReset() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => { currentPage = null; refreshTxn(); }, 300);
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
    ['Nominal', `<span class="font-semibold ${isP ? 'text-emerald-600' : 'text-red-600'}">${fmtRp(row.jumlah)}</span>`],
  ];
  if (isP) {
    items.push(['Jumlah Diterima', fmtRp(row.jumlah_diterima)]);
    items.push(['Status Dana', statusBadge(row.status_dana)]);
    items.push(['Sumber', row.sumber ? escapeHtml(row.sumber) : '-']);
  } else {
    items.push(['Tujuan / Penerima', row.tujuan ? escapeHtml(row.tujuan) : '-']);
  }
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

document.getElementById('btn-confirm-hapus').onclick = async function() {
  if (!pendingDelete) return;
  const res = await fetch(pendingDelete, { method: 'POST' });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-hapus'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal menghapus', 'error');
  pendingDelete = null;
};

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

document.getElementById('btn-confirm-bulk-hapus').onclick = async function() {
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
