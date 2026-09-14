<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerjalananDinasTiket extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'peserta_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'maskapai'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'arah'            => ['type' => 'ENUM', 'constraint' => ['pergi', 'pulang'], 'null' => true],
            'no_tiket'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kode_booking'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'no_penerbangan'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tempat_asal'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tempat_tujuan'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tanggal_terbang' => ['type' => 'DATE', 'null' => true],
            'harga_tiket'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('peserta_id');
        $this->forge->createTable('perjalanan_dinas_tiket');
    }

    public function down()
    {
        $this->forge->dropTable('perjalanan_dinas_tiket');
    }
}
