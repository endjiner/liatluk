<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PegawaiModel;

/**
 * Isi kolom NIP pegawai dari data resmi di sheet "NAMA" pada
 * "A. Rekap Perjalanan Dinas 2026 - OTOMATIS.xlsx" (diberikan langsung oleh pengguna,
 * 56 baris nama+NIP). Dicocokkan by NAMA PERSIS ke tabel pegawai — TIDAK menebak/fuzzy-match,
 * supaya tidak salah pasang NIP ke orang yang salah. Nama yang tidak ketemu persis dilaporkan
 * terpisah supaya bisa dicek manual (mis. karena beda ejaan/singkatan nama belakang).
 *
 * Tidak pernah menimpa NIP yang sudah keisi (beda dari sumber) tanpa dilaporkan — kalau
 * pegawai sudah punya NIP yang beda, itu ikut dilaporkan sebagai "NIP BEDA" alih-alih
 * langsung ditimpa diam-diam.
 *
 * Dry-run secara default. Jalankan dengan --apply untuk benar-benar menyimpan.
 */
class IsiNipPegawai extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:isi-nip-pegawai';
    protected $description = 'Isi NIP pegawai dari data resmi sheet NAMA (cocok nama persis) — dry-run default.';
    protected $usage       = 'app:isi-nip-pegawai [--apply]';
    protected $options     = [
        '--apply' => 'Simpan perubahan (bukan cuma menampilkan usulan).',
    ];

    /** [nama (persis seperti di sheet NAMA) => NIP] */
    private const DATA = [
        ['Alex Sander', '19791212 200604 1 005'],
        ['Mardianto', '19830326 200812 1 001'],
        ['Ria Emeilia', '19840523 200812 2 001'],
        ['Welinda Syafri', '19840111 200912 2 003'],
        ['Tri Astuti Rahmawati', '19840902 200912 2 002'],
        ['Sony Lawrensia', '19840704 200912 2 002'],
        ['Prawita Lintang Larasati', '19880823 201402 2 002'],
        ['Pratiwi Setianingsih', '19891128 201402 2 003'],
        ['Puspa Sari Paniti Ratri', '19870123 201502 2 001'],
        ['Muhammad Herpi Akbar', '19931113 201903 1 002'],
        ['Wedita Destriani', '19940224 201903 2 007'],
        ['Rachmadiniarni', '19820625 201402 2 001'],
        ['Frenandha Dwi Dharmawan', '19910202 201502 1 004'],
        ['Atika Melati', '19941110 201903 2 006'],
        ['Ahmad Burhan Rifa\'i', '19890517 201502 1 004'],
        ['Ririn Suprihanti', '19860505 200912 2 004'],
        ['Marruni Zariah', '19870320 200812 2 003'],
        ['Anita Indriyastuti', '19851022 200812 2 002'],
        ['Lia Tri Wahyuni', '19880113 200812 2 001'],
        ['Indita Tiara Puspa', '19870315 200812 2 002'],
        ['Riantika Kurniati', '19880217 200912 2 003'],
        ['Priya Tri Nanda', '19950322 201903 1 004'],
        ['Ika Kartika', '19861002 200912 2 002'],
        ['Kafidul Ulum', '19920517 201903 1 007'],
        ['Rizki Afriyanti', '19970803 201903 2 001'],
        ['Nungki Nuari Dewi', '19960720 201903 2 003'],
        ['Rizky Rahmadhani Muslim', '19960217 202203 2 004'],
        ['Hudana Alam Putra', '19960107 201903 1 003'],
        ['Tiara Rani', '19960920 202203 2 004'],
        ['Angela Marselly Br Barus', '19970325 202203 2 002'],
        ['Veni Oktivia', '19931017 201903 2 004'],
        ['Ade Yan Emerson', '19880114 201012 1 002'],
        ['Fatrisia Ratnasari', '19960512 201903 2 007'],
        ['Frans Eryxon Ambarita', '19840807 200712 1 001'],
        ['Muhammad Apriadi', '19870428 200912 1 001'],
        ['Hana Mardiah', '19940802 202012 2 002'],
        ['Netty Desi Margaretta Manullang', '19931203 202012 2 001'],
        ['Rizky Wirani', '19950516 202203 2 001'],
        ['Riska Widiyana', '19921213 202203 2 001'],
        ['Ina Miranti', '19981115 202203 2 002'],
        ['Septo Dwi Yan Purnomo', '19920914 201502 1 001'],
        ['Talia Lucluba Madina', '19960315 202506 2 005'],
        ['Alvindra Pratama', '19940925 202506 1 004'],
        ['Yogi Hasudungan', '19950425 202506 1 004'],
        ['Lukman Hakim Rachmawan', '20000408 202506 1 010'],
        ['Al Ihya Yunus Putri', '19951231 201903 2 005'],
        ['Nabila Mukhriza', '19980509 202012 2 002'],
        ['Tio Lestarina M Hutajulu', '19880604 202012 2 001'],
        ['Muchtar Ghanda Taruna', '19940827 202506 1 001'],
        ['Dani Pena Prianto', '19920630 202506 1 002'],
        ['Donny Tua Hamonangan', '19900428 202421 1 008'],
        ['Atik Riwati', '19940330 202521 2 028'],
        ['Syahri Syarif', '19940223 202521 1 023'],
        ['Dedi Afriyansyah', '19881209 202521 1 012'],
        ['M. Rusdi', '19900218 202521 1 013'],
        ['Agus Riyanto', '19900219 202521 1 013'],
    ];

    public function run(array $params)
    {
        $apply = array_key_exists('apply', $params) || CLI::getOption('apply');
        $pegawaiModel = new PegawaiModel();

        $diisi = [];
        $sudahSama = [];
        $nipBeda = [];
        $tidakKetemu = [];

        foreach (self::DATA as [$nama, $nip]) {
            $row = $pegawaiModel->where('nama', $nama)->first();
            if (!$row) {
                $tidakKetemu[] = [$nama, $nip];
                continue;
            }
            $nipSekarang = trim((string)($row['nip'] ?? ''));
            if ($nipSekarang === $nip) {
                $sudahSama[] = $row;
            } elseif ($nipSekarang === '') {
                $diisi[] = ['id' => $row['id'], 'nama' => $nama, 'nip' => $nip];
            } else {
                $nipBeda[] = ['id' => $row['id'], 'nama' => $nama, 'nip_sekarang' => $nipSekarang, 'nip_sheet' => $nip];
            }
        }

        CLI::write('=== Isi NIP Pegawai dari sheet NAMA ===', 'yellow');
        CLI::write('Total baris di sheet: ' . count(self::DATA));
        CLI::newLine();

        CLI::write('Akan diisi (NIP sebelumnya kosong): ' . count($diisi), 'green');
        foreach ($diisi as $d) CLI::write("  #{$d['id']} {$d['nama']} -> {$d['nip']}");
        CLI::newLine();

        CLI::write('Sudah sama, tidak perlu diubah: ' . count($sudahSama));
        CLI::newLine();

        if (!empty($nipBeda)) {
            CLI::write('NIP BEDA — sudah ada NIP tapi tidak sama dengan sheet, TIDAK ditimpa otomatis: ' . count($nipBeda), 'red');
            foreach ($nipBeda as $d) {
                CLI::write("  #{$d['id']} {$d['nama']}: db='{$d['nip_sekarang']}' sheet='{$d['nip_sheet']}'");
            }
            CLI::write('  -> Cek manual mana yang benar, lalu update sendiri lewat Kelola Pegawai kalau perlu.', 'dark_gray');
            CLI::newLine();
        }

        if (!empty($tidakKetemu)) {
            CLI::write('Nama TIDAK KETEMU PERSIS di tabel pegawai (mungkin beda ejaan/singkatan) — dilewati: ' . count($tidakKetemu), 'yellow');
            foreach ($tidakKetemu as [$nama, $nip]) {
                CLI::write("  '{$nama}' (NIP {$nip})");
            }
            CLI::write('  -> Cek manual namanya di Kelola Pegawai, lalu jalankan lagi kalau sudah dicocokkan.', 'dark_gray');
            CLI::newLine();
        }

        if (empty($diisi)) {
            CLI::write('Tidak ada NIP baru yang perlu diisi.', 'green');
            return;
        }

        if (!$apply) {
            CLI::write('Mode DRY-RUN — belum ada perubahan yang disimpan. Jalankan lagi dengan --apply untuk menerapkan.', 'cyan');
            return;
        }

        foreach ($diisi as $d) {
            $pegawaiModel->update($d['id'], ['nip' => $d['nip']]);
        }
        CLI::write(count($diisi) . ' NIP pegawai berhasil diisi.', 'green');
    }
}
