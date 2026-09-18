<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootSpark($paths);

$db = \Config\Database::connect();

// All belum lunas in database
$allBelum = $db->table('perjalanan_dinas_peserta')
    ->select('perjalanan_dinas_peserta.*, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.no_pd, perjalanan_dinas.no_surat_tugas')
    ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
    ->where('perjalanan_dinas_peserta.status_lunas !=', 'lunas')
    ->where('perjalanan_dinas_peserta.dana_taktis >', 0)
    ->get()->getResultArray();

echo "Total belum lunas rows in DB: " . count($allBelum) . "\n";
$missing = [];
foreach ($allBelum as $b) {
    $tgl = $b['tanggal_surat_tugas'];
    if ($tgl < '2015-10-01' || $tgl > '2026-09-30') {
        $missing[] = $b;
    }
}
echo "Rows outside 2015-10-01 s.d. 2026-09-30: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "ID: {$m['id']}, TripID: {$m['perjalanan_dinas_id']}, NoPD: {$m['no_pd']}, Tgl: {$m['tanggal_surat_tugas']}, Nama: {$m['nama_peserta']}, Taktis: {$m['dana_taktis']}, Status: {$m['status_lunas']}\n";
}

// Check trips with no date or NULL date or weird date
$nullTrips = $db->table('perjalanan_dinas')
    ->where('tanggal_surat_tugas IS NULL')
    ->orWhere('tanggal_surat_tugas', '')
    ->orWhere('tanggal_surat_tugas', '0000-00-00')
    ->get()->getResultArray();
echo "Trips with NULL/empty date: " . count($nullTrips) . "\n";

// Check peserta with no perjalanan_dinas join (orphan peserta)
$orphanPeserta = $db->table('perjalanan_dinas_peserta')
    ->select('perjalanan_dinas_peserta.*')
    ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id', 'left')
    ->where('perjalanan_dinas.id IS NULL')
    ->get()->getResultArray();
echo "Orphan peserta: " . count($orphanPeserta) . "\n";
foreach ($orphanPeserta as $op) {
    echo "Orphan: ID {$op['id']}, Nama: {$op['nama_peserta']}, Taktis: {$op['dana_taktis']}\n";
}
