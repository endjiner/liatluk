<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJumlahTerealisasiToRencana extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rencana_pemasukan', [
            'jumlah_terealisasi' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0, 'after' => 'jumlah_rencana'],
        ]);
        $this->forge->addColumn('rencana_pengeluaran', [
            'jumlah_terealisasi' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0, 'after' => 'jumlah_rencana'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rencana_pemasukan', 'jumlah_terealisasi');
        $this->forge->dropColumn('rencana_pengeluaran', 'jumlah_terealisasi');
    }
}
