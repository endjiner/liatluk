<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootSpark($paths);

$db = \Config\Database::connect();
$orphans = $db->table('perjalanan_dinas_peserta')->whereIn('id', [511, 512, 513, 514, 515, 516, 517, 518, 519, 614, 615])->get()->getResultArray();
foreach ($orphans as $o) {
    echo "ID: {$o['id']}, TripID: {$o['perjalanan_dinas_id']}, Nama: {$o['nama_peserta']}, Taktis: {$o['dana_taktis']}, Status: {$o['status_lunas']}\n";
}
