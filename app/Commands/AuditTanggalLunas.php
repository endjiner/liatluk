<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasPesertaModel;

/**
 * Cek SEMUA peserta yang sudah berstatus lunas & tertaut ke Pemasukan — apapun cara
 * penautannya (tombol "Lunas" manual di halaman Perjalanan Dinas, ataupun
 * app:reconcile-setoran-taktis --apply) — dan tandai kalau tanggal Pemasukan-nya
 * (tanggal setoran) justru SEBELUM tanggal trip (tanggal_surat_tugas). Itu mustahil
 * secara kronologis (tidak bisa menyetor untuk trip yang belum terjadi) dan berarti
 * tautannya kemungkinan salah.
 */
class AuditTanggalLunas extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:audit-tanggal-lunas';
    protected $description = 'Cek semua peserta lunas yang tertaut ke Pemasukan — tandai kalau tanggal setoran SEBELUM tanggal trip (mustahil).';

    public function run(array $params)
    {
        $pesertaModel = new PerjalananDinasPesertaModel();

        $rows = $pesertaModel
            ->select('perjalanan_dinas_peserta.*, perjalanan_dinas.tanggal_surat_tugas, pemasukan.tanggal AS tanggal_pemasukan, pemasukan.sumber')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->join('pemasukan', 'pemasukan.id = perjalanan_dinas_peserta.pemasukan_id')
            ->where('perjalanan_dinas_peserta.status_lunas', 'lunas')
            ->where('perjalanan_dinas_peserta.pemasukan_id IS NOT NULL', null, false)
            ->findAll();

        CLI::write('=== Audit Tanggal Lunas ===', 'yellow');
        CLI::write('Total peserta berstatus lunas & tertaut ke Pemasukan: ' . count($rows));
        CLI::newLine();

        $bermasalah = array_values(array_filter(
            $rows,
            static fn($r) => $r['tanggal_pemasukan'] < $r['tanggal_surat_tugas']
        ));

        if (empty($bermasalah)) {
            CLI::write('Tidak ada yang bermasalah — semua tanggal setoran >= tanggal trip.', 'green');
            return;
        }

        CLI::write('DITEMUKAN ' . count($bermasalah) . ' TAUTAN MENCURIGAKAN (tanggal setoran SEBELUM tanggal trip):', 'red');
        foreach ($bermasalah as $r) {
            $selisihHari = (int) round((strtotime($r['tanggal_surat_tugas']) - strtotime($r['tanggal_pemasukan'])) / 86400);
            CLI::write(sprintf(
                '  Peserta #%d (%s, trip #%d tgl %s) <- Pemasukan #%d [%s, disetor %s] — %d hari SEBELUM trip',
                $r['id'],
                $r['nama_peserta'],
                $r['perjalanan_dinas_id'],
                $r['tanggal_surat_tugas'],
                $r['pemasukan_id'],
                $r['sumber'],
                $r['tanggal_pemasukan'],
                $selisihHari
            ));
        }
        CLI::newLine();
        CLI::write('Kalau ada baris di atas: buka trip-nya di halaman Perjalanan Dinas, klik tautan kecil', 'yellow');
        CLI::write('"batalkan" di baris peserta itu, lalu tautkan ulang manual ke Pemasukan yang benar.', 'yellow');
    }
}
