<?php
$pdo = new PDO("mysql:host=localhost;dbname=keu_internal_bbpompkp;charset=utf8mb4", "root", "");

// Total records
$total = $pdo->query("SELECT COUNT(*) FROM perjalanan_dinas_peserta JOIN perjalanan_dinas ON perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id")->fetchColumn();
echo "Total peserta: $total\n";

// Last page (limit 10)
$perPage = 10;
$totalPages = ceil($total / $perPage);
$offset = ($totalPages - 1) * $perPage;

echo "Total Pages: $totalPages, Offset: $offset\n";

$sql = "SELECT p.id, pd.no_pd, p.nama_peserta, pd.tanggal_surat_tugas, p.dana_taktis, p.status_lunas 
        FROM perjalanan_dinas_peserta p 
        JOIN perjalanan_dinas pd ON pd.id = p.perjalanan_dinas_id 
        ORDER BY pd.id ASC, p.id ASC 
        LIMIT $perPage OFFSET $offset";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "=== LAST PAGE ROWS (ORDER BY pd.id ASC) ===\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | No PD: {$r['no_pd']} | Nama: {$r['nama_peserta']} | Tgl: {$r['tanggal_surat_tugas']} | Taktis: {$r['dana_taktis']} | Status: {$r['status_lunas']}\n";
}
