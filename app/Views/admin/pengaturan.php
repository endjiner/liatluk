<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">Pengaturan Sistem</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Parameter keuangan, notifikasi, dan kredensial admin</p>
</div>

<form action="<?= base_url('admin/pengaturan') ?>" method="POST" id="form-pengaturan" onsubmit="return handlePengaturanSubmit(event)">
  <?= csrf_field() ?>
  <input type="hidden" name="current_password" id="current-password-field" value="">

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Parameter Keuangan & Notifikasi -->
    <div class="card lg:col-span-2">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
          <i data-lucide="sliders" class="w-4 h-4 text-primary-600"></i> Parameter Keuangan &amp; Notifikasi
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
            <i data-lucide="bell" class="w-4 h-4 text-amber-600"></i> Opsi Notifikasi Otomatis
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

    <!-- Kredensial Administrator (dipisah: aksi sensitif, konteks beda dari parameter operasional) -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
          <i data-lucide="shield-check" class="w-4 h-4 text-primary-600"></i> Kredensial Administrator
        </h3>
      </div>
      <div class="card-body space-y-6">
        <div>
          <label class="form-label">Username Admin</label>
          <input type="text" name="admin_username" id="input-username-baru"
                 class="form-control" placeholder="Kosongkan jika tidak diubah"
                 value="<?= esc($setting['admin_username'] ?? 'admin') ?>">
        </div>
        <div>
          <label class="form-label">Password Admin Baru</label>
          <div class="relative">
            <input type="password" name="admin_password" id="input-password-baru"
                   class="form-control pr-10" placeholder="Kosongkan jika tidak diubah">
            <button type="button" onclick="togglePasswordVisibility('input-password-baru', this)" tabindex="-1"
                    class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    title="Tampilkan/Sembunyikan">
              <i data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
          <p class="form-hint">Perubahan kredensial memerlukan verifikasi password saat ini.</p>
        </div>
      </div>
    </div>

  </div>

  <div class="modal-footer mt-6 rounded-xl">
    <button type="submit" class="btn btn-primary">
      <i data-lucide="save"></i> Simpan Pengaturan
    </button>
  </div>
</form>

<!-- Modal verifikasi kredensial -->
<div id="modal-konfirmasi-kredensial" class="hidden">
  <div class="modal-backdrop" onclick="closeModal('modal-konfirmasi-kredensial')"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-sm">
      <div class="modal-header">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 shrink-0 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <i data-lucide="shield-alert" class="w-5 h-5"></i>
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Verifikasi Kredensial</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Konfirmasi identitas Anda</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
          Anda akan mengubah username atau password administrator. Masukkan password Anda saat ini untuk melanjutkan.
        </p>
        <div>
          <label class="form-label">Password Saat Ini <span class="text-red-500">*</span></label>
          <div class="relative">
            <input type="password" id="verify-current-password" class="form-control pr-10"
                   placeholder="Masukkan password Anda" autocomplete="current-password">
            <button type="button" onclick="togglePasswordVisibility('verify-current-password', this)" tabindex="-1"
                    class="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600">
              <i data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-konfirmasi-kredensial')">Batal</button>
        <button type="button" class="btn btn-danger" onclick="konfirmasiPerubahanKredensial()">
          <i data-lucide="check"></i> Konfirmasi
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const USERNAME_LAMA = <?= json_encode($setting['admin_username'] ?? 'admin') ?>;

function handlePengaturanSubmit(e) {
  const usernameBaru = document.getElementById('input-username-baru').value.trim();
  const passwordBaru = document.getElementById('input-password-baru').value;
  const adaPerubahanKredensial = (usernameBaru && usernameBaru !== USERNAME_LAMA) || passwordBaru;
  if (adaPerubahanKredensial) {
    e.preventDefault();
    openModal('modal-konfirmasi-kredensial');
    return false;
  }
  return true;
}
function konfirmasiPerubahanKredensial() {
  const pass = document.getElementById('verify-current-password').value;
  if (!pass) { showToast('Password saat ini wajib diisi', 'error'); return; }
  document.getElementById('current-password-field').value = pass;
  closeModal('modal-konfirmasi-kredensial');
  document.getElementById('form-pengaturan').submit();
}
</script>
<?= $this->endSection() ?>
