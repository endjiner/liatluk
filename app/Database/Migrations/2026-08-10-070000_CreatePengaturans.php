<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengaturans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'saldo_awal'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'threshold_notif'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 5000000],
            'admin_username'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'default' => 'admin'],
            'admin_password'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'notif_saldo_rendah'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notif_transaksi_besar' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pengaturan');
    }

    public function down()
    {
        $this->forge->dropTable('pengaturan');
    }
}
