<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDariTandaiLunasToPemasukan extends Migration
{
    public function up()
    {
        // Bedakan Pemasukan yang di-insert OTOMATIS oleh tandaiLunas() (aman dihapus balik
        // kalau lunasnya dibatalkan) dari Pemasukan yang sudah ADA SEBELUMNYA dan cuma
        // ditautkan ke peserta (mis. lewat app:reconcile-setoran-taktis, atau tautan manual
        // ke entri lama) — yang terakhir ini data keuangan asli, tidak boleh ikut terhapus
        // hanya karena tautannya dibatalkan. Default 0 supaya semua baris lama (termasuk yang
        // sudah ditautkan reconciler) otomatis dianggap "bukan auto-generate", aman.
        $this->forge->addColumn('pemasukan', [
            'dari_tandai_lunas' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false, 'after' => 'catatan_internal'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pemasukan', 'dari_tandai_lunas');
    }
}
