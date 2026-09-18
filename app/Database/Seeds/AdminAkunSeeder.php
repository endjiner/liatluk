<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminAkunSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'username'   => 'admin',
            'password'   => password_hash('bpom2026', PASSWORD_BCRYPT),
            'role'       => 'super_admin',
            'aktif'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('admin_akun')->insert($data);
    }
}
