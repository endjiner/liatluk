<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PemasukanModel;

/**
 * Sinkronkan ulang snapshot nama yang sudah kadung tidak sama dengan nama kanonik pegawai
 * saat ini — dua lapis:
 *  1. nama_peserta di perjalanan_dinas_peserta vs nama pegawai (join by pegawai_id) — bisa
 *     drift walau pegawai_id-nya SELALU benar, mis. kalau nama pegawai diedit belakangan
 *     (lewat Kelola Pegawai atau digabung lewat FixDuplikatPegawaiSeeder) tapi baris peserta
 *     lama itu sendiri tidak pernah disimpan ulang sejak itu.
 *  2. sumber di Pemasukan yang tertaut (pemasukan_id) vs nama_peserta — supaya Daftar
 *     Transaksi & Dana Taktis selalu menampilkan nama yang sama persis untuk orang yang sama
 *     (tautan lama dari app:reconcile-setoran-taktis versi sebelum tool itu ikut memperbaiki
 *     sumber, atau tautan manual lain, sumbernya belum tentu ikut ke-refresh).
 *
 * Dry-run secara default. Jalankan dengan --apply untuk benar-benar menyimpan perubahan.
 */
class SinkronSumberSetoran extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:sinkron-sumber-setoran';
    protected $description = 'Samakan ulang nama_peserta (vs nama pegawai saat ini) & sumber Pemasukan tertaut (vs nama_peserta) — dry-run default.';
    protected $usage       = 'app:sinkron-sumber-setoran [--apply]';
    protected $options     = [
        '--apply' => 'Simpan perubahan (bukan cuma menampilkan usulan).',
    ];

    public function run(array $params)
    {
        $apply = array_key_exists('apply', $params) || CLI::getOption('apply');
        $pesertaModel = new PerjalananDinasPesertaModel();

        // ── Lapis 1: nama_peserta vs nama pegawai saat ini ──────────────────────────
        $semuaPeserta = $pesertaModel
            ->select('perjalanan_dinas_peserta.id, perjalanan_dinas_peserta.nama_peserta, pegawai.nama AS nama_pegawai_sekarang')
            ->join('pegawai', 'pegawai.id = perjalanan_dinas_peserta.pegawai_id')
            ->findAll();

        $namaBeda = array_values(array_filter(
            $semuaPeserta,
            fn($r) => trim((string)$r['nama_peserta']) !== trim((string)$r['nama_pegawai_sekarang'])
        ));

        CLI::write('=== Sinkron Nama & Sumber Setoran Taktis ===', 'yellow');
        CLI::write('Total baris peserta: ' . count($semuaPeserta));
        CLI::write('nama_peserta yang beda dari nama pegawai saat ini: ' . count($namaBeda));
        foreach ($namaBeda as $r) {
            CLI::write("  Peserta #{$r['id']}: nama_peserta '{$r['nama_peserta']}' -> '{$r['nama_pegawai_sekarang']}'");
        }
        CLI::newLine();

        // ── Lapis 2: sumber Pemasukan tertaut vs nama_peserta ───────────────────────
        $tertaut = $pesertaModel
            ->select('perjalanan_dinas_peserta.id, perjalanan_dinas_peserta.nama_peserta, perjalanan_dinas_peserta.pemasukan_id, pemasukan.sumber')
            ->join('pemasukan', 'pemasukan.id = perjalanan_dinas_peserta.pemasukan_id')
            ->where('perjalanan_dinas_peserta.pemasukan_id IS NOT NULL', null, false)
            ->findAll();

        $sumberBeda = array_values(array_filter($tertaut, fn($r) => trim((string)$r['sumber']) !== trim((string)$r['nama_peserta'])));

        CLI::write('Total peserta yang tertaut ke Pemasukan: ' . count($tertaut));
        CLI::write('Sumber yang tidak sama persis dengan nama_peserta: ' . count($sumberBeda));
        foreach ($sumberBeda as $r) {
            CLI::write("  Pemasukan #{$r['pemasukan_id']}: sumber '{$r['sumber']}' -> '{$r['nama_peserta']}'");
        }
        CLI::newLine();

        if (empty($namaBeda) && empty($sumberBeda)) {
            CLI::write('Semua sudah konsisten, tidak ada yang perlu diperbaiki.', 'green');
            return;
        }

        if (!$apply) {
            CLI::write('Mode DRY-RUN — belum ada perubahan yang disimpan. Jalankan lagi dengan --apply untuk menerapkan.', 'cyan');
            return;
        }

        foreach ($namaBeda as $r) {
            $pesertaModel->update($r['id'], ['nama_peserta' => $r['nama_pegawai_sekarang']]);
        }
        // Sumber disamakan ke nama_peserta yang SUDAH disegarkan di atas (bukan versi lama),
        // supaya satu jalan --apply ini langsung konsisten dari ujung ke ujung.
        $namaPesertaTerbaru = array_column($namaBeda, 'nama_pegawai_sekarang', 'id');
        foreach ($sumberBeda as $r) {
            $namaFinal = $namaPesertaTerbaru[$r['id']] ?? $r['nama_peserta'];
            (new PemasukanModel())->update($r['pemasukan_id'], ['sumber' => $namaFinal]);
        }

        CLI::write(count($namaBeda) . ' nama_peserta & ' . count($sumberBeda) . ' sumber Pemasukan diperbarui.', 'green');
    }
}
