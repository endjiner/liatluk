<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PemasukanModel;

/**
 * Perbaiki massal status_lunas peserta Perjalanan Dinas yang salah dibandingkan sumber
 * asli — Google Sheet "A. Rekap Perjalanan Dinas 2026 - OTOMATIS" (tab "Rekap PD 2026"),
 * diekspor ke data/rekap_perjalanan_dinas.json. Lihat app:verify-perjadin-sheet untuk
 * laporan lengkapnya; tool ini HANYA menangani baris "STATUS LUNAS beda" dari laporan itu.
 * Pencocokan trip/peserta di bawah ini sengaja disalin persis dari VerifyPerjadinSheet
 * (termasuk fallback nama subset & kunci komposit no_surat_tugas+kode_mak) supaya baris
 * yang "diperbaiki" di sini benar-benar baris yang sama dengan yang dilaporkan di sana.
 *
 * Arah yang diperbaiki: sheet=belum, db=lunas (DB keliru menandai sudah lunas). Arah
 * sebaliknya (sheet=lunas, db=belum) TIDAK PERNAH diperbaiki otomatis di sini — itu
 * berarti ada pembayaran nyata yang belum tercatat di sistem, dan mencatatnya butuh
 * tanggal+nominal pembayaran yang sebenarnya, bukan sekadar membalik status.
 *
 * Baris yang arahnya benar (sheet=belum, db=lunas) dipisah lagi jadi 2 kelompok:
 *   - AMAN: pemasukan_id kosong, ATAU Pemasukan yang tertaut dibuat otomatis oleh
 *     tandaiLunas() (dari_tandai_lunas=1) — batalkanLunas() adalah operasi yang persis
 *     dirancang untuk kasus ini (lihat PerjalananDinasPesertaModel::batalkanLunas()).
 *   - PERLU KEPUTUSAN MANUAL: Pemasukan yang tertaut sudah ada SEBELUM ditandai lunas di
 *     trip ini (dari_tandai_lunas=0, biasanya hasil app:reconcile-setoran-taktis) — artinya
 *     ada uang nyata di pembukuan lama yang dicocokkan ke trip ini. Sheet bilang "belum"
 *     bisa berarti pencocokan itu salah, ATAU sheet-nya yang belum di-update. Tool ini
 *     TIDAK PERNAH menyentuh baris ini secara otomatis — putuskan sendiri lalu pakai
 *     app:batalkan-lunas-satu <peserta_id> --confirm kalau memang mau dibatalkan.
 *
 * Dry-run secara default (hanya melaporkan). Tambahkan --confirm untuk benar-benar
 * menjalankan batalkanLunas() pada baris yang masuk kelompok AMAN.
 */
class PerbaikiStatusLunasMassal extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:perbaiki-status-lunas-massal';
    protected $description = 'Perbaiki massal status_lunas peserta yang salah vs sheet Rekap Perjalanan Dinas (dry-run tanpa --confirm).';
    protected $usage       = 'app:perbaiki-status-lunas-massal [--confirm]';

    private const GELAR = [
        'apt', 's farm', 's t p', 's t', 's e', 's si', 'm farm', 'dr', 's kom', 's h',
        's ip', 's sos', 'm m', 'm si', 's pd', 's km', 'a md', 'm kes', 's psi', 'm ap',
    ];

    private function normNama(?string $s): string
    {
        $n = strtolower((string) $s);
        $n = str_replace(['.', ',', '-', '(', ')', '/'], ' ', $n);
        foreach (self::GELAR as $g) {
            $n = preg_replace('/\b' . preg_quote($g, '/') . '\b/', ' ', $n);
        }
        $n = preg_replace('/\s+/', ' ', $n);
        return trim($n);
    }

    private function normSurat(?string $s): string
    {
        return preg_replace('/\s+/', '', strtolower(trim((string) $s)));
    }

    /** Sama seperti VerifyPerjadinSheet::namaSubset() — lihat komentar di sana. */
    private function namaSubset(string $a, string $b): bool
    {
        if ($a === '' || $b === '') return false;
        $kataA = array_filter(explode(' ', $a));
        $kataB = array_filter(explode(' ', $b));
        [$pendek, $panjang] = count($kataA) <= count($kataB) ? [$kataA, $kataB] : [$kataB, $kataA];
        foreach ($pendek as $kata) {
            if (!in_array($kata, $panjang, true)) return false;
        }
        return true;
    }

    public function run(array $params)
    {
        $path = __DIR__ . '/data/rekap_perjalanan_dinas.json';
        $sheetTrips = json_decode(file_get_contents($path), true);
        if (!is_array($sheetTrips)) {
            CLI::error("Gagal membaca sumber di $path");
            return;
        }

        $pesertaModel = new PerjalananDinasPesertaModel();
        $dbTrips = (new PerjalananDinasModel())->findAll();
        $dbPeserta = $pesertaModel->findAll();

        $pesertaByTrip = [];
        foreach ($dbPeserta as $p) $pesertaByTrip[$p['perjalanan_dinas_id']][] = $p;

        $dbByComposite = [];
        $dbByNoSurat = [];
        foreach ($dbTrips as $t) {
            $keySurat = $this->normSurat($t['no_surat_tugas']);
            $keyKode  = $this->normSurat($t['kode_mak']);
            if ($keySurat !== '') {
                $dbByNoSurat[$keySurat][] = $t['id'];
                if ($keyKode !== '') $dbByComposite[$keySurat . '|' . $keyKode][] = $t['id'];
            }
        }

        // Pemasukan yang dipakai peserta manapun — dimuat sekali supaya bisa cek
        // dari_tandai_lunas tanpa query per-baris.
        $pemasukanIds = array_values(array_unique(array_filter(array_column($dbPeserta, 'pemasukan_id'))));
        $pemasukanById = [];
        if (!empty($pemasukanIds)) {
            foreach ((new PemasukanModel())->whereIn('id', $pemasukanIds)->findAll() as $pm) {
                $pemasukanById[$pm['id']] = $pm;
            }
        }
        $pemakaiPemasukan = [];
        foreach ($dbPeserta as $p) {
            if (!empty($p['pemasukan_id'])) $pemakaiPemasukan[$p['pemasukan_id']][] = $p['id'];
        }

        $aman = [];
        $perluKeputusan = [];
        $arahSebaliknya = 0; // sheet=lunas, db=belum — informasional saja, tidak diperbaiki di sini

        foreach ($sheetTrips as $st) {
            if (empty($st['no_surat_tugas'])) continue;
            $keySurat = $this->normSurat($st['no_surat_tugas']);
            $keyKode  = $this->normSurat($st['kode_mak']);
            $keyKomposit = $keySurat . '|' . $keyKode;

            $dbTripId = null;
            if ($keyKode !== '' && !empty($dbByComposite[$keyKomposit])) {
                $dbTripId = array_shift($dbByComposite[$keyKomposit]);
                $pos = array_search($dbTripId, $dbByNoSurat[$keySurat] ?? [], true);
                if ($pos !== false) unset($dbByNoSurat[$keySurat][$pos]);
            } elseif (!empty($dbByNoSurat[$keySurat])) {
                $dbTripId = array_shift($dbByNoSurat[$keySurat]);
            }
            if ($dbTripId === null) continue;

            $dbPesertaTrip = $pesertaByTrip[$dbTripId] ?? [];
            $dipakai = array_fill(0, count($dbPesertaTrip), false);

            foreach ($st['peserta'] as $sp) {
                $namaNorm = $this->normNama($sp['nama_peserta']);
                $idxMatch = null;
                foreach ($dbPesertaTrip as $i => $dp) {
                    if ($dipakai[$i]) continue;
                    if ($this->normNama($dp['nama_peserta']) === $namaNorm) { $idxMatch = $i; break; }
                }
                if ($idxMatch === null) {
                    $kandidat = [];
                    foreach ($dbPesertaTrip as $i => $dp) {
                        if ($dipakai[$i]) continue;
                        if ($this->namaSubset($namaNorm, $this->normNama($dp['nama_peserta']))) $kandidat[] = $i;
                    }
                    if (count($kandidat) === 1) $idxMatch = $kandidat[0];
                }
                if ($idxMatch === null) continue;
                $dipakai[$idxMatch] = true;
                $dp = $dbPesertaTrip[$idxMatch];

                if ($sp['status_lunas'] === $dp['status_lunas']) continue;

                if ($sp['status_lunas'] === 'lunas' && $dp['status_lunas'] === 'belum') {
                    $arahSebaliknya++;
                    continue;
                }
                if (!($sp['status_lunas'] === 'belum' && $dp['status_lunas'] === 'lunas')) continue;

                $info = [
                    'peserta_id'   => $dp['id'],
                    'nama'         => $dp['nama_peserta'],
                    'no_surat'     => $st['no_surat_tugas'],
                    'maksud'       => mb_substr($st['maksud'] ?? '', 0, 60),
                    'pemasukan_id' => $dp['pemasukan_id'],
                ];

                $pemasukan = !empty($dp['pemasukan_id']) ? ($pemasukanById[$dp['pemasukan_id']] ?? null) : null;
                if ($pemasukan && (int) $pemasukan['dari_tandai_lunas'] !== 1) {
                    $info['pemasukan'] = $pemasukan;
                    $info['dipakai_peserta_lain'] = array_values(array_diff($pemakaiPemasukan[$dp['pemasukan_id']] ?? [], [$dp['id']]));
                    $perluKeputusan[] = $info;
                } else {
                    $aman[] = $info;
                }
            }
        }

        CLI::write('=== Perbaikan Massal Status Lunas vs Sheet Rekap Perjalanan Dinas ===', 'yellow');
        CLI::write('(Hanya menangani arah: sheet=BELUM, db=LUNAS — lihat app:verify-perjadin-sheet untuk laporan lengkap)');
        CLI::newLine();

        CLI::write('Aman untuk diperbaiki otomatis: ' . count($aman), empty($aman) ? 'green' : 'yellow');
        foreach ($aman as $a) {
            CLI::write(sprintf(
                '  peserta #%d %s — trip [%s] %s%s',
                $a['peserta_id'], $a['nama'], $a['no_surat'], $a['maksud'],
                $a['pemasukan_id'] ? " (akan hapus Pemasukan auto #{$a['pemasukan_id']} kalau tidak dipakai peserta lain)" : ''
            ));
        }
        CLI::newLine();

        if (!empty($perluKeputusan)) {
            CLI::write('PERLU KEPUTUSAN MANUAL — TIDAK disentuh tool ini: ' . count($perluKeputusan), 'red');
            foreach ($perluKeputusan as $p) {
                CLI::write(sprintf(
                    '  peserta #%d %s — trip [%s] %s — tertaut Pemasukan #%d [%s, Rp%s, %s]%s',
                    $p['peserta_id'], $p['nama'], $p['no_surat'], $p['maksud'],
                    $p['pemasukan']['id'], $p['pemasukan']['sumber'],
                    number_format((float) $p['pemasukan']['jumlah'], 0, ',', '.'), $p['pemasukan']['tanggal'],
                    empty($p['dipakai_peserta_lain']) ? '' : ' [dipakai bersama peserta id: ' . implode(',', $p['dipakai_peserta_lain']) . ']'
                ));
            }
            CLI::write('  Pemasukan ini sudah ada SEBELUM ditandai lunas (bukan auto dari tandaiLunas), kemungkinan besar hasil');
            CLI::write('  app:reconcile-setoran-taktis. Sheet bilang "belum" bisa berarti pencocokan lama itu salah, ATAU sheet');
            CLI::write('  belum di-update. Putuskan sendiri per kasus, lalu jalankan: php spark app:batalkan-lunas-satu <peserta_id> --confirm');
            CLI::newLine();
        }

        if ($arahSebaliknya > 0) {
            CLI::write("Arah sebaliknya (sheet=LUNAS, db=BELUM): $arahSebaliknya baris — TIDAK diperbaiki di sini (butuh input tanggal+nominal pembayaran nyata).", 'cyan');
            CLI::newLine();
        }

        if (empty($aman)) {
            CLI::write('Tidak ada yang bisa diperbaiki otomatis.', 'green');
            return;
        }

        if (!CLI::getOption('confirm')) {
            CLI::write('Mode DRY-RUN — belum ada perubahan. Tambahkan --confirm untuk memperbaiki ' . count($aman) . ' baris di atas.', 'cyan');
            return;
        }

        CLI::newLine();
        CLI::write('=== Menjalankan perbaikan ===', 'yellow');
        $sukses = 0;
        $pemasukanDihapus = 0;
        foreach ($aman as $a) {
            $hasil = $pesertaModel->batalkanLunas($a['peserta_id']);
            if ($hasil['ok']) {
                $sukses++;
                if ($hasil['pemasukan_dihapus']) $pemasukanDihapus++;
                CLI::write("  OK peserta #{$a['peserta_id']} {$a['nama']}" . ($hasil['pemasukan_dihapus'] ? ' (Pemasukan ikut terhapus)' : ''), 'green');
            } else {
                CLI::write("  GAGAL peserta #{$a['peserta_id']} {$a['nama']} (kemungkinan status sudah berubah)", 'red');
            }
        }
        CLI::newLine();
        CLI::write("Selesai — $sukses/" . count($aman) . " berhasil diperbaiki, $pemasukanDihapus Pemasukan auto ikut terhapus.", 'green');
    }
}
