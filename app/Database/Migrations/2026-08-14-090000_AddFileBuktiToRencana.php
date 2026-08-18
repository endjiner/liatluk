<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileBuktiToRencana extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rencana_pemasukan', [
            'file_bukti' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'keterangan'],
        ]);
        $this->forge->addColumn('rencana_pengeluaran', [
            'file_bukti' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'keterangan'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rencana_pemasukan', 'file_bukti');
        $this->forge->dropColumn('rencana_pengeluaran', 'file_bukti');
    }
}
