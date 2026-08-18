<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCatatanInternalAndTanggalIndex extends Migration
{
    public function up()
    {
        // Kolom catatan internal (admin only) — sudah ada di form Input Data Baru tapi belum punya kolom
        $this->forge->addColumn('pemasukan', [
            'catatan_internal' => ['type' => 'TEXT', 'null' => true, 'after' => 'keterangan'],
        ]);
        $this->forge->addColumn('pengeluaran', [
            'catatan_internal' => ['type' => 'TEXT', 'null' => true, 'after' => 'keterangan'],
        ]);

        // Index pada tanggal supaya filter periode (tahun/bulan) tidak full table scan
        $this->forge->addKey('tanggal', false, false, 'idx_pemasukan_tanggal');
        $this->forge->processIndexes('pemasukan');

        $this->forge->addKey('tanggal', false, false, 'idx_pengeluaran_tanggal');
        $this->forge->processIndexes('pengeluaran');
    }

    public function down()
    {
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_tanggal');
        $this->forge->dropKey('pengeluaran', 'idx_pengeluaran_tanggal');

        $this->forge->dropColumn('pemasukan', 'catatan_internal');
        $this->forge->dropColumn('pengeluaran', 'catatan_internal');
    }
}
