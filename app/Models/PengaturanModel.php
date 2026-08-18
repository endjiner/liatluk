<?php

namespace App\Models;

use CodeIgniter\Model;

class PengaturanModel extends Model
{
    protected $table      = 'pengaturan';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'threshold_notif', 'admin_username',
        'admin_password', 'notif_saldo_rendah', 'notif_transaksi_besar'
    ];
    protected $useTimestamps = true;

    /**
     * Ambil satu baris pengaturan (singleton)
     */
    public function getSetting()
    {
        return $this->first();
    }

    /**
     * Update pengaturan (selalu row pertama)
     */
    public function updateSetting($data)
    {
        $existing = $this->first();
        if ($existing) {
            return $this->update($existing['id'], $data);
        }
        return $this->insert($data);
    }

    /**
     * Verifikasi password admin
     */
    public function verifyAdmin($username, $password)
    {
        $setting = $this->where('admin_username', $username)->first();
        if (!$setting) return false;
        return password_verify($password, $setting['admin_password']);
    }
}
