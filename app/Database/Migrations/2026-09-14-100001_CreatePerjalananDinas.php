<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerjalananDinas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'maksud'              => ['type' => 'TEXT', 'null' => false],
            'no_surat_tugas'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_surat_tugas' => ['type' => 'DATE', 'null' => false],
            'kode_mak'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_spm'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal_surat_tugas');
        $this->forge->createTable('perjalanan_dinas');
    }

    public function down()
    {
        $this->forge->dropTable('perjalanan_dinas');
    }
}
