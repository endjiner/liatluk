<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminAkunModel;

/**
 * Aksi akun untuk diri sendiri (bukan mengelola akun lain) — dipisah dari
 * Admin\AdminAkun supaya bisa diakses kedua role (super_admin maupun admin
 * terbatas), tanpa ikut kena filter 'superadmin'.
 */
class Akun extends BaseController
{
    public function gantiPasswordSaya()
    {
        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword     = (string) $this->request->getPost('new_password');

        if (empty($currentPassword) || empty($newPassword)) {
            return redirect()->back()->with('error', 'Password saat ini dan password baru wajib diisi.');
        }
        if (strlen($newPassword) < 6) {
            return redirect()->back()->with('error', 'Password baru minimal 6 karakter.');
        }

        $adminAkunModel = new AdminAkunModel();
        $username = (string) session()->get('admin_username');
        $akun = $adminAkunModel->verifyLogin($username, $currentPassword);

        if ($akun === false) {
            return redirect()->back()->with('error', 'Password saat ini salah — password tidak diubah.');
        }

        $adminAkunModel->update($akun['id'], ['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }
}
