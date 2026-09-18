<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pisahkan kredensial admin dari tabel pengaturan (config aplikasi) ke tabel
 * admin_akun sendiri, supaya bisa ada lebih dari satu akun dengan role
 * berbeda (super_admin vs admin). Akun admin lama di pengaturan (kalau ada)
 * dipindahkan otomatis jadi super_admin pertama supaya login yang sudah ada
 * tetap jalan tanpa perlu setup ulang.
 */
class CreateAdminAkun extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'role'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'admin'],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('admin_akun');

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('pengaturan');

        if (in_array('admin_username', $fields, true) && in_array('admin_password', $fields, true)) {
            $existing = $db->table('pengaturan')
                ->select('admin_username, admin_password')
                ->get()
                ->getRowArray();

            if ($existing && !empty($existing['admin_username']) && !empty($existing['admin_password'])) {
                $db->table('admin_akun')->insert([
                    'username'   => $existing['admin_username'],
                    'password'   => $existing['admin_password'],
                    'role'       => 'super_admin',
                    'aktif'      => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->forge->dropColumn('pengaturan', ['admin_username', 'admin_password']);
        }
    }

    public function down()
    {
        $this->forge->addColumn('pengaturan', [
            'admin_username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'default' => 'admin'],
            'admin_password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        $db = \Config\Database::connect();
        $superAdmin = $db->table('admin_akun')
            ->where('role', 'super_admin')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($superAdmin) {
            $db->table('pengaturan')->update([
                'admin_username' => $superAdmin['username'],
                'admin_password' => $superAdmin['password'],
            ]);
        }

        $this->forge->dropTable('admin_akun');
    }
}
