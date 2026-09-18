<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;

/**
 * Bandingkan buku kas "Realisasi Taktis" (sumber kebenaran — di data/kas_taktis_ledger.json,
 * diambil dari spreadsheet Excel yang sudah diverifikasi konsisten: saldo berjalannya cocok
 * persis di 920 baris) dengan isi tabel pemasukan+pengeluaran yang sebenarnya di database.
 * Read-only, tidak pernah mengubah apa pun — cuma melaporkan.
 *
 * Setiap baris ledger dicocokkan ke SATU baris DB lewat kunci (tanggal, jumlah) dulu — dua
 * kolom yang paling kecil kemungkinan salah ketik — lalu dalam kelompok itu dicoba cocok
 * PERSIS (kategori+sumber/tujuan+keterangan sama persis), baru kalau tidak ketemu dicoba
 * cocok LONGGAR (kategori sama setelah dinormalisasi, dan sumber/tujuan ATAU keterangan sama
 * setelah dinormalisasi) — supaya beda spasi/huruf besar-kecil tidak dianggap hilang padahal
 * cuma beda ketik. Baris DB yang tersisa (tidak kepakai) dalam rentang tanggal ledger berarti
 * ADA DI DATABASE TAPI TIDAK ADA DI LEDGER — kemungkinan besar duplikat dari import berulang.
 */
class VerifyKasTaktisLedger extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:verify-kas-taktis-ledger';
    protected $description = 'Bandingkan buku kas Realisasi Taktis (ledger sumber) dengan tabel pemasukan+pengeluaran — cari yang hilang/duplikat.';

    private function normalisasi(?string $s): string
    {
        $s = strtolower((string) $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /** @return array{0: array, 1: string} [data baris ternormalisasi, sisi 'pemasukan'|'pengeluaran'] */
    private function normalisasiLedgerRow(array $r): array
    {
        $sisi = $r['pemasukan'] !== null ? 'pemasukan' : 'pengeluaran';
        $jumlah = $sisi === 'pemasukan' ? $r['pemasukan'] : $r['pengeluaran'];
        return [[
            'tanggal'       => $r['tanggal'],
            'jumlah'        => (int) round((float) $jumlah),
            'kategori'      => (string) $r['kategori'],
            'sumber_tujuan' => (string) ($r['sumber_tujuan'] ?? ''),
            'keterangan'    => (string) ($r['keterangan'] ?? ''),
        ], $sisi];
    }

    public function run(array $params)
    {
        $path = __DIR__ . '/data/kas_taktis_ledger.json';
        $ledger = json_decode(file_get_contents($path), true);
        if (!is_array($ledger)) {
            CLI::error("Gagal membaca ledger di $path");
            return;
        }

        $tanggalMaxLedger = max(array_column($ledger, 'tanggal'));

        $pemasukanModel   = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $dbPemasukan      = $pemasukanModel->select('id, tanggal, kategori, jumlah, sumber, keterangan')->findAll();
        $dbPengeluaran    = $pengeluaranModel->select('id, tanggal, kategori, jumlah, tujuan, keterangan')->findAll();

        // Kelompokkan baris DB per (tanggal|jumlah) supaya pencarian tidak perlu O(n*m) penuh.
        $bucket = ['pemasukan' => [], 'pengeluaran' => []];
        foreach ($dbPemasukan as $row) {
            $key = $row['tanggal'] . '|' . (int) round((float) $row['jumlah']);
            $bucket['pemasukan'][$key][] = [
                'id' => $row['id'], 'tanggal' => $row['tanggal'], 'jumlah' => (int) round((float) $row['jumlah']),
                'kategori' => $row['kategori'], 'sumber_tujuan' => $row['sumber'], 'keterangan' => $row['keterangan'],
                'dipakai' => false,
            ];
        }
        foreach ($dbPengeluaran as $row) {
            $key = $row['tanggal'] . '|' . (int) round((float) $row['jumlah']);
            $bucket['pengeluaran'][$key][] = [
                'id' => $row['id'], 'tanggal' => $row['tanggal'], 'jumlah' => (int) round((float) $row['jumlah']),
                'kategori' => $row['kategori'], 'sumber_tujuan' => $row['tujuan'], 'keterangan' => $row['keterangan'],
                'dipakai' => false,
            ];
        }

        $cocokPersis = ['pemasukan' => 0, 'pengeluaran' => 0];
        $cocokLonggar = ['pemasukan' => [], 'pengeluaran' => []];
        $kategoriBeda = ['pemasukan' => [], 'pengeluaran' => []];
        $renameMap = ['pemasukan' => [], 'pengeluaran' => []];
        $hilang = ['pemasukan' => [], 'pengeluaran' => []];

        // Sum per kategori untuk sanity check cepat di laporan — HANYA baris DB dengan tanggal
        // di dalam rentang ledger, supaya transaksi baru (setelah ledger berakhir) tidak bikin
        // total DB kelihatan "lebih besar, tapi wajar" padahal aslinya duplikat.
        $sumLedger = ['pemasukan' => [], 'pengeluaran' => []];
        $sumDb     = ['pemasukan' => [], 'pengeluaran' => []];
        foreach ($dbPemasukan as $row) {
            if ($row['tanggal'] > $tanggalMaxLedger) continue;
            $sumDb['pemasukan'][$row['kategori']] = ($sumDb['pemasukan'][$row['kategori']] ?? 0) + (float) $row['jumlah'];
        }
        foreach ($dbPengeluaran as $row) {
            if ($row['tanggal'] > $tanggalMaxLedger) continue;
            $sumDb['pengeluaran'][$row['kategori']] = ($sumDb['pengeluaran'][$row['kategori']] ?? 0) + (float) $row['jumlah'];
        }

        foreach ($ledger as $r) {
            [$L, $sisi] = $this->normalisasiLedgerRow($r);
            $sumLedger[$sisi][$L['kategori']] = ($sumLedger[$sisi][$L['kategori']] ?? 0) + $L['jumlah'];

            $key = $L['tanggal'] . '|' . $L['jumlah'];
            $kandidat = &$bucket[$sisi][$key];
            if (empty($kandidat)) $kandidat = [];

            // Pass 1: cocok PERSIS (semua field sama persis, tidak dinormalisasi).
            $idxPersis = null;
            foreach ($kandidat as $i => $db) {
                if ($db['dipakai']) continue;
                if ($db['kategori'] === $L['kategori'] && $db['sumber_tujuan'] === $L['sumber_tujuan'] && $db['keterangan'] === $L['keterangan']) {
                    $idxPersis = $i;
                    break;
                }
            }
            if ($idxPersis !== null) {
                $kandidat[$idxPersis]['dipakai'] = true;
                $cocokPersis[$sisi]++;
                continue;
            }

            // Pass 2: cocok LONGGAR (kategori normal sama, DAN sumber/tujuan ATAU keterangan normal sama).
            $idxLonggar = null;
            foreach ($kandidat as $i => $db) {
                if ($db['dipakai']) continue;
                $kategoriSama = $this->normalisasi($db['kategori']) === $this->normalisasi($L['kategori']);
                $sumberSama   = $this->normalisasi($db['sumber_tujuan']) === $this->normalisasi($L['sumber_tujuan']);
                $ketSama      = $this->normalisasi($db['keterangan']) === $this->normalisasi($L['keterangan']);
                if ($kategoriSama && ($sumberSama || $ketSama)) {
                    $idxLonggar = $i;
                    break;
                }
            }
            if ($idxLonggar !== null) {
                $kandidat[$idxLonggar]['dipakai'] = true;
                $cocokLonggar[$sisi][] = ['ledger' => $L, 'db' => $kandidat[$idxLonggar]];
                continue;
            }

            // Pass 3: sama tanggal+jumlah+(sumber/tujuan ATAU keterangan), TAPI kategori
            // beda — kemungkinan besar kategorinya di-rename/direorganisasi di kemudian
            // hari (kategori cuma teks bebas, bukan dropdown tetap), bukan baris hilang.
            $idxKategoriBeda = null;
            foreach ($kandidat as $i => $db) {
                if ($db['dipakai']) continue;
                $sumberSama = $this->normalisasi($db['sumber_tujuan']) === $this->normalisasi($L['sumber_tujuan']);
                $ketSama    = $this->normalisasi($db['keterangan']) === $this->normalisasi($L['keterangan']);
                if ($sumberSama || $ketSama) {
                    $idxKategoriBeda = $i;
                    break;
                }
            }
            if ($idxKategoriBeda !== null) {
                $kandidat[$idxKategoriBeda]['dipakai'] = true;
                $kategoriBeda[$sisi][] = ['ledger' => $L, 'db' => $kandidat[$idxKategoriBeda]];
                $renameMap[$sisi][$L['kategori'] . ' -> ' . $kandidat[$idxKategoriBeda]['kategori']] =
                    ($renameMap[$sisi][$L['kategori'] . ' -> ' . $kandidat[$idxKategoriBeda]['kategori']] ?? 0) + 1;
                continue;
            }

            $hilang[$sisi][] = $L;
        }
        unset($kandidat);

        // Sisa baris DB yang tidak kepakai sama sekali = ada di DB tapi tidak di ledger.
        $ekstra = ['pemasukan' => [], 'pengeluaran' => []];
        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            foreach ($bucket[$sisi] as $rows) {
                foreach ($rows as $db) {
                    if (!$db['dipakai']) $ekstra[$sisi][] = $db;
                }
            }
        }
        // Pisahkan yang tanggalnya SETELAH ledger berakhir — itu wajar (transaksi baru setelah
        // spreadsheet ini terakhir diupdate), bukan berarti duplikat/bermasalah.
        $ekstraBaru = ['pemasukan' => [], 'pengeluaran' => []];
        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            $ekstra[$sisi] = array_values(array_filter($ekstra[$sisi], function ($db) use (&$ekstraBaru, $sisi, $tanggalMaxLedger) {
                if ($db['tanggal'] > $tanggalMaxLedger) {
                    $ekstraBaru[$sisi][] = $db;
                    return false;
                }
                return true;
            }));
        }

        CLI::write('=== Verifikasi Ledger Realisasi Taktis vs Database ===', 'yellow');
        CLI::write("Ledger: {$path}");
        CLI::write('Total baris ledger: ' . count($ledger) . ' (rentang tanggal s.d. ' . $tanggalMaxLedger . ')');
        CLI::newLine();

        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            CLI::write("--- " . strtoupper($sisi) . " ---", 'yellow');
            CLI::write("  Cocok persis  : {$cocokPersis[$sisi]}");
            CLI::write("  Cocok (teks beda dikit): " . count($cocokLonggar[$sisi]));
            CLI::write("  Cocok (kategori beda, kemungkinan di-rename): " . count($kategoriBeda[$sisi]));
            CLI::write("  HILANG dari DB: " . count($hilang[$sisi]), empty($hilang[$sisi]) ? 'green' : 'red');
            CLI::write("  ADA DI DB, TIDAK DI LEDGER (dalam rentang tanggal ledger): " . count($ekstra[$sisi]), empty($ekstra[$sisi]) ? 'green' : 'red');
            CLI::write("  Di DB, tanggal setelah ledger berakhir (wajar, transaksi baru): " . count($ekstraBaru[$sisi]));
            CLI::newLine();
        }

        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            if (!empty($renameMap[$sisi])) {
                CLI::write('--- KEMUNGKINAN KATEGORI DI-RENAME (' . strtoupper($sisi) . ') — data sama persis, cuma label kategori beda ---', 'cyan');
                arsort($renameMap[$sisi]);
                foreach ($renameMap[$sisi] as $pair => $count) {
                    CLI::write("  {$pair}  ({$count}x)");
                }
                CLI::newLine();
            }
        }

        // Sanity check total per kategori — DB di sini sudah dibatasi ke rentang tanggal ledger
        // (lihat perhitungan $sumDb di atas), jadi SELISIH APA PUN (lebih besar atau lebih
        // kecil) berarti ada masalah nyata: lebih besar = kemungkinan duplikat, lebih kecil =
        // kemungkinan ada yang hilang. Tidak ada alasan sah untuk beda dalam rentang ini.
        CLI::write('--- Perbandingan total per kategori (ledger vs DB, HANYA dalam rentang tanggal ledger) ---', 'yellow');
        CLI::write('  Kalau sepasang kategori di sini saling melengkapi total yang sama dengan kategori lain', 'yellow');
        CLI::write('  (lihat "KEMUNGKINAN KATEGORI DI-RENAME" di atas kalau ada) — itu bukan data hilang,', 'yellow');
        CLI::write('  cuma nama kategorinya beda antara ledger dan database.', 'yellow');
        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            $semuaKategori = array_unique(array_merge(array_keys($sumLedger[$sisi]), array_keys($sumDb[$sisi])));
            sort($semuaKategori);
            foreach ($semuaKategori as $kat) {
                $sl = $sumLedger[$sisi][$kat] ?? 0;
                $sd = $sumDb[$sisi][$kat] ?? 0;
                $cocok = abs($sl - $sd) < 1;
                CLI::write(sprintf('  [%s] %s: ledger Rp%s vs DB Rp%s', $sisi, $kat, number_format($sl, 0, ',', '.'), number_format($sd, 0, ',', '.')), $cocok ? 'green' : 'red');
            }
        }
        CLI::newLine();

        foreach (['pemasukan', 'pengeluaran'] as $sisi) {
            if (!empty($hilang[$sisi])) {
                CLI::write('--- HILANG DARI DB (' . strtoupper($sisi) . ') — ada di ledger, tidak ketemu sama sekali di DB ---', 'red');
                foreach (array_slice($hilang[$sisi], 0, 40) as $L) {
                    CLI::write(sprintf(
                        '  [%s, Rp%s, %s] "%s" — %s',
                        $L['tanggal'], number_format($L['jumlah'], 0, ',', '.'), $L['kategori'], $L['sumber_tujuan'], $L['keterangan']
                    ));
                }
                if (count($hilang[$sisi]) > 40) CLI::write('  ... dan ' . (count($hilang[$sisi]) - 40) . ' baris lain.');
                CLI::newLine();
            }
            if (!empty($ekstra[$sisi])) {
                CLI::write('--- ADA DI DB, TIDAK DI LEDGER (' . strtoupper($sisi) . ') — kemungkinan duplikat ---', 'red');
                foreach (array_slice($ekstra[$sisi], 0, 40) as $db) {
                    CLI::write(sprintf(
                        '  DB #%d [%s, Rp%s, %s] "%s" — %s',
                        $db['id'], $db['tanggal'], number_format($db['jumlah'], 0, ',', '.'), $db['kategori'], $db['sumber_tujuan'], $db['keterangan']
                    ));
                }
                if (count($ekstra[$sisi]) > 40) CLI::write('  ... dan ' . (count($ekstra[$sisi]) - 40) . ' baris lain.');
                CLI::newLine();
            }
            if (!empty($cocokLonggar[$sisi])) {
                CLI::write('--- COCOK TAPI TEKSNYA BEDA DIKIT (' . strtoupper($sisi) . ') — cuma informasi, bukan masalah ---', 'cyan');
                foreach (array_slice($cocokLonggar[$sisi], 0, 15) as $pair) {
                    CLI::write(sprintf('  [%s, Rp%s] Ledger: "%s" / "%s"  <->  DB #%d: "%s" / "%s"',
                        $pair['ledger']['tanggal'], number_format($pair['ledger']['jumlah'], 0, ',', '.'),
                        $pair['ledger']['kategori'], $pair['ledger']['sumber_tujuan'],
                        $pair['db']['id'], $pair['db']['kategori'], $pair['db']['sumber_tujuan']
                    ));
                }
                if (count($cocokLonggar[$sisi]) > 15) CLI::write('  ... dan ' . (count($cocokLonggar[$sisi]) - 15) . ' baris lain.');
                CLI::newLine();
            }
        }

        $totalMasalah = count($hilang['pemasukan']) + count($hilang['pengeluaran']) + count($ekstra['pemasukan']) + count($ekstra['pengeluaran']);
        if ($totalMasalah === 0) {
            CLI::write('KESIMPULAN: Semua baris ledger ketemu persis di database, tidak ada yang hilang atau duplikat.', 'green');
        } else {
            CLI::write("KESIMPULAN: Ditemukan $totalMasalah baris bermasalah (lihat detail di atas). Tidak ada perubahan yang dibuat — ini murni laporan.", 'red');
        }
    }
}
