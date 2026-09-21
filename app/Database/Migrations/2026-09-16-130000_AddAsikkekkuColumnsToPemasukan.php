<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAsikkekkuColumnsToPemasukan extends Migration
{
    public function up()
    {
        // Menambahkan kolom relasional murni MariaDB untuk integrasi Asikkekku (tanpa JSON)
        $this->forge->addColumn('pemasukan', [
            'perjalanan_dinas_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'dari_tandai_lunas',
            ],
            'perjalanan_dinas_peserta_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'perjalanan_dinas_id',
            ],
            'no_surat_tugas' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'perjalanan_dinas_peserta_id',
            ],
            'kode_mak' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'no_surat_tugas',
            ],
            'no_spm' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'kode_mak',
            ],
        ]);

        $this->forge->addKey('perjalanan_dinas_id', false, false, 'idx_pemasukan_perjadin');
        $this->forge->addKey('perjalanan_dinas_peserta_id', false, false, 'idx_pemasukan_peserta');
        $this->forge->addKey('no_surat_tugas', false, false, 'idx_pemasukan_no_st');
        $this->forge->addKey('kode_mak', false, false, 'idx_pemasukan_kode_mak');
        $this->forge->addKey('no_spm', false, false, 'idx_pemasukan_no_spm');
        $this->forge->processIndexes('pemasukan');
    }

    public function down()
    {
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_perjadin');
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_peserta');
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_no_st');
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_kode_mak');
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_no_spm');

        $this->forge->dropColumn('pemasukan', [
            'perjalanan_dinas_id',
            'perjalanan_dinas_peserta_id',
            'no_surat_tugas',
            'kode_mak',
            'no_spm',
        ]);
    }
}
