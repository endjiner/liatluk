<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerjalananDinasPeserta extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'perjalanan_dinas_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'pegawai_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            // Snapshot nama saat disimpan — supaya riwayat tidak berubah/rusak kalau data
            // master pegawai belakangan diedit atau dihapus.
            'nama_peserta'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'uang_harian'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'meeting_fullboard'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'meeting_fullday'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'uang_representasi'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'transport_lokal'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'bbm'                 => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            // Kolom hasil hitung (di-cache di kolom, dihitung ulang tiap kali disimpan) —
            // dibaca terus-menerus di rekap Dana Taktis, lebih murah daripada JOIN+SUM tiket
            // & hotel setiap kali halaman itu dibuka.
            'total_spj'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'dana_taktis'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'status_lunas'        => ['type' => 'ENUM', 'constraint' => ['belum', 'lunas'], 'default' => 'belum'],
            'tanggal_lunas'       => ['type' => 'DATE', 'null' => true],
            // Referensi ke pemasukan yang otomatis dibuat saat ditandai lunas, supaya bisa
            // dibatalkan/dihapus balik kalau status lunas-nya dibatalkan (lihat toggleLunas()).
            'pemasukan_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('perjalanan_dinas_id');
        $this->forge->addKey('pegawai_id');
        $this->forge->addKey('status_lunas');
        $this->forge->createTable('perjalanan_dinas_peserta');
    }

    public function down()
    {
        $this->forge->dropTable('perjalanan_dinas_peserta');
    }
}
