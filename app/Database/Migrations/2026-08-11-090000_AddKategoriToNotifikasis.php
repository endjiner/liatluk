<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKategoriToNotifikasis extends Migration
{
    public function up()
    {
        // Guard: pada instalasi baru, CreateNotifikasis sudah langsung menyertakan kolom
        // kategori sejak awal — tanpa pengecekan ini, addColumn di bawah akan gagal dengan
        // "duplicate column" pada fresh install (kolomnya cuma perlu ditambah di DB lama).
        if (!$this->db->fieldExists('kategori', 'notifikasi')) {
            $this->forge->addColumn('notifikasi', [
                'kategori' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pemasukan', 'pengeluaran', 'rencana', 'sistem'],
                    'default'    => 'sistem',
                    'after'      => 'tipe',
                ],
            ]);
        }

        // Sintaks "MODIFY" khusus MySQL — tidak berlaku (dan tidak diperlukan) di driver lain.
        if ($this->db->DBDriver === 'MySQLi') {
            // Perluas enum tipe agar menampung notifikasi pengingat rencana
            $this->db->query(
                "ALTER TABLE notifikasi MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','pengingat','info') DEFAULT 'info'"
            );
        }

        // Backfill data lama berdasarkan tipe yang sudah ada
        $this->db->query("UPDATE notifikasi SET kategori = 'rencana' WHERE tipe = 'rencana'");
        $this->db->query("UPDATE notifikasi SET kategori = 'pemasukan' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pemasukan%'");
        $this->db->query("UPDATE notifikasi SET kategori = 'pengeluaran' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pengeluaran%'");
    }

    public function down()
    {
        if ($this->db->fieldExists('kategori', 'notifikasi')) {
            $this->forge->dropColumn('notifikasi', 'kategori');
        }
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query(
                "ALTER TABLE notifikasi MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','info') DEFAULT 'info'"
            );
        }
    }
}
