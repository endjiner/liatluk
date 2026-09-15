<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerjalananDinasHotel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'peserta_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'nama_hotel'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'alamat_hotel'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'telp_hotel'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'checkin'               => ['type' => 'DATE', 'null' => true],
            'checkout'              => ['type' => 'DATE', 'null' => true],
            'total_bill'            => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'no_kamar'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'no_invoice'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'total_biaya_30persen'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('peserta_id');
        $this->forge->createTable('perjalanan_dinas_hotel');
    }

    public function down()
    {
        $this->forge->dropTable('perjalanan_dinas_hotel');
    }
}
