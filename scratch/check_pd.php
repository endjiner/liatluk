<?php
$pdo = new PDO("mysql:host=localhost;dbname=keu_internal_bbpompkp;charset=utf8mb4", "root", "");
$stmt = $pdo->query("SELECT id, no_pd, no_surat_tugas, maksud FROM perjalanan_dinas WHERE id BETWEEN 260 AND 270");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
