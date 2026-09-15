<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PemasukanModel;
use App\Models\PerjalananDinasPesertaModel;

/**
 * Cocokkan Pemasukan "Setoran Taktis Pegawai" yang diinput manual (dari spreadsheet, sebelum
 * fitur Perjalanan Dinas terstruktur ini ada) dengan baris perjalanan_dinas_peserta yang
 * sekarang berstatus "belum lunas" — supaya orang yang sebenarnya sudah setor (dicatat lewat
 * Pemasukan manual) tidak terus muncul sebagai "Belum Lunas" di halaman Dana Taktis, dan supaya
 * Total Pemasukan tidak dobel kalau baris ini nanti ditandai lunas lagi lewat jalur normal.
 *
 * Dry-run secara default: hanya menampilkan usulan penautan, tidak mengubah apa pun. Jalankan
 * dengan --apply untuk benar-benar menautkan baris yang kecocokannya PASTI (nama+nominal unik).
 * Baris yang ambigu (lebih dari satu kandidat) atau tanpa kandidat TIDAK PERNAH ditautkan
 * otomatis — itu perlu ditinjau & ditautkan manual lewat tombol "Lunas"/"Edit" di halaman
 * Perjalanan Dinas atau Dana Taktis.
 */
class ReconcileSetoranTaktis extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:reconcile-setoran-taktis';
    protected $description = 'Cocokkan Pemasukan "Setoran Taktis Pegawai" manual lama dengan data Perjalanan Dinas terstruktur (dry-run secara default).';
    protected $usage       = 'app:reconcile-setoran-taktis [--apply]';
    protected $options     = [
        '--apply' => 'Terapkan penautan untuk kecocokan yang PASTI (bukan cuma menampilkan usulan).',
    ];

    /** Token gelar/titel umum yang perlu dibuang saat membandingkan nama — daftar ini tidak
     *  perlu lengkap sempurna; kalau ada gelar lain yang tidak dikenali, namanya cukup tidak
     *  akan cocok persis dan baris itu jatuh ke kategori "tidak ada kandidat" (aman, bukan
     *  salah tautkan), lalu bisa ditautkan manual. */
    private const GELAR = [
        'apt', 's farm', 's t p', 's t', 's e', 's si', 'm farm', 'dr', 's kom', 's h',
        's ip', 's sos', 'm m', 'm si', 's pd', 's km', 'a md', 'm kes', 's psi', 'm ap',
    ];

    /** Sapaan/panggilan umum di data — "pegawai" sendiri juga dibuang karena semua sumber
     *  di data nyata diawali kata itu (mis. "Pegawai (Setoran Rutin)", "Pegawai - Ade Yan
     *  Emerson"), bukan bagian dari nama. */
    private const PANGGILAN = ['pegawai', 'pak', 'bapak', 'bu', 'ibu', 'bang', 'uni', 'mas', 'mbak', 'kak'];

    private function normalisasiNama(string $nama): string
    {
        $n = strtolower($nama);
        $n = str_replace(['.', ',', '-', '(', ')', '/'], ' ', $n);
        foreach (self::GELAR as $g) {
            $n = preg_replace('/\b' . preg_quote($g, '/') . '\b/', ' ', $n);
        }
        foreach (self::PANGGILAN as $p) {
            $n = preg_replace('/\b' . preg_quote($p, '/') . '\b/', ' ', $n);
        }
        $n = preg_replace('/\s+/', ' ', $n);
        return trim($n);
    }

    /**
     * Sebagian sumber di data nyata jelas BUKAN nama satu orang tertentu — kalau dipaksa
     * dicocokkan lewat nama+nominal, berisiko salah tautkan (mis. nominal gabungan banyak
     * orang yang kebetulan sama dengan dana_taktis satu peserta). Baris begini dikeluarkan
     * dari pencocokan nama sama sekali dan dilaporkan terpisah supaya jelas kenapa dilewati.
     */
    private function klasifikasiKhusus(string $sumberAsli): ?string
    {
        $s = strtolower($sumberAsli);
        if (str_contains($s, 'setoran rutin')) return 'GENERIK — bukan nama individu, cuma label kategori umum';
        if (preg_match('/taktis\s*20\d\d/', $s)) return 'KEMUNGKINAN SETORAN GABUNGAN TAHUNAN (mis. "TAKTIS 2024")';
        if (str_contains($s, 'pengembalian')) return 'KEMUNGKINAN BUKAN SETORAN (mengandung kata "pengembalian")';
        if (str_contains($s, 'dkk') || str_contains($s, ' dan ') || str_contains($s, '&') || substr_count($s, ',') >= 1) {
            return 'MULTI-ORANG (menyebut lebih dari satu nama/kelompok)';
        }
        return null;
    }

    public function run(array $params)
    {
        $apply = (bool) CLI::getOption('apply');

        $pemasukanModel = new PemasukanModel();
        $pesertaModel   = new PerjalananDinasPesertaModel();

        // Pemasukan yang sudah tertaut ke peserta manapun (lewat jalur normal tandaiLunas())
        // bukan "entri lama yang yatim" — lewati supaya tidak diproses ulang di sini.
        $sudahTertaut = array_values(array_filter(array_column(
            $pesertaModel->select('pemasukan_id')->where('pemasukan_id IS NOT NULL', null, false)->findAll(),
            'pemasukan_id'
        )));

        $builder = $pemasukanModel->where('kategori', 'Setoran Taktis Pegawai');
        if (!empty($sudahTertaut)) {
            $builder->whereNotIn('id', $sudahTertaut);
        }
        $pemasukanYatim = $builder->orderBy('id', 'ASC')->findAll();

        $pesertaBelum = $pesertaModel->where('status_lunas', 'belum')->where('dana_taktis >', 0)->findAll();

        // Index kandidat per (nama_ternormalisasi, nominal) supaya pencarian cepat & jelas
        // ambigu-tidaknya (lebih dari satu peserta dengan nama+nominal identik).
        $kandidat = [];
        foreach ($pesertaBelum as $p) {
            $key = $this->normalisasiNama($p['nama_peserta']) . '|' . (int) round((float) $p['dana_taktis']);
            $kandidat[$key][] = $p;
        }

        $pasti = [];
        $ambigu = [];
        $tidakCocok = [];
        $khususPerKategori = [];

        foreach ($pemasukanYatim as $pm) {
            if (empty($pm['sumber'])) {
                $tidakCocok[] = $pm;
                continue;
            }

            $khusus = $this->klasifikasiKhusus($pm['sumber']);
            if ($khusus !== null) {
                $khususPerKategori[$khusus][] = $pm;
                continue;
            }

            $key    = $this->normalisasiNama($pm['sumber']) . '|' . (int) round((float) $pm['jumlah']);
            $daftar = $kandidat[$key] ?? [];
            if (count($daftar) === 1) {
                $pasti[] = ['pemasukan' => $pm, 'peserta' => $daftar[0]];
            } elseif (count($daftar) > 1) {
                $ambigu[] = ['pemasukan' => $pm, 'kandidat' => $daftar];
            } else {
                $tidakCocok[] = $pm;
            }
        }

        CLI::write('=== Rekonsiliasi Setoran Taktis Pegawai ===', 'yellow');
        CLI::write('Pemasukan manual "Setoran Taktis Pegawai" yang belum tertaut: ' . count($pemasukanYatim));
        CLI::write('Peserta Perjalanan Dinas berstatus Belum Lunas: ' . count($pesertaBelum));
        CLI::newLine();

        if (!empty($khususPerKategori)) {
            CLI::write('--- DIKELUARKAN DARI PENCOCOKAN (bukan setoran satu orang tertentu) ---', 'yellow');
            CLI::write('Baris ini TIDAK bisa ditautkan otomatis ke satu peserta manapun secara aman, apapun namanya —');
            CLI::write('kalau memang perlu, cocokkan manual satu per satu lewat tombol "Lunas" di halaman Perjalanan Dinas.');
            foreach ($khususPerKategori as $label => $rows) {
                CLI::write('  [' . $label . '] — ' . count($rows) . ' baris', 'yellow');
                $contoh = array_slice($rows, 0, 3);
                foreach ($contoh as $pm) {
                    CLI::write(sprintf(
                        '    Pemasukan #%d [%s, Rp%s, %s]',
                        $pm['id'], $pm['sumber'], number_format((float) $pm['jumlah'], 0, ',', '.'), $pm['tanggal']
                    ));
                }
                if (count($rows) > 3) {
                    CLI::write('    ... dan ' . (count($rows) - 3) . ' baris lain dengan kategori yang sama.');
                }
            }
            CLI::newLine();
        }

        CLI::write('--- COCOK PASTI (nama + nominal unik): ' . count($pasti) . ' ---', 'green');
        foreach ($pasti as $m) {
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, %s] -> Peserta #%d (trip #%d)',
                $m['pemasukan']['id'],
                $m['pemasukan']['sumber'],
                number_format((float) $m['pemasukan']['jumlah'], 0, ',', '.'),
                $m['pemasukan']['tanggal'],
                $m['peserta']['id'],
                $m['peserta']['perjalanan_dinas_id']
            ));
        }
        CLI::newLine();

        CLI::write('--- AMBIGU, dilewati (lebih dari 1 kandidat — tinjau & tautkan manual): ' . count($ambigu) . ' ---', 'yellow');
        foreach ($ambigu as $m) {
            $daftarKandidat = implode(', ', array_map(
                static fn($p) => '#' . $p['id'] . ' (trip #' . $p['perjalanan_dinas_id'] . ')',
                $m['kandidat']
            ));
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s] -> kandidat: %s',
                $m['pemasukan']['id'],
                $m['pemasukan']['sumber'],
                number_format((float) $m['pemasukan']['jumlah'], 0, ',', '.'),
                $daftarKandidat
            ));
        }
        CLI::newLine();

        CLI::write('--- TIDAK ADA KANDIDAT (ada nama, tapi nama+nominal tidak cocok dengan peserta manapun): ' . count($tidakCocok) . ' ---', 'red');
        foreach (array_slice($tidakCocok, 0, 50) as $pm) {
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, %s]',
                $pm['id'],
                $pm['sumber'] ?: '(kosong)',
                number_format((float) $pm['jumlah'], 0, ',', '.'),
                $pm['tanggal']
            ));
        }
        if (count($tidakCocok) > 50) {
            CLI::write('  ... dan ' . (count($tidakCocok) - 50) . ' baris lain.');
        }
        CLI::newLine();

        if (!$apply) {
            CLI::write('Mode DRY-RUN — belum ada perubahan yang disimpan.', 'cyan');
            CLI::write('Jalankan lagi dengan --apply untuk menautkan baris "COCOK PASTI" di atas.', 'cyan');
            return;
        }

        if (empty($pasti)) {
            CLI::write('Tidak ada kecocokan pasti untuk diterapkan.', 'cyan');
            return;
        }

        CLI::write('Menerapkan ' . count($pasti) . ' penautan...', 'green');
        foreach ($pasti as $m) {
            $pesertaModel->update($m['peserta']['id'], [
                'status_lunas'  => 'lunas',
                'tanggal_lunas' => $m['pemasukan']['tanggal'],
                'pemasukan_id'  => $m['pemasukan']['id'],
            ]);
            CLI::write("  Peserta #{$m['peserta']['id']} ditandai lunas, ditautkan ke Pemasukan #{$m['pemasukan']['id']}.");
        }
        CLI::write('Selesai.', 'green');
    }
}
