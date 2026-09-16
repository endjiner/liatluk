<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSebagianStatusToPerjalananDinasPeserta extends Migration
{
    public function up()
    {
        // Dana Taktis sekarang bisa disetor bertahap, bukan cuma lunas/belum — mirror pola
        // status_dana pada tabel pemasukan (diterima/sebagian/belum_diterima). jumlah_disetor
        // menyimpan berapa yang sudah masuk sejauh ini (0 untuk 'belum', = dana_taktis untuk
        // 'lunas'), supaya sisa setoran gampang dihitung tanpa perlu baca ulang Pemasukan.
        $this->forge->addColumn('perjalanan_dinas_peserta', [
            'jumlah_disetor' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0, 'after' => 'dana_taktis'],
        ]);

        $this->forge->modifyColumn('perjalanan_dinas_peserta', [
            'status_lunas' => ['type' => 'ENUM', 'constraint' => ['belum', 'sebagian', 'lunas'], 'default' => 'belum'],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('perjalanan_dinas_peserta', [
            'status_lunas' => ['type' => 'ENUM', 'constraint' => ['belum', 'lunas'], 'default' => 'belum'],
        ]);

        $this->forge->dropColumn('perjalanan_dinas_peserta', 'jumlah_disetor');
    }
}
