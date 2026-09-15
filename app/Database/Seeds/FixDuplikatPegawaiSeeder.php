<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Gabungkan pegawai duplikat hasil import data historis (PerjalananDinasHistorisSeeder)
 * — nama yang sama tapi tertulis beda di baris spreadsheet yang berbeda, sehingga sempat
 * ke-import sebagai 2 baris pegawai terpisah. Dicocokkan by NAMA (bukan id) supaya aman
 * dijalankan di database mana pun, dan aman dijalankan berkali-kali (no-op kalau nama
 * duplikatnya sudah tidak ada).
 *
 * Jalankan sekali setelah PerjalananDinasHistorisSeeder pernah dijalankan sebelum
 * perbaikan ini ada: php spark db:seed FixDuplikatPegawaiSeeder
 */
class FixDuplikatPegawaiSeeder extends Seeder
{
    /** [nama duplikat => nama kanonik yang benar] */
    private const ALIAS = [
        'Alvindra' => 'Alvindra Pratama',
    ];

    public function run()
    {
        foreach (self::ALIAS as $duplikat => $kanonik) {
            $dup = $this->db->table('pegawai')->where('nama', $duplikat)->get()->getRowArray();
            if (!$dup) {
                echo "Lewati '{$duplikat}' — tidak ditemukan (mungkin sudah pernah digabung).\n";
                continue;
            }

            $canon = $this->db->table('pegawai')->where('nama', $kanonik)->get()->getRowArray();
            if (!$canon) {
                echo "Lewati '{$duplikat}' — nama kanonik '{$kanonik}' tidak ditemukan, tidak digabung.\n";
                continue;
            }

            $this->db->table('perjalanan_dinas_peserta')
                ->where('pegawai_id', $dup['id'])
                ->update(['pegawai_id' => $canon['id']]);
            $jumlahDipindah = $this->db->affectedRows();

            $this->db->table('pegawai')->where('id', $dup['id'])->delete();

            echo "Gabung '{$duplikat}' (id {$dup['id']}) -> '{$kanonik}' (id {$canon['id']}), {$jumlahDipindah} baris peserta dipindah.\n";
        }
    }
}
