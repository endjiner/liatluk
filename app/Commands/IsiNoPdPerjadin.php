<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasModel;

class IsiNoPdPerjadin extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:isi-no-pd';
    protected $description = 'Isi kolom no_pd pada tabel perjalanan_dinas dari spreadsheet Rekap PD 2026';

    public function run(array $params)
    {
        $csvFile = FCPATH . '../rekap_pd_2026.csv';
        if (!file_exists($csvFile)) {
            // Coba unduh jika belum ada
            CLI::write("Mengunduh sheet Rekap PD 2026...");
            $url = 'https://docs.google.com/spreadsheets/d/1ycXxhpUwIo2X_fcpna2cMBt49r4Q_Arq6Q2hcNiprH4/export?format=csv&gid=77856494';
            $content = file_get_contents($url);
            if (!$content) {
                CLI::error("Gagal mengunduh CSV dari Google Sheets.");
                return;
            }
            file_put_contents($csvFile, $content);
        }

        $f = fopen($csvFile, 'r');
        $rowNum = 0;
        $sheetTrips = [];
        $currentNo = null;

        while (($row = fgetcsv($f)) !== false) {
            $rowNum++;
            if ($rowNum <= 4) continue;

            $colNo = trim($row[0] ?? '');
            $colMaksud = trim($row[1] ?? '');
            $colStTgl = trim($row[2] ?? '');
            $colMak = trim($row[3] ?? '');

            if ($colNo !== '') {
                $cleanNo = preg_replace('/\s+/', ' ', $colNo);
                $currentNo = trim($cleanNo);
            }

            // Baris perjalanan dinas baru jika ada maksud atau nomor surat tugas
            if ($colMaksud !== '' || $colStTgl !== '') {
                // Pisahkan No. ST dan Tanggal: split pada " / " (spasi-slash-spasi)
                $stParts = preg_split('/\s+\/\s+/', $colStTgl);
                $st = trim($stParts[0] ?? '');

                $sheetTrips[] = [
                    'row'    => $rowNum,
                    'no_pd'  => $currentNo,
                    'st'     => $st,
                    'mak'    => $colMak,
                    'maksud' => $colMaksud,
                ];
            }
        }
        fclose($f);

        CLI::write("Total trips terdeteksi di sheet: " . count($sheetTrips));

        $dbModel = new PerjalananDinasModel();
        $dbTrips = $dbModel->findAll();
        CLI::write("Total trips di database: " . count($dbTrips));

        $norm = fn($s) => preg_replace('/\s+/', '', strtolower(trim((string)$s)));

        $dbByComp = [];
        $dbByST = [];
        foreach ($dbTrips as $t) {
            $stKey = $norm($t['no_surat_tugas']);
            $makKey = $norm($t['kode_mak']);
            if ($stKey !== '') {
                $dbByComp[$stKey . '|' . $makKey][] = $t;
                $dbByST[$stKey][] = $t;
            }
        }

        $matchedDbIds = [];
        $updates = []; // trip_id => no_pd

        foreach ($sheetTrips as $st) {
            $stKey = $norm($st['st']);
            $makKey = $norm($st['mak']);
            $target = null;

            if ($stKey !== '' && isset($dbByComp[$stKey . '|' . $makKey])) {
                foreach ($dbByComp[$stKey . '|' . $makKey] as $cand) {
                    if (!isset($matchedDbIds[$cand['id']])) {
                        $target = $cand;
                        break;
                    }
                }
            }
            if (!$target && $stKey !== '' && isset($dbByST[$stKey])) {
                foreach ($dbByST[$stKey] as $cand) {
                    if (!isset($matchedDbIds[$cand['id']])) {
                        $target = $cand;
                        break;
                    }
                }
            }

            if ($target) {
                $matchedDbIds[$target['id']] = true;
                $updates[$target['id']] = $st['no_pd'];
            }
        }

        CLI::write("Memperbarui no_pd untuk " . count($updates) . " trips di database...");
        $db = \Config\Database::connect();
        $db->transStart();
        $countUp = 0;
        foreach ($updates as $tripId => $noPd) {
            $dbModel->update($tripId, ['no_pd' => $noPd]);
            if ($noPd && stripos($noPd, 'UP') !== false) {
                $countUp++;
            }
        }
        $db->transComplete();

        CLI::write("Selesai! " . count($updates) . " trips diperbarui ($countUp diantaranya memiliki penanda UP).");

        // Tampilkan beberapa sampel
        $samples = $dbModel->where('no_pd IS NOT NULL', null, false)->orderBy('id', 'ASC')->findAll(10);
        CLI::write("\n--- Contoh Data Database Setelah Update ---");
        foreach ($samples as $s) {
            CLI::write("ID: {$s['id']} | No. PD: '{$s['no_pd']}' | ST: {$s['no_surat_tugas']}");
        }

        $samplesUP = $dbModel->like('no_pd', 'UP')->findAll();
        CLI::write("\n--- Semua Data No. PD dengan UP di Database ---");
        foreach ($samplesUP as $s) {
            CLI::write("ID: {$s['id']} | No. PD: '{$s['no_pd']}' | ST: {$s['no_surat_tugas']}");
        }
    }
}
