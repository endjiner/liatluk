<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penambahan index untuk mendukung query volume besar (scalability) pada
 * tabel perjalanan_dinas dan perjalanan_dinas_peserta (idempotent).
 */
class AddPerjadinAndAsikkekkuScaleIndexes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Indeks pada tabel perjalanan_dinas
        $idxPerjadin = array_column($db->query("SHOW INDEX FROM perjalanan_dinas")->getResultArray(), 'Key_name');
        if (!in_array('idx_perjadin_no_surat_tugas', $idxPerjadin, true)) {
            $this->forge->addKey('no_surat_tugas', false, false, 'idx_perjadin_no_surat_tugas');
            $this->forge->processIndexes('perjalanan_dinas');
        }
        if (!in_array('idx_perjadin_kode_mak', $idxPerjadin, true)) {
            $this->forge->addKey('kode_mak', false, false, 'idx_perjadin_kode_mak');
            $this->forge->processIndexes('perjalanan_dinas');
        }

        // 2. Indeks pada tabel perjalanan_dinas_peserta
        $idxPeserta = array_column($db->query("SHOW INDEX FROM perjalanan_dinas_peserta")->getResultArray(), 'Key_name');
        if (!in_array('idx_peserta_pemasukan_id', $idxPeserta, true)) {
            $this->forge->addKey('pemasukan_id', false, false, 'idx_peserta_pemasukan_id');
            $this->forge->processIndexes('perjalanan_dinas_peserta');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $idxPerjadin = array_column($db->query("SHOW INDEX FROM perjalanan_dinas")->getResultArray(), 'Key_name');
        if (in_array('idx_perjadin_no_surat_tugas', $idxPerjadin, true)) {
            $this->forge->dropKey('perjalanan_dinas', 'idx_perjadin_no_surat_tugas');
        }
        if (in_array('idx_perjadin_kode_mak', $idxPerjadin, true)) {
            $this->forge->dropKey('perjalanan_dinas', 'idx_perjadin_kode_mak');
        }

        $idxPeserta = array_column($db->query("SHOW INDEX FROM perjalanan_dinas_peserta")->getResultArray(), 'Key_name');
        if (in_array('idx_peserta_pemasukan_id', $idxPeserta, true)) {
            $this->forge->dropKey('perjalanan_dinas_peserta', 'idx_peserta_pemasukan_id');
        }
    }
}
