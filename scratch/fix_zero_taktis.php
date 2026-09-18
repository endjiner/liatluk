<?php
$pdo = new PDO("mysql:host=localhost;dbname=keu_internal_bbpompkp;charset=utf8mb4", "root", "");
$count = $pdo->query("SELECT COUNT(*) FROM perjalanan_dinas_peserta WHERE dana_taktis <= 0 AND status_lunas != 'lunas'")->fetchColumn();
echo "Peserta with dana_taktis <= 0 and status_lunas != 'lunas': $count\n";

if ($count > 0) {
    $updated = $pdo->exec("UPDATE perjalanan_dinas_peserta SET status_lunas = 'lunas' WHERE dana_taktis <= 0 AND status_lunas != 'lunas'");
    echo "Updated $updated rows to status_lunas = 'lunas'.\n";
}
