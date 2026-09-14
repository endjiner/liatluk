<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameTablesToSingular extends Migration
{
    private array $renames = [
        'pemasukans'           => 'pemasukan',
        'pengeluarans'         => 'pengeluaran',
        'rencana_pemasukans'   => 'rencana_pemasukan',
        'rencana_pengeluarans' => 'rencana_pengeluaran',
        'notifikasis'          => 'notifikasi',
        'pengaturans'          => 'pengaturan',
    ];

    public function up()
    {
        // Ganti nama semua tabel: hapus akhiran "s" agar konsisten (idempotent, aman dijalankan ulang)
        foreach ($this->renames as $old => $new) {
            if ($this->db->tableExists($old) && !$this->db->tableExists($new)) {
                $this->forge->renameTable($old, $new);
            }
        }

        // Tambah kolom kategori untuk filter notifikasi (pemasukan/pengeluaran/rencana/sistem)
        if ($this->db->tableExists('notifikasi') && !$this->db->fieldExists('kategori', 'notifikasi')) {
            $this->forge->addColumn('notifikasi', [
                'kategori' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pemasukan', 'pengeluaran', 'rencana', 'sistem'],
                    'default'    => 'sistem',
                    'after'      => 'tipe',
                ],
            ]);
        }

        if ($this->db->tableExists('notifikasi')) {
            // Sintaks "MODIFY" khusus MySQL — tidak berlaku (dan tidak diperlukan) di driver lain.
            if ($this->db->DBDriver === 'MySQLi') {
                // Perluas enum tipe agar menampung notifikasi pengingat rencana
                $this->db->query(
                    "ALTER TABLE notifikasi MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','pengingat','info') DEFAULT 'info'"
                );
            }

            // Backfill data lama berdasarkan tipe yang sudah ada
            $this->db->query("UPDATE notifikasi SET kategori = 'rencana' WHERE tipe = 'rencana' AND kategori = 'sistem'");
            $this->db->query("UPDATE notifikasi SET kategori = 'pemasukan' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pemasukan%' AND kategori = 'sistem'");
            $this->db->query("UPDATE notifikasi SET kategori = 'pengeluaran' WHERE tipe = 'transaksi_besar' AND pesan LIKE '%pengeluaran%' AND kategori = 'sistem'");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('notifikasi')) {
            if ($this->db->fieldExists('kategori', 'notifikasi')) {
                $this->forge->dropColumn('notifikasi', 'kategori');
            }
            $this->db->query(
                "ALTER TABLE notifikasi MODIFY tipe ENUM('saldo_rendah','transaksi_besar','rencana','info') DEFAULT 'info'"
            );
        }

        foreach (array_reverse($this->renames) as $old => $new) {
            if ($this->db->tableExists($new) && !$this->db->tableExists($old)) {
                $this->forge->renameTable($new, $old);
            }
        }
    }
}
