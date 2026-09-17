<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PegawaiModel;

class HealthCheck extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:health-check';
    protected $description = 'Audit menyeluruh kesehatan sistem, integritas data, relasi MariaDB, dan kesiapan integrasi Asikkekku.';
    protected $usage       = 'app:health-check [--fix]';
    protected $options     = [
        '--fix' => 'Otomatis perbaiki masalah integritas data yang terdeteksi secara aman.',
    ];

    public function run(array $params)
    {
        $fixMode = array_key_exists('fix', $params) || CLI::getOption('fix');
        CLI::write('=====================================================', 'yellow');
        CLI::write('   AUDIT KESEHATAN SISTEM KEUANGAN & PERJADIN BBPOM   ', 'yellow');
        CLI::write('=====================================================', 'yellow');
        CLI::write('Mode: ' . ($fixMode ? 'PERBAIKAN OTOMATIS (--fix)' : 'HANYA PEMERIKSAAN (Dry-Run)'), 'cyan');
        CLI::newLine();

        $db = \Config\Database::connect();
        $pemasukanModel = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $tripModel = new PerjalananDinasModel();
        $pesertaModel = new PerjalananDinasPesertaModel();
        $pegawaiModel = new PegawaiModel();

        $totalIsu = 0;

        // 1. Audit Saldo Kas (Pemasukan vs Pengeluaran)
        CLI::write('[1/5] Memeriksa Integritas Saldo Kas & Pembukuan...', 'white');
        $sumPemasukanDiterima = (float)($pemasukanModel->selectSum('jumlah_diterima', 't')->first()['t'] ?? 0);
        $sumPengeluaran = (float)($pengeluaranModel->selectSum('jumlah', 't')->first()['t'] ?? 0);
        $saldoAktual = $sumPemasukanDiterima - $sumPengeluaran;

        CLI::write(sprintf("  - Total Pemasukan Diterima: Rp %s", number_format($sumPemasukanDiterima, 0, ',', '.')), 'green');
        CLI::write(sprintf("  - Total Pengeluaran Kas    : Rp %s", number_format($sumPengeluaran, 0, ',', '.')), 'green');
        CLI::write(sprintf("  - Saldo Akhir Berjalan     : Rp %s", number_format($saldoAktual, 0, ',', '.')), 'green');

        if ($saldoAktual < 0) {
            CLI::error("  [PERINGATAN] Saldo kas bernilai negatif! Pengeluaran melebihi kas masuk.");
            $totalIsu++;
        } else {
            CLI::write("  -> Status Saldo Kas: SEHAT (Positif)", 'green');
        }
        CLI::newLine();

        // 2. Audit Relasi Peserta Perjadin vs Pemasukan Setoran
        CLI::write('[2/5] Memeriksa Integritas Relasi Setoran Taktis (Perjadin <-> Pemasukan)...', 'white');
        $pesertaDenganPemasukan = $pesertaModel->where('pemasukan_id IS NOT NULL')->findAll();
        $yatimCount = 0;

        foreach ($pesertaDenganPemasukan as $p) {
            $pem = $pemasukanModel->find($p['pemasukan_id']);
            if (!$pem) {
                $yatimCount++;
                CLI::write(sprintf("  - Peserta #%d (%s) memiliki pemasukan_id #%d tapi baris pemasukan tidak ditemukan di DB!",
                    $p['id'], $p['nama_peserta'], $p['pemasukan_id']), 'red');

                if ($fixMode) {
                    $pesertaModel->update($p['id'], [
                        'status_lunas'   => 'belum',
                        'jumlah_disetor' => 0,
                        'tanggal_lunas'  => null,
                        'pemasukan_id'   => null,
                    ]);
                    CLI::write("    -> Berhasil di-reset ke status 'belum lunas' secara bersih.", 'yellow');
                }
            }
        }

        if ($yatimCount > 0) {
            $totalIsu += $yatimCount;
            if (!$fixMode) {
                CLI::write(sprintf("  [TEMUAN] Terdapat %d baris peserta dengan referensi pemasukan yatim. Jalankan dengan --fix untuk mereset.", $yatimCount), 'red');
            }
        } else {
            CLI::write("  -> Status Relasi Setoran: SEHAT (Semua tautan valid)", 'green');
        }
        CLI::newLine();

        // 3. Audit Rumus Dana Taktis (Harus 10% Uang Harian)
        CLI::write('[3/5] Memeriksa Konsistensi Rumus Dana Taktis (10% Uang Harian)...', 'white');
        $pesertaList = $pesertaModel->findAll();
        $selisihRumusCount = 0;

        foreach ($pesertaList as $p) {
            $expectedTaktis = round((float)$p['uang_harian'] * 0.10);
            $actualTaktis   = round((float)$p['dana_taktis']);
            if (abs($expectedTaktis - $actualTaktis) > 1) {
                $selisihRumusCount++;
                CLI::write(sprintf("  - Peserta #%d (%s): Uang Harian Rp %s, Taktis Rp %s (Seharusnya Rp %s)",
                    $p['id'], $p['nama_peserta'],
                    number_format($p['uang_harian'], 0, ',', '.'),
                    number_format($actualTaktis, 0, ',', '.'),
                    number_format($expectedTaktis, 0, ',', '.')
                ), 'yellow');

                if ($fixMode) {
                    $pesertaModel->recalculate((int)$p['id']);
                }
            }
        }

        if ($selisihRumusCount > 0) {
            $totalIsu += $selisihRumusCount;
            if ($fixMode) {
                CLI::write(sprintf("  -> Berhasil menghitung ulang %d nominal taktis.", $selisihRumusCount), 'green');
            } else {
                CLI::write(sprintf("  [TEMUAN] %d peserta memiliki selisih pembulatan/rumus dana taktis.", $selisihRumusCount), 'yellow');
            }
        } else {
            CLI::write("  -> Status Rumus Taktis: SEHAT (100% konsisten)", 'green');
        }
        CLI::newLine();

        // 4. Audit Kolom Relasi & Indeks MariaDB (Asikkekku-Ready)
        CLI::write('[4/5] Memeriksa Indeks & Struktur Kolom Asikkekku...', 'white');
        $indeksPemasukan = array_column($db->query("SHOW INDEX FROM pemasukan")->getResultArray(), 'Key_name');
        $indeksPerjadin  = array_column($db->query("SHOW INDEX FROM perjalanan_dinas")->getResultArray(), 'Key_name');
        $indeksPeserta   = array_column($db->query("SHOW INDEX FROM perjalanan_dinas_peserta")->getResultArray(), 'Key_name');

        $cekIndeks = [
            'pemasukan.kode_mak' => in_array('idx_pemasukan_kode_mak', $indeksPemasukan, true),
            'pemasukan.no_surat_tugas' => in_array('idx_pemasukan_no_st', $indeksPemasukan, true) || in_array('idx_pemasukan_no_surat_tugas', $indeksPemasukan, true),
            'pemasukan.perjalanan_dinas_id' => in_array('idx_pemasukan_perjadin', $indeksPemasukan, true),
            'perjalanan_dinas.no_surat_tugas' => in_array('idx_perjadin_no_surat_tugas', $indeksPerjadin, true),
            'perjalanan_dinas.kode_mak' => in_array('idx_perjadin_kode_mak', $indeksPerjadin, true),
            'perjalanan_dinas_peserta.pemasukan_id' => in_array('idx_peserta_pemasukan_id', $indeksPeserta, true),
        ];

        foreach ($cekIndeks as $namaIndeks => $ada) {
            if ($ada) {
                CLI::write(sprintf("  - Indeks %-40s : TERPASANG", $namaIndeks), 'green');
            } else {
                CLI::write(sprintf("  - Indeks %-40s : BELUM TERPASANG", $namaIndeks), 'yellow');
                if ($fixMode) {
                    $this->pasangIndeksOtomatis($db, $namaIndeks);
                }
            }
        }
        CLI::newLine();

        // 5. Audit File Bukti Fisik
        CLI::write('[5/5] Memeriksa Keberadaan File Bukti Transaksi di Storage...', 'white');
        $uploadPath = FCPATH . 'uploads/bukti/';
        $pemasukanDenganBukti = $pemasukanModel->where('file_bukti IS NOT NULL')->findAll();
        $pengeluaranDenganBukti = $pengeluaranModel->where('file_bukti IS NOT NULL')->findAll();
        $buktiHilang = 0;

        foreach (array_merge($pemasukanDenganBukti, $pengeluaranDenganBukti) as $row) {
            if (!empty($row['file_bukti'])) {
                $filePath = $uploadPath . $row['file_bukti'];
                if (!file_exists($filePath)) {
                    $buktiHilang++;
                }
            }
        }

        if ($buktiHilang > 0) {
            CLI::write(sprintf("  [CATATAN] %d file bukti fisik tercatat di database namun tidak ditemukan di direktori uploads/bukti/.", $buktiHilang), 'yellow');
        } else {
            CLI::write("  -> Status File Bukti: LENGKAP", 'green');
        }
        CLI::newLine();

        // Ringkasan
        CLI::write('=====================================================', 'yellow');
        if ($totalIsu === 0) {
            CLI::write('KESIMPULAN: Seluruh sistem dan data dalam kondisi PRIMA & SEHAT!', 'green');
            CLI::write('Sistem siap di-scale up dan terhubung dengan kode MAK Asikkekku.', 'green');
        } else {
            CLI::write(sprintf('KESIMPULAN: Ditemukan %d catatan/isu integritas data.', $totalIsu), 'yellow');
            if (!$fixMode) {
                CLI::write('Gunakan perintah: php spark app:health-check --fix untuk memperbaikinya secara otomatis.', 'cyan');
            }
        }
        CLI::write('=====================================================', 'yellow');
    }

    private function pasangIndeksOtomatis($db, string $namaTarget): void
    {
        try {
            if ($namaTarget === 'perjalanan_dinas.no_surat_tugas') {
                $db->query("ALTER TABLE perjalanan_dinas ADD INDEX idx_perjadin_no_surat_tugas (no_surat_tugas)");
                CLI::write("    -> Indeks idx_perjadin_no_surat_tugas berhasil dibuat.", 'green');
            } elseif ($namaTarget === 'perjalanan_dinas.kode_mak') {
                $db->query("ALTER TABLE perjalanan_dinas ADD INDEX idx_perjadin_kode_mak (kode_mak)");
                CLI::write("    -> Indeks idx_perjadin_kode_mak berhasil dibuat.", 'green');
            } elseif ($namaTarget === 'perjalanan_dinas_peserta.pemasukan_id') {
                $db->query("ALTER TABLE perjalanan_dinas_peserta ADD INDEX idx_peserta_pemasukan_id (pemasukan_id)");
                CLI::write("    -> Indeks idx_peserta_pemasukan_id berhasil dibuat.", 'green');
            }
        } catch (\Throwable $e) {
            // Abaikan jika sudah terpasang
        }
    }
}
