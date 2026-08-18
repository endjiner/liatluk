<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Index tambahan untuk query yang berjalan di HAMPIR SETIAP request (badge notifikasi
 * di topbar, dashboard admin) tapi belum punya index pendukung — di skala ribuan baris
 * ini jadi full table scan berulang-ulang. Ditambahkan menjelang go-live, sebelum data
 * bertumbuh jauh melebihi ukuran saat ini.
 */
class AddScaleIndexes extends Migration
{
    public function up()
    {
        // notifikasi: where('is_read', 0) + orderBy('created_at') dipanggil di topbar HAMPIR
        // SETIAP halaman admin (badge unread) — query paling sering dieksekusi di seluruh app.
        $this->forge->addKey(['is_read', 'created_at'], false, false, 'idx_notif_isread_created');
        $this->forge->processIndexes('notifikasi');

        // rencana_pemasukan/pengeluaran: where('status','aktif') [+ where('tanggal_rencana', ...)
        // untuk pengecekan pengingat jatuh tempo] dipanggil di setiap load dashboard admin.
        $this->forge->addKey(['status', 'tanggal_rencana'], false, false, 'idx_rencana_pemasukan_status_tgl');
        $this->forge->processIndexes('rencana_pemasukan');

        $this->forge->addKey(['status', 'tanggal_rencana'], false, false, 'idx_rencana_pengeluaran_status_tgl');
        $this->forge->processIndexes('rencana_pengeluaran');

        // pemasukan: whereIn('status_dana', [...]) di getTotalBelumDiterima(), dipanggil
        // setiap load dashboard admin.
        $this->forge->addKey('status_dana', false, false, 'idx_pemasukan_status_dana');
        $this->forge->processIndexes('pemasukan');
    }

    public function down()
    {
        $this->forge->dropKey('notifikasi', 'idx_notif_isread_created');
        $this->forge->dropKey('rencana_pemasukan', 'idx_rencana_pemasukan_status_tgl');
        $this->forge->dropKey('rencana_pengeluaran', 'idx_rencana_pengeluaran_status_tgl');
        $this->forge->dropKey('pemasukan', 'idx_pemasukan_status_dana');
    }
}
