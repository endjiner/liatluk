<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PegawaiModel;

/**
 * Cari kandidat pegawai duplikat (orang yang sama tapi ke-input sebagai 2+ baris terpisah
 * di master data Pegawai) — mis. "Ika Kartika" vs "Ika Kartika, S.T.P", atau typo kecil
 * seperti "Muchtar Ganda Taruna" vs "Muchtar Ghanda Taruna". HANYA MELAPORKAN, tidak
 * mengubah data apa pun — supaya admin yang kenal orangnya langsung bisa konfirmasi mana
 * yang benar-benar orang yang sama, sebelum digabung lewat FixDuplikatPegawaiSeeder.
 *
 * Dua jenis kecocokan yang dicari:
 *  1. "Nama inti" sama persis setelah gelar/derajat di belakang koma dibuang (pola umum
 *     penulisan nama pegawai negeri: "Nama, Gelar1, Gelar2, ..."), atau salah satu nama
 *     adalah AWALAN dari nama lain (mis. "Ika Kartika" adalah awalan "Ika Kartika, S.T.P").
 *  2. Nama initinya sangat mirip (jarak Levenshtein kecil dibanding panjang nama) —
 *     menangkap typo/variasi ejaan/spasi, bukan soal gelar.
 *
 * Jalankan: php spark app:cari-duplikat-pegawai
 */
class CariDuplikatPegawai extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:cari-duplikat-pegawai';
    protected $description = 'Laporkan (tanpa mengubah data) kandidat pegawai duplikat berdasarkan kemiripan nama.';

    /** Nama inti: buang semua setelah koma pertama (gelar), rapikan spasi & kapitalisasi. */
    private function namaInti(string $nama): string
    {
        $inti = trim(explode(',', $nama)[0]);
        $inti = preg_replace('/\s+/', ' ', $inti);
        return mb_strtolower($inti);
    }

    public function run(array $params)
    {
        $pegawai = (new PegawaiModel())->orderBy('nama', 'ASC')->findAll();
        CLI::write('=== Cari Duplikat Pegawai ===', 'yellow');
        CLI::write('Total pegawai di database: ' . count($pegawai));
        CLI::newLine();

        $kandidat = [];
        for ($i = 0; $i < count($pegawai); $i++) {
            for ($j = $i + 1; $j < count($pegawai); $j++) {
                $a = $pegawai[$i];
                $b = $pegawai[$j];
                $intiA = $this->namaInti($a['nama']);
                $intiB = $this->namaInti($b['nama']);

                $alasan = null;
                if ($intiA === $intiB) {
                    $alasan = 'nama inti sama persis (beda gelar/format)';
                } elseif (str_starts_with($intiA, $intiB . ' ') || str_starts_with($intiB, $intiA . ' ')) {
                    $alasan = 'satu nama adalah awalan dari nama lain';
                } else {
                    $jarak = levenshtein($intiA, $intiB);
                    $panjangMin = min(strlen($intiA), strlen($intiB));
                    if ($panjangMin >= 8 && $jarak > 0 && $jarak <= 2) {
                        $alasan = "mirip banget, kemungkinan typo (jarak edit {$jarak})";
                    }
                }

                if ($alasan !== null) {
                    $kandidat[] = [$a, $b, $alasan];
                }
            }
        }

        if (empty($kandidat)) {
            CLI::write('Tidak ditemukan kandidat duplikat.', 'green');
            return;
        }

        CLI::write('Ditemukan ' . count($kandidat) . ' pasangan kandidat — CEK MANUAL sebelum digabung, ini cuma dugaan berdasarkan kemiripan nama:', 'yellow');
        CLI::newLine();
        foreach ($kandidat as [$a, $b, $alasan]) {
            CLI::write("  '{$a['nama']}' (id {$a['id']})  <->  '{$b['nama']}' (id {$b['id']})", 'white');
            CLI::write("    alasan: {$alasan}", 'dark_gray');
        }
        CLI::newLine();
        CLI::write('Kalau memang orang yang sama: tambahkan pasangannya ke ALIAS di FixDuplikatPegawaiSeeder', 'cyan');
        CLI::write('(nama yang KURANG lengkap => nama yang PALING lengkap/baku), lalu jalankan:', 'cyan');
        CLI::write('  php spark db:seed FixDuplikatPegawaiSeeder', 'cyan');
    }
}
