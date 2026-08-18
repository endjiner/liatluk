<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKategoriToNotifikasis extends Migration
{
    public function up()
    {
        // Tambah kolom kategori untuk filter notifikasi (pemasukan/pengeluaran/rencana/sistem)
        $this->forge->addColumn('notifikasis', [
            'kategori' => [
                'type'       => 'ENUM',
                'constraint' => ['pemasukan', 'pengeluaran', 'rencana', 'sistem'],
                'default'    => 'sistem',
                'after'      => 'tipe',
            ],
        ]);

        // Perluas enum tipe agar menampung notifikasi pengingat rencana
        $this->db->query(
            "ALTER TABLE notifikasis MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','pengingat','info') DEFAULT 'info'"
        );

        // Backfill data lama berdasarkan tipe yang sudah ada
        $this->db->query("UPDATE notifikasis SET kategori = 'rencana' WHERE tipe = 'rencana'");
        $this->db->query("UPDATE notifikasis SET kategori = 'pemasukan' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pemasukan%'");
        $this->db->query("UPDATE notifikasis SET kategori = 'pengeluaran' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pengeluaran%'");
    }

    public function down()
    {
        $this->forge->dropColumn('notifikasis', 'kategori');
        $this->db->query(
            "ALTER TABLE notifikasis MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','info') DEFAULT 'info'"
        );
    }
}
