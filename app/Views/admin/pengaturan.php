<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Pengaturan Sistem</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Parameter keuangan, notifikasi, dan akun admin</p>
</div>

<form action="<?= base_url('admin/pengaturan') ?>" method="POST" id="form-pengaturan">
  <?= csrf_field() ?>

  <!-- Parameter Keuangan & Notifikasi -->
  <div class="card max-w-2xl">
    <div class="card-header">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
        <?= iconsax('slider-horizontal', 'w-4 h-4 text-primary-600') ?> Parameter Keuangan &amp; Notifikasi
      </h3>
    </div>
    <div class="card-body space-y-6">

      <!-- Threshold -->
      <div>
        <label class="form-label">Batas Threshold Notifikasi <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">Rp</span>
          <input type="text" inputmode="numeric" name="threshold_notif"
                 class="form-control input-rupiah pl-9"
                 value="<?= esc($setting['threshold_notif'] ?? 5000000) ?>" required>
        </div>
        <p class="form-hint">Batas minimum saldo kas &amp; transaksi besar untuk pemicu notifikasi otomatis.</p>
      </div>

      <!-- Notifikasi -->
      <div>
        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2 mb-3">
          <?= iconsax('notification', 'w-4 h-4 text-amber-600') ?> Opsi Notifikasi Otomatis
        </h4>
        <div class="space-y-2">
          <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50">
            <input type="checkbox" name="notif_saldo_rendah" value="1" <?= ($setting['notif_saldo_rendah'] ?? 1) ? 'checked' : '' ?>
                   class="form-checkbox mt-0.5">
            <span class="text-sm text-slate-700 dark:text-slate-200">
              <span class="font-medium">Peringatan Saldo Rendah</span>
              <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kirim notifikasi jika saldo kas di bawah threshold</span>
            </span>
          </label>
          <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50">
            <input type="checkbox" name="notif_transaksi_besar" value="1" <?= ($setting['notif_transaksi_besar'] ?? 1) ? 'checked' : '' ?>
                   class="form-checkbox mt-0.5">
            <span class="text-sm text-slate-700 dark:text-slate-200">
              <span class="font-medium">Notifikasi Transaksi Besar</span>
              <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kirim notifikasi untuk transaksi ≥ threshold</span>
            </span>
          </label>
        </div>
      </div>

    </div>
  </div>

  <div class="modal-footer mt-6 rounded-xl">
    <button type="submit" class="btn btn-primary">
      <?= iconsax('save-2', '') ?> Simpan Pengaturan
    </button>
  </div>
</form>

<!-- Kelola Akun Admin (super_admin only, dipisah dari form pengaturan operasional di atas) -->
<div class="card mt-6">
  <div class="card-header">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
      <?= iconsax('shield-tick', 'w-4 h-4 text-primary-600') ?> Kelola Akun Admin
    </h3>
    <button type="button" onclick="bukaModalTambahAkun()" class="btn btn-primary btn-sm">
      <?= iconsax('add', '') ?> Tambah Akun
    </button>
  </div>
  <div class="overflow-x-auto">
    <table class="table">
      <thead><tr><th>Username</th><th>Role</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
      <tbody id="daftar-akun-body">
        <?php if (empty($daftarAkun)): ?>
        <tr><td colspan="4" class="text-center py-8 text-slate-500">Belum ada akun admin</td></tr>
        <?php else: foreach ($daftarAkun as $akun): ?>
        <tr>
          <td class="font-medium"><?= esc($akun['username']) ?></td>
          <td>
            <?php if ($akun['role'] === 'super_admin'): ?>
              <span class="badge badge-info">Super Admin</span>
            <?php else: ?>
              <span class="badge badge-muted">Admin</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($akun['aktif']): ?>
              <span class="badge badge-success">Aktif</span>
            <?php else: ?>
              <span class="badge badge-warning">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td class="text-right whitespace-nowrap">
            <button type="button" onclick='bukaModalEditAkun(<?= json_encode($akun) ?>)' class="btn btn-ghost btn-icon btn-sm" title="Edit">
              <?= iconsax('edit-2', 'w-4 h-4') ?>
            </button>
            <button type="button" onclick="toggleAktifAkun(<?= (int)$akun['id'] ?>, '<?= esc($akun['username'], 'js') ?>', <?= $akun['aktif'] ? 'true' : 'false' ?>)"
                    class="btn btn-ghost btn-icon btn-sm" title="<?= $akun['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
              <?= iconsax($akun['aktif'] ? 'user-remove' : 'user-add', 'w-4 h-4') ?>
            </button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah/Edit Akun Admin -->
<div id="modal-akun" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-akun')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <form id="form-akun" onsubmit="return submitFormAkun(event)">
        <div class="modal-header">
          <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100" id="modal-akun-title">Tambah Akun Admin</h3>
        </div>
        <div class="modal-body space-y-4">
          <div>
            <label class="form-label">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" id="akun-username" class="form-control" required minlength="3">
          </div>
          <div>
            <label class="form-label">Role <span class="text-red-500">*</span></label>
            <select name="role" id="akun-role" class="form-control" required>
              <option value="admin">Admin (Dana Taktis &amp; Perjalanan Dinas saja)</option>
              <option value="super_admin">Super Admin (semua fitur)</option>
            </select>
          </div>
          <div>
            <label class="form-label" id="akun-password-label">Password <span class="text-red-500">*</span></label>
            <div class="relative">
              <input type="password" name="password" id="akun-password" class="form-control pr-10" minlength="6" autocomplete="new-password">
              <button type="button" onclick="togglePasswordVisibility('akun-password', this)" tabindex="-1"
                      class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <?= iconsax('eye', 'w-4 h-4') ?>
              </button>
            </div>
            <p class="form-hint" id="akun-password-hint">Minimal 6 karakter.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modal-akun')">Batal</button>
          <button type="submit" class="btn btn-primary">
            <?= iconsax('save-2', '') ?> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const AKUN_BASE_URL = '<?= base_url('admin/admin-akun') ?>';
let akunEditId = null;

function bukaModalTambahAkun() {
  akunEditId = null;
  document.getElementById('modal-akun-title').textContent = 'Tambah Akun Admin';
  document.getElementById('form-akun').reset();
  document.getElementById('akun-password').required = true;
  document.getElementById('akun-password-label').innerHTML = 'Password <span class="text-red-500">*</span>';
  document.getElementById('akun-password-hint').textContent = 'Minimal 6 karakter.';
  openModal('modal-akun');
}

function bukaModalEditAkun(akun) {
  akunEditId = akun.id;
  document.getElementById('modal-akun-title').textContent = 'Edit Akun Admin';
  document.getElementById('akun-username').value = akun.username;
  document.getElementById('akun-role').value = akun.role;
  document.getElementById('akun-password').value = '';
  document.getElementById('akun-password').required = false;
  document.getElementById('akun-password-label').textContent = 'Password Baru';
  document.getElementById('akun-password-hint').textContent = 'Kosongkan jika tidak ingin mengubah password.';
  openModal('modal-akun');
}
window.bukaModalTambahAkun = bukaModalTambahAkun;
window.bukaModalEditAkun = bukaModalEditAkun;

async function submitFormAkun(e) {
  e.preventDefault();
  const fd = new FormData(document.getElementById('form-akun'));
  const url = akunEditId ? `${AKUN_BASE_URL}/update/${akunEditId}` : AKUN_BASE_URL;
  try {
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      showToast(json.message, 'success');
      closeModal('modal-akun');
      setTimeout(() => location.reload(), 800);
    } else {
      const pesan = json.message || Object.values(json.errors || {})[0] || 'Gagal menyimpan akun';
      showToast(pesan, 'error');
    }
  } catch (err) {
    showToast('Terjadi kesalahan saat menyimpan', 'error');
  }
  return false;
}

function toggleAktifAkun(id, username, sedangAktif) {
  const pesan = sedangAktif ? `Nonaktifkan akun "${username}"?` : `Aktifkan kembali akun "${username}"?`;
  tampilkanKonfirmasi(pesan, async () => {
    try {
      const res = await fetch(`${AKUN_BASE_URL}/toggle-aktif/${id}`, { method: 'POST' });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(json.message || 'Gagal mengubah status akun', 'error');
      }
    } catch (err) {
      showToast('Terjadi kesalahan', 'error');
    }
  });
}
window.toggleAktifAkun = toggleAktifAkun;
</script>
<?= $this->endSection() ?>
