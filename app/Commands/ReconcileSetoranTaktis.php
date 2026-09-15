<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PemasukanModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PegawaiModel;

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

    /** Sama seperti normalisasiNama(), tapi untuk nama di tabel pegawai (tidak pernah
     *  berawalan "Pegawai"/sapaan, jadi tidak perlu buang PANGGILAN). */
    private function normalisasiPegawai(string $nama): string
    {
        $n = strtolower($nama);
        $n = str_replace(['.', ',', '-', '(', ')', '/'], ' ', $n);
        foreach (self::GELAR as $g) {
            $n = preg_replace('/\b' . preg_quote($g, '/') . '\b/', ' ', $n);
        }
        $n = preg_replace('/\s+/', ' ', $n);
        return trim($n);
    }

    /**
     * Cari SATU pegawai yang namanya mengandung semua kata dari nama sumber yang sudah
     * dinormalisasi (mis. "agus" cocok ke pegawai "Agus Riyanto"). Kalau lebih dari satu
     * pegawai memenuhi ini, dianggap tidak yakin (null) — jangan menebak siapa yang dimaksud,
     * biar konsisten dengan alasan user sendiri: "kalau namanya hanya 1 berarti itu orang
     * yang sama" — sebaliknya kalau lebih dari 1, memang bukan lagi hal yang pasti.
     */
    private function resolvePegawaiUnik(string $sumberNormal, array $semuaPegawai): ?array
    {
        $kataSumber = array_values(array_filter(explode(' ', $sumberNormal)));
        if (empty($kataSumber)) return null;

        $cocok = [];
        foreach ($semuaPegawai as $pg) {
            $kataPegawai = explode(' ', $pg['_nama_normal']);
            $semuaAda = true;
            foreach ($kataSumber as $kata) {
                if (!in_array($kata, $kataPegawai, true)) {
                    $semuaAda = false;
                    break;
                }
            }
            if ($semuaAda) $cocok[] = $pg;
        }
        return count($cocok) === 1 ? $cocok[0] : null;
    }

    /**
     * Untuk baris yang gagal cocok — cari SEMUA pegawai yang punya minimal satu kata yang
     * sama dengan nama sumber (longgar, tidak seperti resolvePegawaiUnik yang butuh SEMUA
     * kata cocok). Ini cuma petunjuk diagnostik yang ditampilkan ke user, TIDAK PERNAH dipakai
     * untuk menautkan otomatis — supaya user bisa lihat sendiri kenapa gagal (mis. ada 2+
     * pegawai bernama "Agus", atau ejaannya beda seperti "Ronny" vs "Rony").
     */
    private function cariKemungkinanPegawai(string $sumberNormal, array $semuaPegawai): array
    {
        $kataSumber = array_values(array_filter(explode(' ', $sumberNormal)));
        if (empty($kataSumber)) return [];

        $kemungkinan = [];
        foreach ($semuaPegawai as $pg) {
            $kataPegawai = explode(' ', $pg['_nama_normal']);
            foreach ($kataSumber as $kata) {
                if (strlen($kata) >= 3 && in_array($kata, $kataPegawai, true)) {
                    $kemungkinan[] = $pg['nama'];
                    break;
                }
            }
        }
        return $kemungkinan;
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

        // tanggal_surat_tugas diikutkan karena setoran gabungan cuma bisa menutup trip yang
        // TANGGALNYA SUDAH LEWAT saat setoran itu dibayar — trip yang baru terjadi belakangan
        // jelas bukan bagian dari pembayaran lama itu (lihat pemakaiannya di bawah).
        $pesertaBelum = $pesertaModel
            ->select('perjalanan_dinas_peserta.*, perjalanan_dinas.tanggal_surat_tugas')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas_peserta.status_lunas', 'belum')
            ->where('perjalanan_dinas_peserta.dana_taktis >', 0)
            ->findAll();

        // Index kandidat per (nama_ternormalisasi, nominal) supaya pencarian cepat & jelas
        // ambigu-tidaknya (lebih dari satu peserta dengan nama+nominal identik).
        $kandidat = [];
        $belumByPegawai = [];
        foreach ($pesertaBelum as $p) {
            $key = $this->normalisasiNama($p['nama_peserta']) . '|' . (int) round((float) $p['dana_taktis']);
            $kandidat[$key][] = $p;
            if (!empty($p['pegawai_id'])) {
                $belumByPegawai[$p['pegawai_id']][] = $p;
            }
        }

        $semuaPegawai = (new PegawaiModel())->findAll();
        foreach ($semuaPegawai as &$pg) {
            $pg['_nama_normal'] = $this->normalisasiPegawai($pg['nama']);
        }
        unset($pg);

        $pasti = [];
        $multiTrip = [];
        $identitasSajaCocok = [];
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

            $namaSumberNormal = $this->normalisasiNama($pm['sumber']);
            $key    = $namaSumberNormal . '|' . (int) round((float) $pm['jumlah']);
            $daftar = $kandidat[$key] ?? [];
            if (count($daftar) === 1) {
                $pasti[] = ['pemasukan' => $pm, 'peserta' => $daftar[0]];
                continue;
            }
            if (count($daftar) > 1) {
                $ambigu[] = ['pemasukan' => $pm, 'kandidat' => $daftar];
                continue;
            }

            // Tidak ada trip TUNGGAL yang nominalnya persis sama — coba kenali orangnya lewat
            // tabel pegawai (nama pendek/panggilan tetap unik ke satu orang, per konfirmasi
            // user), lalu cek apakah setoran ini sebenarnya gabungan beberapa trip sekaligus.
            // Trip yang tanggalnya SETELAH tanggal setoran dikeluarkan dari jumlah — setoran
            // lama jelas tidak mungkin menutup perjalanan dinas yang belum terjadi saat itu.
            $pgCocok = $this->resolvePegawaiUnik($namaSumberNormal, $semuaPegawai);
            $semuaTripOrangIni = $pgCocok ? ($belumByPegawai[$pgCocok['id']] ?? []) : [];
            $tripBelumOrangIni = array_values(array_filter(
                $semuaTripOrangIni,
                static fn($p) => $p['tanggal_surat_tugas'] <= $pm['tanggal']
            ));

            if ($pgCocok !== null && !empty($tripBelumOrangIni)) {
                $totalBelum = array_sum(array_map(static fn($p) => (float) $p['dana_taktis'], $tripBelumOrangIni));
                if ((int) round($totalBelum) === (int) round((float) $pm['jumlah'])) {
                    $multiTrip[] = ['pemasukan' => $pm, 'pegawai' => $pgCocok, 'trips' => $tripBelumOrangIni];
                } else {
                    $identitasSajaCocok[] = [
                        'pemasukan' => $pm, 'pegawai' => $pgCocok, 'trips' => $tripBelumOrangIni, 'total_belum' => $totalBelum,
                    ];
                }
                continue;
            }

            $tidakCocok[] = $pm;
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

        CLI::write('--- COCOK PASTI (nama + nominal unik, 1 trip): ' . count($pasti) . ' ---', 'green');
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

        CLI::write('--- COCOK MULTI-TRIP (nama teridentifikasi unik ke 1 pegawai, jumlah setoran = total semua trip belum lunas orang itu): ' . count($multiTrip) . ' ---', 'green');
        foreach ($multiTrip as $m) {
            $daftarTrip = implode(', ', array_map(
                static fn($p) => '#' . $p['id'] . ' (trip #' . $p['perjalanan_dinas_id'] . ', Rp' . number_format((float) $p['dana_taktis'], 0, ',', '.') . ')',
                $m['trips']
            ));
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, %s] -> %s, %d trip: %s',
                $m['pemasukan']['id'],
                $m['pemasukan']['sumber'],
                number_format((float) $m['pemasukan']['jumlah'], 0, ',', '.'),
                $m['pemasukan']['tanggal'],
                $m['pegawai']['nama'],
                count($m['trips']),
                $daftarTrip
            ));
        }
        CLI::newLine();

        CLI::write('--- IDENTITAS DITEMUKAN, TOTAL TIDAK PAS (trip di bawah ini dibatasi sampai tanggal setoran — tinjau manual): ' . count($identitasSajaCocok) . ' ---', 'yellow');
        foreach ($identitasSajaCocok as $m) {
            $daftarTrip = implode(', ', array_map(
                static fn($p) => '#' . $p['id'] . ' (trip #' . $p['perjalanan_dinas_id'] . ', Rp' . number_format((float) $p['dana_taktis'], 0, ',', '.') . ')',
                $m['trips']
            ));
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, %s] -> %s, trip belum lunas (total Rp%s): %s',
                $m['pemasukan']['id'],
                $m['pemasukan']['sumber'],
                number_format((float) $m['pemasukan']['jumlah'], 0, ',', '.'),
                $m['pemasukan']['tanggal'],
                $m['pegawai']['nama'],
                number_format($m['total_belum'], 0, ',', '.'),
                $daftarTrip
            ));
        }
        CLI::newLine();

        CLI::write('--- AMBIGU, dilewati (lebih dari 1 kandidat — tinjau & tautkan manual): ' . count($ambigu) . ' ---', 'yellow');
        CLI::write('  Petunjuk: setoran biasanya dibayar SETELAH trip terjadi, jadi kandidat dengan tanggal');
        CLI::write('  trip tepat sebelum tanggal setoran biasanya yang paling mungkin.');
        foreach ($ambigu as $m) {
            $daftarKandidat = implode(', ', array_map(
                static fn($p) => '#' . $p['id'] . ' (trip #' . $p['perjalanan_dinas_id'] . ', tgl trip ' . $p['tanggal_surat_tugas'] . ')',
                $m['kandidat']
            ));
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, disetor %s] -> kandidat: %s',
                $m['pemasukan']['id'],
                $m['pemasukan']['sumber'],
                number_format((float) $m['pemasukan']['jumlah'], 0, ',', '.'),
                $m['pemasukan']['tanggal'],
                $daftarKandidat
            ));
        }
        CLI::newLine();

        CLI::write('--- TIDAK ADA KANDIDAT (ada nama, tapi nama+nominal tidak cocok dengan peserta manapun): ' . count($tidakCocok) . ' ---', 'red');
        CLI::write('  "Kemungkinan terkait" di bawah HANYA petunjuk (tidak pernah ditautkan otomatis) — kalau muncul');
        CLI::write('  lebih dari 1 nama, berarti ada beberapa pegawai dengan kata nama yang sama (makanya tidak bisa');
        CLI::write('  ditautkan otomatis); kalau kosong, kemungkinan cuma beda ejaan atau memang bukan pegawai aktif.');
        foreach (array_slice($tidakCocok, 0, 50) as $pm) {
            CLI::write(sprintf(
                '  Pemasukan #%d [%s, Rp%s, %s]',
                $pm['id'],
                $pm['sumber'] ?: '(kosong)',
                number_format((float) $pm['jumlah'], 0, ',', '.'),
                $pm['tanggal']
            ));
            if (!empty($pm['sumber'])) {
                $kemungkinan = $this->cariKemungkinanPegawai($this->normalisasiNama($pm['sumber']), $semuaPegawai);
                if (!empty($kemungkinan)) {
                    CLI::write('    Kemungkinan terkait: ' . implode(', ', $kemungkinan));
                }
            }
        }
        if (count($tidakCocok) > 50) {
            CLI::write('  ... dan ' . (count($tidakCocok) - 50) . ' baris lain.');
        }
        CLI::newLine();

        if (!$apply) {
            CLI::write('Mode DRY-RUN — belum ada perubahan yang disimpan.', 'cyan');
            CLI::write('Jalankan lagi dengan --apply untuk menautkan baris "COCOK PASTI" dan "COCOK MULTI-TRIP" di atas.', 'cyan');
            return;
        }

        if (empty($pasti) && empty($multiTrip)) {
            CLI::write('Tidak ada kecocokan pasti untuk diterapkan.', 'cyan');
            return;
        }

        CLI::write('Menerapkan ' . count($pasti) . ' penautan 1-trip...', 'green');
        foreach ($pasti as $m) {
            $pesertaModel->update($m['peserta']['id'], [
                'status_lunas'  => 'lunas',
                'tanggal_lunas' => $m['pemasukan']['tanggal'],
                'pemasukan_id'  => $m['pemasukan']['id'],
            ]);
            CLI::write("  Peserta #{$m['peserta']['id']} ditandai lunas, ditautkan ke Pemasukan #{$m['pemasukan']['id']}.");
        }

        CLI::write('Menerapkan ' . count($multiTrip) . ' penautan multi-trip...', 'green');
        foreach ($multiTrip as $m) {
            foreach ($m['trips'] as $trip) {
                $pesertaModel->update($trip['id'], [
                    'status_lunas'  => 'lunas',
                    'tanggal_lunas' => $m['pemasukan']['tanggal'],
                    'pemasukan_id'  => $m['pemasukan']['id'],
                ]);
            }
            CLI::write("  {$m['pegawai']['nama']}: " . count($m['trips']) . " trip ditandai lunas, ditautkan ke Pemasukan #{$m['pemasukan']['id']}.");
        }
        CLI::write('Selesai.', 'green');
    }
}
