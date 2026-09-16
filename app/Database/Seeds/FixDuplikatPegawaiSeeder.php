<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Gabungkan pegawai duplikat — orang yang sama tapi ke-input sebagai 2+ baris terpisah di
 * master data Pegawai (baik dari import data historis maupun ditambahkan manual belakangan
 * lewat "Kelola Pegawai" dengan format nama/gelar yang beda). Dicocokkan by NAMA (bukan id)
 * supaya aman dijalankan di database mana pun, dan aman dijalankan berkali-kali (no-op kalau
 * nama duplikatnya sudah tidak ada — mis. sudah pernah digabung sebelumnya).
 *
 * PENTING: selain memindahkan pegawai_id di perjalanan_dinas_peserta (kalau baris pegawai
 * duplikatnya masih ada), versi ini SELALU JUGA memperbaiki nama_peserta (snapshot nama
 * yang di-cache saat peserta disimpan) dan sumber di Pemasukan yang tertaut (lewat
 * pemasukan_id) dengan mencari langsung berdasarkan TEKS nama lama — supaya nama yang
 * tampil di Daftar Transaksi & Dana Taktis konsisten dengan nama kanonik pegawai, baik
 * untuk penggabungan baru maupun penggabungan LAMA yang barisnya sudah kadung terhapus
 * sebelum perbaikan snapshot ini ada (mis. "Alvindra" -> "Alvindra Pratama" sebelumnya).
 *
 * Jalankan setiap kali ada pasangan duplikat baru ditemukan (lihat app:cari-duplikat-pegawai):
 *   php spark db:seed FixDuplikatPegawaiSeeder
 */
class FixDuplikatPegawaiSeeder extends Seeder
{
    /** [nama duplikat => nama kanonik yang benar/paling lengkap] */
    private const ALIAS = [
        'Alvindra' => 'Alvindra Pratama',
        'Muchtar Ganda Taruna' => 'Muchtar Ghanda Taruna',
        'Rizki Arfiyanti' => 'Rizki Afriyanti',
        'sony' => 'Sony Lawrensia',
    ];

    public function run()
    {
        foreach (self::ALIAS as $duplikat => $kanonik) {
            $dup = $this->db->table('pegawai')->where('nama', $duplikat)->get()->getRowArray();

            if ($dup) {
                $canon = $this->db->table('pegawai')->where('nama', $kanonik)->get()->getRowArray();
                if (!$canon) {
                    echo "Lewati '{$duplikat}' — baris pegawainya ada tapi nama kanonik '{$kanonik}' tidak ditemukan, tidak digabung.\n";
                    continue;
                }
                $this->db->table('perjalanan_dinas_peserta')->where('pegawai_id', $dup['id'])->update(['pegawai_id' => $canon['id']]);
                $this->db->table('pegawai')->where('id', $dup['id'])->delete();
                echo "Gabung baris pegawai '{$duplikat}' (id {$dup['id']}) -> '{$kanonik}' (id {$canon['id']}).\n";
            }

            // Perbaiki snapshot nama_peserta yang masih nyantol ke nama lama — baik dari
            // penggabungan pegawai_id barusan di atas, maupun dari penggabungan versi
            // SEBELUMNYA (baris pegawai duplikatnya sudah tidak ada lagi, tapi teks nama
            // lamanya masih ke-cache di riwayat peserta karena versi lama seeder ini belum
            // memperbaiki snapshot-nya).
            $pesertaLama = $this->db->table('perjalanan_dinas_peserta')
                ->where('nama_peserta', $duplikat)
                ->get()->getResultArray();

            foreach ($pesertaLama as $p) {
                $this->db->table('perjalanan_dinas_peserta')->where('id', $p['id'])->update(['nama_peserta' => $kanonik]);
                if (!empty($p['pemasukan_id'])) {
                    $this->db->table('pemasukan')->where('id', $p['pemasukan_id'])->update(['sumber' => $kanonik]);
                }
            }

            if (!empty($pesertaLama)) {
                echo "Segarkan nama '{$duplikat}' -> '{$kanonik}': " . count($pesertaLama) . " baris peserta (+ Pemasukan tertaut kalau ada).\n";
            } elseif (!$dup) {
                echo "Lewati '{$duplikat}' — tidak ada baris pegawai maupun riwayat nama lama, sepertinya memang sudah bersih.\n";
            }
        }
    }
}
