<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRencanaTables extends Migration
{
    public function up()
    {
        $this->forge->dropTable('rencana_pemasukan', true);
        $this->forge->dropTable('rencana_pengeluaran', true);
    }

    public function down()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal_rencana'    => ['type' => 'DATE', 'null' => false],
            'kategori'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'jumlah_rencana'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'jumlah_terealisasi' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'keterangan'         => ['type' => 'TEXT', 'null' => true],
            'file_bukti'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['aktif', 'terealisasi'], 'default' => 'aktif'],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rencana_pemasukan');

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal_rencana'    => ['type' => 'DATE', 'null' => false],
            'kategori'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'jumlah_rencana'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'jumlah_terealisasi' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'keterangan'         => ['type' => 'TEXT', 'null' => true],
            'file_bukti'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['aktif', 'terealisasi'], 'default' => 'aktif'],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rencana_pengeluaran');
    }
}
