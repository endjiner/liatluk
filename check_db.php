<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootSpark($paths);

$db = \Config\Database::connect();

echo "=== TOP 10 PERJALANAN DINAS BY TANGGAL ASC ===\n";
$trips = $db->table('perjalanan_dinas')->select('id, no_pd, tanggal_surat_tugas, no_surat_tugas, maksud')->orderBy('tanggal_surat_tugas', 'ASC')->limit(10)->get()->getResultArray();
foreach ($trips as $t) {
    echo "ID: {$t['id']}, NO_PD: {$t['no_pd']}, TGL: {$t['tanggal_surat_tugas']}, NO_ST: {$t['no_surat_tugas']}\n";
}

echo "\n=== TOP 10 PERJALANAN DINAS BY ID ASC ===\n";
$tripsById = $db->table('perjalanan_dinas')->select('id, no_pd, tanggal_surat_tugas, no_surat_tugas')->orderBy('id', 'ASC')->limit(10)->get()->getResultArray();
foreach ($tripsById as $t) {
    echo "ID: {$t['id']}, NO_PD: {$t['no_pd']}, TGL: {$t['tanggal_surat_tugas']}, NO_ST: {$t['no_surat_tugas']}\n";
}

echo "\n=== DANA TAKTIS SUMMARY CHECK ===\n";
$dtBelum = $db->table('perjalanan_dinas_peserta')
    ->selectSum('dana_taktis', 'total_taktis')
    ->selectSum('jumlah_disetor', 'total_disetor')
    ->where('status_lunas !=', 'lunas')
    ->get()->getRowArray();
echo "Overall Belum Lunas (dana_taktis - jumlah_disetor): " . ((float)$dtBelum['total_taktis'] - (float)$dtBelum['total_disetor']) . "\n";
echo "Overall Total Taktis Belum Lunas: " . ((float)$dtBelum['total_taktis']) . "\n";

$dtAll = $db->table('perjalanan_dinas_peserta')
    ->selectSum('dana_taktis', 'total_taktis')
    ->get()->getRowArray();
echo "Overall Total Dana Taktis: " . ((float)$dtAll['total_taktis']) . "\n";

// In Laporan for period 2015-10 to 2026-09
$laporanModel = new \App\Models\PerjalananDinasPesertaModel();
$rowsLap = $laporanModel->getForLaporan('2015-10-01', '2026-09-30');
$sumTaktisLap = array_sum(array_column($rowsLap, 'dana_taktis'));
$sumBelumLap = 0;
foreach ($rowsLap as $r) {
    if ($r['status_lunas'] !== 'lunas') {
        $sumBelumLap += ((float)$r['dana_taktis'] - (float)($r['jumlah_disetor'] ?? 0));
    }
}
$sumBelumLapRaw = 0;
foreach ($rowsLap as $r) {
    if ($r['status_lunas'] !== 'lunas') {
        $sumBelumLapRaw += (float)$r['dana_taktis'];
    }
}
echo "\nLaporan rows count: " . count($rowsLap) . "\n";
echo "Laporan sum dana_taktis: {$sumTaktisLap}\n";
echo "Laporan sum belum lunas (raw dana_taktis): {$sumBelumLapRaw}\n";
echo "Laporan sum belum lunas (netto - jumlah_disetor): {$sumBelumLap}\n";
