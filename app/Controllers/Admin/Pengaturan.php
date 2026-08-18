<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PengaturanModel;
use App\Models\NotifikasiModel;

class Pengaturan extends BaseController
{
    protected $pengaturanModel;
    protected $notifikasiModel;

    public function __construct()
    {
        $this->pengaturanModel = new PengaturanModel();
        $this->notifikasiModel = new NotifikasiModel();
    }

    public function index(): string
    {
        $notifCount = $this->notifikasiModel->countUnread();
        return view('admin/pengaturan', [
            'setting'    => $this->pengaturanModel->getSetting(),
            'notifCount' => $notifCount,
        ]);
    }

    public function update()
    {
        // Normalisasi threshold_notif (input rupiah bisa masuk sebagai "1.000.000")
        $threshold = $this->request->getPost('threshold_notif');
        if ($threshold !== null && !is_numeric($threshold)) {
            $s = (string)$threshold;
            if (preg_match('/^([\d.]+),(\d{1,2})$/', $s, $m)) {
                $threshold = (float)(str_replace('.', '', $m[1]) . '.' . $m[2]);
            } else {
                $threshold = (float)str_replace(['.', ','], ['', ''], $s);
            }
        }

        $data = [
            'threshold_notif'       => $threshold,
            'notif_saldo_rendah'    => $this->request->getPost('notif_saldo_rendah') ? 1 : 0,
            'notif_transaksi_besar' => $this->request->getPost('notif_transaksi_besar') ? 1 : 0,
        ];

        // Update username/password jika benar-benar berubah — aksi kritikal, wajib verifikasi password saat ini
        $newUsername = $this->request->getPost('admin_username');
        $newPassword = $this->request->getPost('admin_password');
        $currentPassword = $this->request->getPost('current_password');
        $existing = $this->pengaturanModel->getSetting();
        $usernameBerubah = !empty($newUsername) && $newUsername !== ($existing['admin_username'] ?? '');

        if ($usernameBerubah || !empty($newPassword)) {
            $currentUsername = session()->get('admin_username');
            if (empty($currentPassword) || !$this->pengaturanModel->verifyAdmin($currentUsername, $currentPassword)) {
                return redirect()->back()->with('error', 'Password saat ini salah — perubahan username/password dibatalkan.');
            }
            if ($usernameBerubah) {
                $data['admin_username'] = $newUsername;
            }
            if (!empty($newPassword)) {
                $data['admin_password'] = password_hash($newPassword, PASSWORD_BCRYPT);
            }
        }

        $this->pengaturanModel->updateSetting($data);

        // Update session username jika berubah
        if ($usernameBerubah) {
            session()->set('admin_username', $newUsername);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
