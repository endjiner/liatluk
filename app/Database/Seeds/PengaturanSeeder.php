<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'threshold_notif'       => 5000000,
            'admin_username'        => 'admin',
            'admin_password'        => password_hash('bpom2026', PASSWORD_BCRYPT),
            'notif_saldo_rendah'    => 1,
            'notif_transaksi_besar' => 1,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        $this->db->table('pengaturan')->insert($data);
    }
}
