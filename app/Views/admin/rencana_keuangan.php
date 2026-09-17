<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Rencana Keuangan</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola rencana pemasukan &amp; pengeluaran mendatang</p>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <button class="btn btn-success btn-sm" onclick="openModal('modal-rp')">
      <?= iconsax('add', '') ?> <span class="hidden sm:inline">Rencana Pemasukan</span>
    </button>
    <button class="btn btn-danger btn-sm" onclick="openModal('modal-re')">
      <?= iconsax('add', '') ?> <span class="hidden sm:inline">Rencana Pengeluaran</span>
    </button>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
  <!-- Rencana Pemasukan -->
  <div>
  <?php if ($danaTaktisBelumDibayar['jumlah'] > 0): ?>
  <div class="card mb-3">
    <div class="card-body flex items-center justify-between gap-3 py-3 flex-wrap">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
          <?= iconsax('airplane', 'w-4 h-4') ?>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">Setoran Dana Taktis (Perjalanan Dinas) belum dibayar &middot; <?= number_format($danaTaktisBelumDibayar['jumlah'], 0, ',', '.') ?> peserta</p>
          <p class="text-base font-bold text-emerald-600">Rp <?= number_format($danaTaktisBelumDibayar['total'], 0, ',', '.') ?></p>
        </div>
      </div>
      <a href="<?= base_url('admin/perjalanan-dinas/dana-taktis') ?>" class="btn btn-outline btn-sm shrink-0">
        <span class="hidden sm:inline">Lihat Daftar</span> <?= iconsax('arrow-right', '') ?>
      </a>
    </div>
  </div>
  <?php endif; ?>
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <?= iconsax('trend-up', 'w-4 h-4 text-emerald-600') ?> Rencana Pemasukan
      </h3>
      <span class="text-xs text-slate-500">
        <?= $totalPemasukanAll > $limitRencana ? 'Menampilkan ' . count($rencanaPemasukan) . ' terbaru dari ' . $totalPemasukanAll : 'Total: ' . $totalPemasukanAll ?>
      </span>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th class="text-right">Estimasi</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($rencanaPemasukan)): ?>
          <tr><td colspan="5" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Belum ada rencana pemasukan.</td></tr>
        <?php else: foreach ($rencanaPemasukan as $row): $sisaP = (float)$row['jumlah_rencana'] - (float)($row['jumlah_terealisasi'] ?? 0); ?>
          <tr>
            <td class="whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal_rencana'])) ?></td>
            <td class="truncate max-w-[180px]"><?= esc($row['kategori']) ?></td>
            <td class="text-right font-medium text-emerald-600 text-currency">
              Rp <?= number_format($row['jumlah_rencana'], 0, ',', '.') ?>
              <?php if (($row['jumlah_terealisasi'] ?? 0) > 0): ?>
              <div class="text-xs font-normal text-slate-500">Terealisasi: Rp <?= number_format($row['jumlah_terealisasi'], 0, ',', '.') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $row['status'] === 'terealisasi' ? 'badge-success' : 'badge-warning' ?>">
                <?= ucfirst($row['status']) ?>
              </span>
              <?php if ($row['status'] === 'terealisasi' && !empty($row['updated_at'])): ?>
              <div class="text-xs text-slate-500 mt-1">
                <i class="ti ti-calendar-check text-emerald-500"></i>
                <?= date('d M Y', strtotime($row['updated_at'])) ?>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex items-center justify-center gap-1">
                <button type="button" onclick="showDetailRencana(this)" data-row="<?= esc(json_encode($row), 'attr') ?>" data-tipe="pemasukan"
                        class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Detail">
                  <?= iconsax('eye', 'w-4 h-4') ?>
                </button>
              <?php if ($row['status'] === 'aktif'): ?>
                <button onclick="realisasiPemasukan(<?= $row['id'] ?>, <?= $sisaP ?>)"
                        class="btn btn-success btn-sm" title="Realisasi">
                  <?= iconsax('check', '') ?> Realisasi
                </button>
              <?php endif; ?>
                <button onclick="deleteRencanaPemasukan(<?= $row['id'] ?>)"
                        class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" title="Hapus">
                  <?= iconsax('trash', 'w-4 h-4') ?>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>

  <!-- Rencana Pengeluaran -->
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <?= iconsax('trend-down', 'w-4 h-4 text-red-600') ?> Rencana Pengeluaran
      </h3>
      <span class="text-xs text-slate-500">
        <?= $totalPengeluaranAll > $limitRencana ? 'Menampilkan ' . count($rencanaPengeluaran) . ' terbaru dari ' . $totalPengeluaranAll : 'Total: ' . $totalPengeluaranAll ?>
      </span>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th class="text-right">Estimasi</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($rencanaPengeluaran)): ?>
          <tr><td colspan="5" class="text-center py-8 text-slate-500"><img src="<?= icons8('empty-box', '3d-fluency', 64) ?>" alt="" class="w-10 h-10 mx-auto mb-2 opacity-80"><br>Belum ada rencana pengeluaran.</td></tr>
        <?php else: foreach ($rencanaPengeluaran as $row): $sisaE = (float)$row['jumlah_rencana'] - (float)($row['jumlah_terealisasi'] ?? 0); ?>
          <tr>
            <td class="whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal_rencana'])) ?></td>
            <td class="truncate max-w-[180px]"><?= esc($row['kategori']) ?></td>
            <td class="text-right font-medium text-red-600 text-currency">
              Rp <?= number_format($row['jumlah_rencana'], 0, ',', '.') ?>
              <?php if (($row['jumlah_terealisasi'] ?? 0) > 0): ?>
              <div class="text-xs font-normal text-slate-500">Terealisasi: Rp <?= number_format($row['jumlah_terealisasi'], 0, ',', '.') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $row['status'] === 'terealisasi' ? 'badge-success' : 'badge-warning' ?>">
                <?= ucfirst($row['status']) ?>
              </span>
              <?php if ($row['status'] === 'terealisasi' && !empty($row['updated_at'])): ?>
              <div class="text-xs text-slate-500 mt-1">
                <i class="ti ti-calendar-check text-emerald-500"></i>
                <?= date('d M Y', strtotime($row['updated_at'])) ?>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex items-center justify-center gap-1">
                <button type="button" onclick="showDetailRencana(this)" data-row="<?= esc(json_encode($row), 'attr') ?>" data-tipe="pengeluaran"
                        class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 hover:text-primary-600" title="Detail">
                  <?= iconsax('eye', 'w-4 h-4') ?>
                </button>
              <?php if ($row['status'] === 'aktif'): ?>
                <button onclick="realisasiPengeluaran(<?= $row['id'] ?>, <?= $sisaE ?>)"
                        class="btn btn-danger btn-sm" title="Realisasi">
                  <?= iconsax('check', '') ?> Realisasi
                </button>
              <?php endif; ?>
                <button onclick="deleteRencanaPengeluaran(<?= $row['id'] ?>)"
                        class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 text-slate-500 hover:text-red-600" title="Hapus">
                  <?= iconsax('trash', 'w-4 h-4') ?>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Rencana Pemasukan -->
<div id="modal-rp" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-rp')"></div>
  <div class="modal-container">
    <div class="modal-box">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('add', 'w-5 h-5 text-emerald-600') ?> Rencana Pemasukan Baru</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-rp')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-rp" onsubmit="submitRencanaPemasukan(event)">
        <div class="modal-body space-y-4">
          <div><label class="form-label">Tanggal Rencana <span class="text-red-500">*</span></label>
            <input type="date" name="tanggal_rencana" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div><label class="form-label">Kategori <span class="text-red-500">*</span></label>
            <select name="kategori" class="form-control" required>
              <option value="">Pilih kategori...</option>
              <?php foreach (\App\Config\Kategori::$pemasukan as $k): ?>
              <option value="<?= esc($k) ?>"><?= esc($k) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div><label class="form-label">Estimasi Nominal <span class="text-red-500">*</span></label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
              <input type="text" inputmode="numeric" name="jumlah_rencana" class="form-control input-rupiah pl-9" placeholder="0" required>
            </div></div>
          <div><label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi rencana..."></textarea></div>
          <div>
            <label class="form-label">Bukti (Opsional)</label>
            <label class="block border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition">
              <input type="file" name="bukti" accept="image/*,.pdf" class="hidden">
              <?= iconsax('cloud-add', 'w-6 h-6 mx-auto text-slate-400') ?>
              <div class="text-sm text-slate-600 dark:text-slate-300 mt-1">Klik atau seret file</div>
              <div class="text-xs text-slate-500 mt-0.5">JPG, PNG, PDF &middot; Max 3MB</div>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-rp')">Batal</button>
          <button type="submit" class="btn btn-success"><?= iconsax('save-2', '') ?> Simpan Rencana</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Rencana Pengeluaran -->
<div id="modal-re" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-re')"></div>
  <div class="modal-container">
    <div class="modal-box">
      <div class="modal-header">
        <div><h3 class="modal-title"><?= iconsax('add', 'w-5 h-5 text-red-600') ?> Rencana Pengeluaran Baru</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-re')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <form id="form-re" onsubmit="submitRencanaPengeluaran(event)">
        <div class="modal-body space-y-4">
          <div><label class="form-label">Tanggal Rencana <span class="text-red-500">*</span></label>
            <input type="date" name="tanggal_rencana" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div><label class="form-label">Kategori <span class="text-red-500">*</span></label>
            <select name="kategori" class="form-control" required>
              <option value="">Pilih kategori...</option>
              <?php foreach (\App\Config\Kategori::$pengeluaran as $k): ?>
              <option value="<?= esc($k) ?>"><?= esc($k) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div><label class="form-label">Estimasi Nominal <span class="text-red-500">*</span></label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
              <input type="text" inputmode="numeric" name="jumlah_rencana" class="form-control input-rupiah pl-9" placeholder="0" required>
            </div></div>
          <div><label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi rencana..."></textarea></div>
          <div>
            <label class="form-label">Bukti (Opsional)</label>
            <label class="block border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-4 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition">
              <input type="file" name="bukti" accept="image/*,.pdf" class="hidden">
              <?= iconsax('cloud-add', 'w-6 h-6 mx-auto text-slate-400') ?>
              <div class="text-sm text-slate-600 dark:text-slate-300 mt-1">Klik atau seret file</div>
              <div class="text-xs text-slate-500 mt-0.5">JPG, PNG, PDF &middot; Max 3MB</div>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-re')">Batal</button>
          <button type="submit" class="btn btn-danger"><?= iconsax('save-2', '') ?> Simpan Rencana</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Realisasi Pemasukan -->
<div id="modal-realisasi-p" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-realisasi-p')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <?= iconsax('tick-circle', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Realisasi Pemasukan</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Konversi rencana ke transaksi</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <input type="hidden" id="realisasi-p-id">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Ubah rencana ini menjadi transaksi pemasukan aktual.</p>
        <div class="mb-4">
          <label class="form-label">Cakupan Realisasi</label>
          <div class="segment w-full" id="realisasi-p-scope">
            <button type="button" class="segment-btn active flex-1" data-scope="semua" onclick="setRealisasiScope('p', 'semua')">Semua</button>
            <button type="button" class="segment-btn flex-1" data-scope="sebagian" onclick="setRealisasiScope('p', 'sebagian')">Sebagian</button>
          </div>
        </div>
        <div>
          <label class="form-label">Jumlah Diterima Aktual <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
            <input type="text" inputmode="numeric" class="form-control input-rupiah pl-9" id="realisasi-p-jumlah" required>
          </div>
          <p class="form-hint">Nominal ini yang akan tersimpan sebagai transaksi, bukan nilai rencana.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-realisasi-p')">Batal</button>
        <button class="btn btn-success" onclick="confirmRealisasiPemasukan()"><?= iconsax('check', '') ?> Realisasikan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Realisasi Pengeluaran -->
<div id="modal-realisasi-e" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-realisasi-e')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center">
            <?= iconsax('tick-circle', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Realisasi Pengeluaran</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Konversi rencana ke transaksi</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <input type="hidden" id="realisasi-e-id">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Ubah rencana ini menjadi transaksi pengeluaran aktual.</p>
        <div class="mb-4">
          <label class="form-label">Cakupan Realisasi</label>
          <div class="segment w-full" id="realisasi-e-scope">
            <button type="button" class="segment-btn active flex-1" data-scope="semua" onclick="setRealisasiScope('e', 'semua')">Semua</button>
            <button type="button" class="segment-btn flex-1" data-scope="sebagian" onclick="setRealisasiScope('e', 'sebagian')">Sebagian</button>
          </div>
        </div>
        <div>
          <label class="form-label">Jumlah Aktual Dikeluarkan <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
            <input type="text" inputmode="numeric" class="form-control input-rupiah pl-9" id="realisasi-e-jumlah" required>
          </div>
          <p class="form-hint">Nominal ini yang akan tersimpan sebagai transaksi, bukan nilai rencana.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-realisasi-e')">Batal</button>
        <button class="btn btn-danger" onclick="confirmRealisasiPengeluaran()"><?= iconsax('check', '') ?> Realisasikan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Rencana -->
<div id="modal-detail-rencana" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-detail-rencana')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div><h3 class="modal-title" id="detail-rencana-title"><?= iconsax('document-text', 'w-5 h-5 text-primary-600') ?> Detail Rencana</h3></div>
        <button class="btn btn-ghost btn-icon" onclick="closeModal('modal-detail-rencana')"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body space-y-3">
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Tanggal Rencana</div>
          <div class="text-sm font-medium text-slate-800 dark:text-slate-100" id="detail-rencana-tanggal"></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Kategori</div>
          <div class="text-sm font-medium text-slate-800 dark:text-slate-100" id="detail-rencana-kategori"></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Estimasi Nominal (Rencana Awal)</div>
          <div class="text-sm font-medium text-slate-800 dark:text-slate-100 text-currency" id="detail-rencana-jumlah"></div>
        </div>
        <div id="detail-rencana-progress-wrap" class="hidden">
          <div class="text-xs text-slate-500 dark:text-slate-400">Sudah Terealisasi / Sisa</div>
          <div class="text-sm font-medium text-slate-800 dark:text-slate-100 text-currency" id="detail-rencana-progress"></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Status</div>
          <div class="text-sm" id="detail-rencana-status"></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Keterangan</div>
          <div class="text-sm text-slate-700 dark:text-slate-200" id="detail-rencana-keterangan"></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Bukti</div>
          <div class="text-sm" id="detail-rencana-bukti"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-detail-rencana')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Umum -->
<div id="modal-konfirmasi" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-konfirmasi')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <?= iconsax('warning-2', 'w-5 h-5') ?>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100" id="konfirmasi-title">Konfirmasi</h3>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300" id="konfirmasi-text">Yakin ingin melanjutkan?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modal-konfirmasi')">Batal</button>
        <button class="btn btn-danger" id="btn-konfirmasi-ya">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const BASE = '<?= base_url() ?>';

async function submitRencanaPemasukan(e) {
  e.preventDefault();
  const form = document.getElementById('form-rp');
  const res  = await fetch(BASE + 'admin/rencana/pemasukan', { method: 'POST', body: buildFormData(form) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-rp'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal', 'error');
}

async function submitRencanaPengeluaran(e) {
  e.preventDefault();
  const form = document.getElementById('form-re');
  const res  = await fetch(BASE + 'admin/rencana/pengeluaran', { method: 'POST', body: buildFormData(form) });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-re'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal', 'error');
}

function showDetailRencana(btn) {
  const row = JSON.parse(btn.dataset.row);
  const tipe = btn.dataset.tipe;
  const aksen = tipe === 'pemasukan' ? 'text-emerald-600' : 'text-red-600';
  document.getElementById('detail-rencana-title').innerHTML =
    iconsax('document-text', 'w-5 h-5 ' + aksen) + ' Detail Rencana ' + (tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran');
  document.getElementById('detail-rencana-tanggal').textContent =
    new Date(row.tanggal_rencana).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  document.getElementById('detail-rencana-kategori').textContent = row.kategori;
  document.getElementById('detail-rencana-jumlah').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(row.jumlah_rencana);
  const terealisasi = parseFloat(row.jumlah_terealisasi) || 0;
  const progressWrap = document.getElementById('detail-rencana-progress-wrap');
  if (terealisasi > 0) {
    const sisa = (parseFloat(row.jumlah_rencana) || 0) - terealisasi;
    document.getElementById('detail-rencana-progress').textContent =
      'Rp ' + new Intl.NumberFormat('id-ID').format(terealisasi) + ' / Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, sisa));
    progressWrap.classList.remove('hidden');
  } else {
    progressWrap.classList.add('hidden');
  }
  let statusHtml = '<span class="badge ' + (row.status === 'terealisasi' ? 'badge-success' : 'badge-warning') + '">' + row.status.charAt(0).toUpperCase() + row.status.slice(1) + '</span>';
  if (row.status === 'terealisasi' && row.updated_at) {
    statusHtml += '<div class="text-xs text-slate-500 mt-1"><i class="ti ti-calendar-check text-emerald-500 mr-1"></i>Tanggal Realisasi: ' + new Date(row.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) + '</div>';
  }
  document.getElementById('detail-rencana-status').innerHTML = statusHtml;
  document.getElementById('detail-rencana-keterangan').textContent = row.keterangan || '-';
  const buktiEl = document.getElementById('detail-rencana-bukti');
  if (row.file_bukti) {
    buktiEl.innerHTML = '<a href="' + BASE + 'uploads/bukti/' + row.file_bukti + '" target="_blank" class="inline-flex items-center gap-1 text-primary-600 hover:underline">' + iconsax('paperclip-2', 'w-3.5 h-3.5') + ' Lihat bukti</a>';
  } else {
    buktiEl.textContent = 'Tidak ada bukti dilampirkan';
  }
  openModal('modal-detail-rencana');
}

function tampilkanKonfirmasi(pesan, aksi) {
  document.getElementById('konfirmasi-text').textContent = pesan;
  const btn = document.getElementById('btn-konfirmasi-ya');
  btn.onclick = async function() { closeModal('modal-konfirmasi'); await aksi(); };
  openModal('modal-konfirmasi');
}

function deleteRencanaPemasukan(id) {
  tampilkanKonfirmasi('Hapus rencana pemasukan ini?', async () => {
    const res  = await fetch(BASE + 'admin/rencana/pemasukan/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
  });
}
function deleteRencanaPengeluaran(id) {
  tampilkanKonfirmasi('Hapus rencana pengeluaran ini?', async () => {
    const res  = await fetch(BASE + 'admin/rencana/pengeluaran/delete/' + id, { method: 'POST' });
    const json = await res.json();
    if (json.success) { showToast(json.message, 'success'); setTimeout(() => location.reload(), 800); }
  });
}
/** Segmen "Semua/Sebagian" pada modal realisasi — cuma toggle tampilan tab aktif, nominal
 *  tetap bisa diedit bebas di kedua mode (user yang menentukan angka aktualnya). */
function setRealisasiScope(tipe, scope) {
  document.querySelectorAll('#realisasi-' + tipe + '-scope .segment-btn').forEach(b => b.classList.toggle('active', b.dataset.scope === scope));
  document.getElementById('realisasi-' + tipe + '-scope').dataset.selected = scope;
}

function realisasiPemasukan(id, estimasi) {
  document.getElementById('realisasi-p-id').value = id;
  // Use setRupiahValue helper — safe against DB decimal x1000 bug
  setRupiahValue(document.getElementById('realisasi-p-jumlah'), estimasi);
  setRealisasiScope('p', 'semua');
  openModal('modal-realisasi-p');
}
async function confirmRealisasiPemasukan() {
  const id = document.getElementById('realisasi-p-id').value;
  const scope = document.getElementById('realisasi-p-scope').dataset.selected || 'semua';
  const fd = new FormData();
  fd.append('jumlah_diterima', unformatRibuan(document.getElementById('realisasi-p-jumlah').value));
  fd.append('scope', scope);
  const res  = await fetch(BASE + 'admin/rencana/pemasukan/realisasi/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-realisasi-p'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal', 'error');
}
function realisasiPengeluaran(id, estimasi) {
  document.getElementById('realisasi-e-id').value = id;
  setRupiahValue(document.getElementById('realisasi-e-jumlah'), estimasi);
  setRealisasiScope('e', 'semua');
  openModal('modal-realisasi-e');
}
async function confirmRealisasiPengeluaran() {
  const id = document.getElementById('realisasi-e-id').value;
  const scope = document.getElementById('realisasi-e-scope').dataset.selected || 'semua';
  const fd = new FormData();
  fd.append('jumlah', unformatRibuan(document.getElementById('realisasi-e-jumlah').value));
  fd.append('scope', scope);
  const res  = await fetch(BASE + 'admin/rencana/pengeluaran/realisasi/' + id, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) { showToast(json.message, 'success'); closeModal('modal-realisasi-e'); setTimeout(() => location.reload(), 800); }
  else showToast(json.message || 'Gagal', 'error');
}
</script>
<?= $this->endSection() ?>
