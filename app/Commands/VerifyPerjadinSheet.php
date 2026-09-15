<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PerjalananDinasTiketModel;
use App\Models\PerjalananDinasHotelModel;

/**
 * Bandingkan data trip/peserta/tiket/hotel di database dengan sumber aslinya — Google Sheet
 * "A. Rekap Perjalanan Dinas 2026 - OTOMATIS" (tab "Rekap PD 2026"), diekspor ke
 * data/rekap_perjalanan_dinas.json. Read-only, tidak pernah mengubah apa pun.
 *
 * Kunci pencocokan trip adalah no_surat_tugas (dinormalisasi: lowercase, buang semua spasi) —
 * BUKAN kolom "No" di spreadsheet, karena kolom itu ternyata punya blok yang ke-duplikat
 * (No 209-219 dipakai dua kali untuk 22 trip yang benar-benar berbeda, kemungkinan salah
 * renumbering manual). no_surat_tugas adalah nomor surat resmi, jauh lebih bisa diandalkan.
 * Satu trip di spreadsheet tidak punya no_surat_tugas sama sekali (baris kosong, kemungkinan
 * surat tugasnya belum terbit) — trip itu dilaporkan terpisah, tidak bisa dicocokkan otomatis.
 *
 * Nama peserta dinormalisasi (buang gelar) sebelum dicocokkan, karena spreadsheet secara
 * eksplisit mencatat nama "TANPA GELAR AKADEMIK" sementara data di database kadang menyimpan
 * gelar (mis. "Apt. ... S.Farm").
 */
class VerifyPerjadinSheet extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:verify-perjadin-sheet';
    protected $description = 'Bandingkan trip/peserta/tiket/hotel di database dengan sumber Google Sheet Rekap Perjalanan Dinas.';

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

    private function normTeks(?string $s): string
    {
        $s = strtolower((string) $s);
        $s = trim($s);
        return in_array($s, ['', '-', 'n/a', 'na'], true) ? '' : $s;
    }

    private function sama(float $a, float $b): bool
    {
        return abs($a - $b) < 1;
    }

    public function run(array $params)
    {
        $path = __DIR__ . '/data/rekap_perjalanan_dinas.json';
        $sheetTrips = json_decode(file_get_contents($path), true);
        if (!is_array($sheetTrips)) {
            CLI::error("Gagal membaca sumber di $path");
            return;
        }

        $dbTrips = (new PerjalananDinasModel())->findAll();
        $dbPeserta = (new PerjalananDinasPesertaModel())->findAll();
        $dbTiket = (new PerjalananDinasTiketModel())->findAll();
        $dbHotel = (new PerjalananDinasHotelModel())->findAll();

        $pesertaByTrip = [];
        foreach ($dbPeserta as $p) $pesertaByTrip[$p['perjalanan_dinas_id']][] = $p;
        $tiketByPeserta = [];
        foreach ($dbTiket as $t) $tiketByPeserta[$t['peserta_id']][] = $t;
        $hotelByPeserta = [];
        foreach ($dbHotel as $h) $hotelByPeserta[$h['peserta_id']] = $h;

        // Satu no_surat_tugas kadang menaungi LEBIH DARI SATU trip di database — biaya satu
        // surat tugas yang sama dipecah ke beberapa Kode MAK, masing-masing dengan peserta
        // sendiri (mis. "...139" dipakai untuk Kode MAK "...A" dan "...C" sekaligus, dengan
        // peserta yang beda). Karena itu kunci utamanya (no_surat_tugas + kode_mak), baru
        // kalau tidak ketemu jatuh ke no_surat_tugas saja — dan kandidat yang sudah dipakai
        // di-pop dari daftar supaya tidak pernah dicocokkan dua kali ke trip sheet yang beda.
        $dbByComposite = [];
        $dbByNoSurat = [];
        $dbDipakai = [];
        foreach ($dbTrips as $t) {
            $keySurat = $this->normSurat($t['no_surat_tugas']);
            $keyKode  = $this->normSurat($t['kode_mak']);
            if ($keySurat !== '') {
                $dbByNoSurat[$keySurat][] = $t['id'];
                if ($keyKode !== '') $dbByComposite[$keySurat . '|' . $keyKode][] = $t['id'];
            }
            $dbDipakai[$t['id']] = false;
        }

        $dbTripById = [];
        foreach ($dbTrips as $t) $dbTripById[$t['id']] = $t;

        $tripCocok = 0;
        $tripHilang = [];
        $tripTanpaSurat = [];
        $tripBermasalah = []; // matched trip, but participant/amount/tiket/hotel diffs found

        foreach ($sheetTrips as $st) {
            if (empty($st['no_surat_tugas'])) {
                $tripTanpaSurat[] = $st;
                continue;
            }
            $keySurat = $this->normSurat($st['no_surat_tugas']);
            $keyKode  = $this->normSurat($st['kode_mak']);
            $keyKomposit = $keySurat . '|' . $keyKode;

            $dbTripId = null;
            if ($keyKode !== '' && !empty($dbByComposite[$keyKomposit])) {
                $dbTripId = array_shift($dbByComposite[$keyKomposit]);
                // Buang juga dari daftar no_surat_tugas-saja supaya tidak terpakai dua kali
                // lewat jalur fallback di bawah.
                $pos = array_search($dbTripId, $dbByNoSurat[$keySurat] ?? [], true);
                if ($pos !== false) unset($dbByNoSurat[$keySurat][$pos]);
            } elseif (!empty($dbByNoSurat[$keySurat])) {
                $dbTripId = array_shift($dbByNoSurat[$keySurat]);
            }

            if ($dbTripId === null) {
                $tripHilang[] = $st;
                continue;
            }
            $dbDipakai[$dbTripId] = true;

            $masalah = [];
            $dbTripRow = $dbTripById[$dbTripId];
            if ($this->normSurat($dbTripRow['tanggal_surat_tugas']) !== '' && $dbTripRow['tanggal_surat_tugas'] !== $st['tanggal_surat_tugas']) {
                $masalah[] = "tanggal_surat_tugas beda: sheet={$st['tanggal_surat_tugas']} db={$dbTripRow['tanggal_surat_tugas']}";
            }

            // Cocokkan peserta dalam trip ini berdasarkan nama ternormalisasi.
            $dbPesertaTrip = $pesertaByTrip[$dbTripId] ?? [];
            $dbPesertaDipakai = array_fill(0, count($dbPesertaTrip), false);

            foreach ($st['peserta'] as $sp) {
                $namaNorm = $this->normNama($sp['nama_peserta']);
                $idxMatch = null;
                foreach ($dbPesertaTrip as $i => $dp) {
                    if ($dbPesertaDipakai[$i]) continue;
                    if ($this->normNama($dp['nama_peserta']) === $namaNorm) { $idxMatch = $i; break; }
                }
                if ($idxMatch === null) {
                    $masalah[] = "peserta HILANG dari DB: {$sp['nama_peserta']} (uang harian Rp" . number_format($sp['uang_harian'], 0, ',', '.') . ")";
                    continue;
                }
                $dbPesertaDipakai[$idxMatch] = true;
                $dp = $dbPesertaTrip[$idxMatch];

                foreach ([
                    'uang_harian' => 'Uang Harian', 'meeting_fullboard' => 'Meeting Fullboard', 'meeting_fullday' => 'Meeting Fullday',
                    'uang_representasi' => 'Uang Representasi', 'transport_lokal' => 'Transport Lokal', 'bbm' => 'BBM',
                    'total_spj' => 'Total SPJ', 'dana_taktis' => 'Dana Taktis',
                ] as $field => $label) {
                    if (!$this->sama((float) $sp[$field], (float) $dp[$field])) {
                        $masalah[] = sprintf('%s (%s) beda: sheet=Rp%s db=Rp%s', $label, $sp['nama_peserta'],
                            number_format((float) $sp[$field], 0, ',', '.'), number_format((float) $dp[$field], 0, ',', '.'));
                    }
                }

                // Tiket: cocokkan per leg via (maskapai, harga_tiket) — laporkan selisih jumlah & total.
                $dbTiketPeserta = $tiketByPeserta[$dp['id']] ?? [];
                if (count($sp['tiket']) !== count($dbTiketPeserta)) {
                    $masalah[] = sprintf('jumlah tiket (%s) beda: sheet=%d db=%d', $sp['nama_peserta'], count($sp['tiket']), count($dbTiketPeserta));
                } else {
                    $sheetTotalTiket = array_sum(array_column($sp['tiket'], 'harga_tiket'));
                    $dbTotalTiket = array_sum(array_column($dbTiketPeserta, 'harga_tiket'));
                    if (!$this->sama($sheetTotalTiket, $dbTotalTiket)) {
                        $masalah[] = sprintf('total harga tiket (%s) beda: sheet=Rp%s db=Rp%s', $sp['nama_peserta'],
                            number_format($sheetTotalTiket, 0, ',', '.'), number_format($dbTotalTiket, 0, ',', '.'));
                    }
                }

                // Hotel: bandingkan keberadaan + nama + total bill.
                $dbHotelPeserta = $hotelByPeserta[$dp['id']] ?? null;
                $sheetPunyaHotel = !empty($sp['hotel']);
                $dbPunyaHotel = $dbHotelPeserta !== null;
                if ($sheetPunyaHotel !== $dbPunyaHotel) {
                    $masalah[] = sprintf('data hotel (%s) beda: sheet %s, db %s', $sp['nama_peserta'],
                        $sheetPunyaHotel ? 'ADA' : 'TIDAK ADA', $dbPunyaHotel ? 'ADA' : 'TIDAK ADA');
                } elseif ($sheetPunyaHotel && $dbPunyaHotel) {
                    if ($this->normTeks($sp['hotel']['nama_hotel']) !== $this->normTeks($dbHotelPeserta['nama_hotel'])) {
                        $masalah[] = sprintf('nama hotel (%s) beda: sheet="%s" db="%s"', $sp['nama_peserta'], $sp['hotel']['nama_hotel'], $dbHotelPeserta['nama_hotel']);
                    }
                    $totalSheet = (float) $sp['hotel']['total_bill'] + (float) $sp['hotel']['total_biaya_30persen'];
                    $totalDb = (float) $dbHotelPeserta['total_bill'] + (float) $dbHotelPeserta['total_biaya_30persen'];
                    if (!$this->sama($totalSheet, $totalDb)) {
                        $masalah[] = sprintf('total biaya hotel (%s) beda: sheet=Rp%s db=Rp%s', $sp['nama_peserta'],
                            number_format($totalSheet, 0, ',', '.'), number_format($totalDb, 0, ',', '.'));
                    }
                }
            }
            foreach ($dbPesertaTrip as $i => $dp) {
                if (!$dbPesertaDipakai[$i]) {
                    $masalah[] = "peserta ADA DI DB TAPI TIDAK DI SHEET: {$dp['nama_peserta']} (peserta id #{$dp['id']})";
                }
            }

            if (empty($masalah)) {
                $tripCocok++;
            } else {
                $tripBermasalah[] = ['sheet' => $st, 'db_id' => $dbTripId, 'masalah' => $masalah];
            }
        }

        $tripEkstra = [];
        foreach ($dbTrips as $t) {
            if (!$dbDipakai[$t['id']]) $tripEkstra[] = $t;
        }

        CLI::write('=== Verifikasi Rekap Perjalanan Dinas vs Database ===', 'yellow');
        CLI::write("Sumber: {$path}");
        CLI::write('Total trip di sheet: ' . count($sheetTrips) . ' | Total trip di DB: ' . count($dbTrips));
        CLI::newLine();

        CLI::write('Trip cocok persis (semua peserta, nominal, tiket, hotel sama): ' . $tripCocok, 'green');
        CLI::write('Trip cocok no_surat_tugas TAPI ada beda di dalamnya: ' . count($tripBermasalah), empty($tripBermasalah) ? 'green' : 'red');
        CLI::write('Trip HILANG dari DB (ada di sheet, no_surat_tugas tidak ketemu di DB): ' . count($tripHilang), empty($tripHilang) ? 'green' : 'red');
        CLI::write('Trip ADA DI DB, TIDAK DI SHEET (kemungkinan duplikat/tidak lagi valid): ' . count($tripEkstra), empty($tripEkstra) ? 'green' : 'red');
        CLI::write('Trip di sheet TANPA no_surat_tugas (tidak bisa dicocokkan otomatis): ' . count($tripTanpaSurat), empty($tripTanpaSurat) ? 'green' : 'yellow');
        CLI::newLine();

        if (!empty($tripBermasalah)) {
            CLI::write('--- TRIP COCOK TAPI ADA BEDA DI DALAMNYA ---', 'red');
            foreach (array_slice($tripBermasalah, 0, 25) as $tb) {
                CLI::write(sprintf('  [%s] %s (DB trip #%d)', $tb['sheet']['no_surat_tugas'], mb_substr($tb['sheet']['maksud'] ?? '', 0, 60), $tb['db_id']));
                foreach ($tb['masalah'] as $m) CLI::write('    - ' . $m);
            }
            if (count($tripBermasalah) > 25) CLI::write('  ... dan ' . (count($tripBermasalah) - 25) . ' trip lain.');
            CLI::newLine();
        }

        if (!empty($tripHilang)) {
            CLI::write('--- TRIP HILANG DARI DB ---', 'red');
            foreach (array_slice($tripHilang, 0, 25) as $st) {
                CLI::write(sprintf('  [%s, %s] %s — %d peserta', $st['no_surat_tugas'], $st['tanggal_surat_tugas'], mb_substr($st['maksud'] ?? '', 0, 60), count($st['peserta'])));
            }
            if (count($tripHilang) > 25) CLI::write('  ... dan ' . (count($tripHilang) - 25) . ' trip lain.');
            CLI::newLine();
        }

        if (!empty($tripEkstra)) {
            CLI::write('--- TRIP ADA DI DB, TIDAK DI SHEET ---', 'red');
            foreach (array_slice($tripEkstra, 0, 25) as $t) {
                CLI::write(sprintf('  DB #%d [%s, %s] %s', $t['id'], $t['no_surat_tugas'] ?: '(kosong)', $t['tanggal_surat_tugas'], mb_substr($t['maksud'] ?? '', 0, 60)));
            }
            if (count($tripEkstra) > 25) CLI::write('  ... dan ' . (count($tripEkstra) - 25) . ' trip lain.');
            CLI::newLine();
        }

        if (!empty($tripTanpaSurat)) {
            CLI::write('--- TRIP DI SHEET TANPA NO_SURAT_TUGAS (tinjau manual) ---', 'yellow');
            foreach ($tripTanpaSurat as $st) {
                CLI::write(sprintf('  [%s] %s — %d peserta', $st['tanggal_surat_tugas'] ?: '(tanggal kosong)', mb_substr($st['maksud'] ?? '', 0, 70), count($st['peserta'])));
            }
            CLI::newLine();
        }

        $totalMasalah = count($tripBermasalah) + count($tripHilang) + count($tripEkstra);
        if ($totalMasalah === 0) {
            CLI::write('KESIMPULAN: Semua trip di sheet ketemu persis di database (di luar trip tanpa no_surat_tugas, kalau ada).', 'green');
        } else {
            CLI::write("KESIMPULAN: Ditemukan $totalMasalah trip bermasalah (lihat detail di atas). Tidak ada perubahan yang dibuat — ini murni laporan.", 'red');
        }
    }
}
