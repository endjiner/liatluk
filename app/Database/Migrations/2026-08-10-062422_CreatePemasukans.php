<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePemasukans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal'       => ['type' => 'DATE', 'null' => false],
            'kategori'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'jumlah'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'jumlah_diterima' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'status_dana'   => ['type' => 'ENUM', 'constraint' => ['diterima', 'sebagian', 'belum_diterima'], 'default' => 'belum_diterima'],
            'sumber'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'keterangan'    => ['type' => 'TEXT', 'null' => true],
            'file_bukti'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pemasukan');
    }

    public function down()
    {
        $this->forge->dropTable('pemasukan');
    }
}
