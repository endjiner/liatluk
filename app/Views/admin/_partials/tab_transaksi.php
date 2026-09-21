<?php
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahunSekarang = (int)date('Y');
$tahunOpsi = range($tahunSekarang, 2016);
?>

<?php if ($isSuperAdmin): ?>
<div class="mb-3 flex items-center justify-end gap-2">
  <button onclick="openModal('modal-import')" class="btn btn-outline btn-sm">
    <?= iconsax('document-upload', '') ?> Import CSV
  </button>
  <button onclick="openExportMenu()" class="btn btn-outline btn-sm">
    <?= iconsax('document-download', '') ?> Export
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
<?php endif; ?>

<!-- Tabel Gabungan -->
<div class="card">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('task-square', 'w-4 h-4') ?> Daftar Transaksi
    </h3>
    <span class="text-xs text-slate-500">Total: <span id="txn-total">-</span></span>
  </div>

  <!-- Filter compact -->
  <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 space-y-2">
    <!-- Baris 1: Toggle tipe + Search -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Toggle tipe styled (sama persis dengan public) -->
      <div class="flex items-center gap-1 p-0.5 bg-slate-200/70 dark:bg-slate-700/50 rounded-lg shrink-0">
        <button type="button" id="adm-toggle-pemasukan"
          onclick="admToggleTipeTxn('pemasukan')"
          class="adm-txn-toggle inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 shadow-sm">
          <?= iconsax('trend-up', 'w-3 h-3') ?> Pemasukan
        </button>
        <button type="button" id="adm-toggle-pengeluaran"
          onclick="admToggleTipeTxn('pengeluaran')"
          class="adm-txn-toggle inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 shadow-sm">
          <?= iconsax('trend-down', 'w-3 h-3') ?> Pengeluaran
        </button>
      </div>
      <!-- Hidden checkboxes untuk kompatibilitas JS lama -->
      <input type="checkbox" id="filter-pemasukan" checked class="hidden" aria-label="Tampilkan Pemasukan" aria-hidden="true" tabindex="-1">
      <input type="checkbox" id="filter-pengeluaran" checked class="hidden" aria-label="Tampilkan Pengeluaran" aria-hidden="true" tabindex="-1">
      <!-- Search -->
      <div class="relative flex-1 min-w-[200px]">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
          <?= iconsax('search-normal-1', 'w-3.5 h-3.5') ?>
        </span>
        <input type="text" id="filter-search" aria-label="Cari transaksi" placeholder="Cari kategori, keterangan, MAK, no. ST..."
               class="form-control form-control-sm pl-8 w-full">
      </div>
    </div>
    <!-- Baris 2: Bulan, Tahun, Per page -->
    <div class="flex flex-wrap items-center justify-between gap-2.5">
      <div class="flex items-center gap-2 flex-wrap">
        <select id="filter-bulan" aria-label="Filter bulan" class="form-control form-control-sm w-auto">
          <option value="">Semua Bulan</option>
          <?php foreach ($namaBulan as $n => $nm): ?>
          <option value="<?= $n ?>"><?= $nm ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filter-tahun" aria-label="Filter tahun" class="form-control form-control-sm w-auto">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunOpsi as $t): ?>
          <option value="<?= $t ?>"><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500">
        <span>Tampilkan</span>
        <select id="filter-perpage" aria-label="Jumlah baris per halaman" class="form-control form-control-sm w-auto">
          <option value="10">10</option>
          <option value="15" selected>15</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span>baris</span>
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
          <?php if ($isSuperAdmin): ?>
          <th class="w-10">
            <input type="checkbox" id="select-all-txn" onclick="toggleSelectAllTxn()" class="form-checkbox" aria-label="Pilih semua baris">
          </th>
          <?php endif; ?>
          <th class="w-12 text-center">No</th>
          <th>Tanggal</th>
          <th>Kategori</th>
          <th>Sumber</th>
          <th>Tipe</th>
          <th class="text-right">Nominal</th>
          <th class="<?= $isSuperAdmin ? 'w-28 min-w-[100px]' : 'w-16' ?> text-center whitespace-nowrap">
            <?= $isSuperAdmin ? 'Aksi' : 'Detail' ?>
          </th>
        </tr>
      </thead>
      <tbody id="txn-tbody">
        <tr><td colspan="<?= $isSuperAdmin ? 8 : 7 ?>" class="text-center py-8 text-slate-500">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div id="pagination-wrap" class="flex items-center justify-between gap-3 p-3 border-t border-slate-200 dark:border-slate-700 flex-wrap"></div>
</div>

<?php if ($isSuperAdmin): ?>
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
              <label class="form-label" for="edit-p-tanggal">Tanggal <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal" id="edit-p-tanggal" class="form-control" required>
            </div>
            <div>
              <label class="form-label" for="edit-p-kategori">Kategori <span class="text-red-500">*</span></label>
              <select name="kategori" id="edit-p-kategori" class="form-control" required>
                <?php foreach (\App\Config\Kategori::$pemasukan as $k): ?>
                <option value="<?= $k ?>"><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label" for="edit-p-jumlah">Nominal <span class="text-red-500">*</span></label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah" id="edit-p-jumlah" class="form-control input-rupiah pl-9" required>
              </div>
            </div>
            <div>
              <label class="form-label" for="edit-p-diterima">Jumlah Diterima</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah_diterima" id="edit-p-diterima" class="form-control input-rupiah pl-9">
              </div>
            </div>
            <div>
              <label class="form-label" for="edit-p-status">Status Dana</label>
              <select name="status_dana" id="edit-p-status" class="form-control">
                <option value="diterima">Sudah Diterima</option>
                <option value="sebagian">Sebagian</option>
                <option value="belum_diterima">Belum Diterima</option>
              </select>
            </div>
            <div>
              <label class="form-label" for="edit-p-sumber">Sumber</label>
              <input type="text" name="sumber" id="edit-p-sumber" class="form-control">
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
              <label class="form-label" for="edit-p-mak">Kode MAK</label>
              <input type="text" name="kode_mak" id="edit-p-mak" class="form-control font-mono" placeholder="cth: 524111.001">
            </div>
            <div>
              <label class="form-label" for="edit-p-st">No. Surat Tugas</label>
              <input type="text" name="no_surat_tugas" id="edit-p-st" class="form-control" placeholder="cth: ST-001/...">
            </div>
            <div>
              <label class="form-label" for="edit-p-spm">No. SPM</label>
              <input type="text" name="no_spm" id="edit-p-spm" class="form-control" placeholder="cth: SPM-001/...">
            </div>
          </div>
          <div class="mt-4">
            <label class="form-label" for="edit-p-ket">Keterangan</label>
            <input type="text" name="keterangan" id="edit-p-ket" class="form-control">
          </div>
          <!-- Preview bukti + upload baru -->
          <div class="mt-4">
            <label class="form-label" for="edit-p-bukti">Bukti Transaksi</label>
            <div id="edit-p-bukti-preview" class="mb-2"></div>
            <input type="file" id="edit-p-bukti" name="bukti" accept="image/*,.pdf" class="text-sm">
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
              <label class="form-label" for="edit-e-tanggal">Tanggal <span class="text-red-500">*</span></label>
              <input type="date" name="tanggal" id="edit-e-tanggal" class="form-control" required>
            </div>
            <div>
              <label class="form-label" for="edit-e-kategori">Kategori <span class="text-red-500">*</span></label>
              <select name="kategori" id="edit-e-kategori" class="form-control" required>
                <?php foreach (\App\Config\Kategori::$pengeluaran as $k): ?>
                <option value="<?= $k ?>"><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label" for="edit-e-jumlah">Nominal <span class="text-red-500">*</span></label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
                <input type="text" inputmode="numeric" name="jumlah" id="edit-e-jumlah" class="form-control input-rupiah pl-9" required>
              </div>
            </div>
            <div>
              <label class="form-label" for="edit-e-tujuan">Tujuan / Penerima</label>
              <input type="text" name="tujuan" id="edit-e-tujuan" class="form-control">
            </div>
          </div>
          <div class="mt-4">
            <label class="form-label" for="edit-e-ket">Keterangan</label>
            <input type="text" name="keterangan" id="edit-e-ket" class="form-control">
          </div>
          <div class="mt-4">
            <label class="form-label" for="edit-e-bukti">Bukti Transaksi</label>
            <div id="edit-e-bukti-preview" class="mb-2"></div>
            <input type="file" id="edit-e-bukti" name="bukti" accept="image/*,.pdf" class="text-sm">
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
<?php endif; ?>

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

<?php if ($isSuperAdmin): ?>
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
            <label class="form-label" for="import-file">File Excel / CSV</label>
            <input type="file" id="import-file" name="import_file" accept=".csv,.xlsx,.xls" class="form-control" required>
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
<?php endif; ?>

<?= $this->include('admin/_partials/tab_transaksi_scripts') ?>
