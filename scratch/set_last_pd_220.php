<?php
$pdo = new PDO("mysql:host=localhost;dbname=keu_internal_bbpompkp;charset=utf8mb4", "root", "");
$pdo->exec("DELETE FROM perjalanan_dinas_tiket WHERE peserta_id IN (SELECT id FROM perjalanan_dinas_peserta WHERE perjalanan_dinas_id = 285)");
$pdo->exec("DELETE FROM perjalanan_dinas_hotel WHERE peserta_id IN (SELECT id FROM perjalanan_dinas_peserta WHERE perjalanan_dinas_id = 285)");
$pdo->exec("DELETE FROM perjalanan_dinas_peserta WHERE perjalanan_dinas_id = 285");
$pdo->exec("DELETE FROM perjalanan_dinas WHERE id = 285");
echo "Deleted 221. Max no_pd is now: " . $pdo->query("SELECT MAX(CAST(no_pd AS UNSIGNED)) FROM perjalanan_dinas")->fetchColumn() . "\n";
$lastRow = $pdo->query("SELECT id, no_pd, maksud, tanggal_surat_tugas FROM perjalanan_dinas ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($lastRow);
