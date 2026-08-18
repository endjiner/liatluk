<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifikasis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pesan'      => ['type' => 'TEXT', 'null' => false],
            'tipe'       => ['type' => 'ENUM', 'constraint' => ['saldo_rendah', 'transaksi_besar', 'rencana', 'pengingat', 'info'], 'default' => 'info'],
            'kategori'   => ['type' => 'ENUM', 'constraint' => ['pemasukan', 'pengeluaran', 'rencana', 'sistem'], 'default' => 'sistem'],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('notifikasi');
    }

    public function down()
    {
        $this->forge->dropTable('notifikasi');
    }
}
