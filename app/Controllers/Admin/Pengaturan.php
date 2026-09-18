<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PengaturanModel;
use App\Models\NotifikasiModel;
use App\Models\AdminAkunModel;

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
        $daftarAkun = array_map(function ($a) {
            unset($a['password']);
            return $a;
        }, (new AdminAkunModel())->getAllAkun());

        return view('admin/pengaturan', [
            'setting'    => $this->pengaturanModel->getSetting(),
            'daftarAkun' => $daftarAkun,
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

        $this->pengaturanModel->updateSetting($data);

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
