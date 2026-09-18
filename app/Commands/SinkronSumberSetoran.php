<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PemasukanModel;

/**
 * Sinkronkan ulang snapshot nama yang sudah kadung tidak sama dengan nama kanonik pegawai
 * saat ini — dua hal, keduanya dibandingkan LANGSUNG ke nama pegawai saat ini (bukan saling
 * mengacu satu sama lain) supaya tidak ada celah kalau salah satunya tidak bisa ditentukan:
 *  1. nama_peserta di perjalanan_dinas_peserta vs nama pegawai (join by pegawai_id) — bisa
 *     drift walau pegawai_id-nya SELALU benar, mis. kalau nama pegawai diedit belakangan
 *     (lewat Kelola Pegawai atau digabung lewat FixDuplikatPegawaiSeeder) tapi baris peserta
 *     lama itu sendiri tidak pernah disimpan ulang sejak itu.
 *  2. sumber di Pemasukan yang tertaut (pemasukan_id) vs nama pegawai saat ini — supaya Daftar
 *     Transaksi & Dana Taktis selalu menampilkan nama yang sama persis untuk orang yang sama
 *     (tautan lama dari app:reconcile-setoran-taktis versi sebelum tool itu ikut memperbaiki
 *     sumber, atau tautan manual lain, sumbernya belum tentu ikut ke-refresh).
 *
 * pegawai_id dipakai LEFT JOIN (bukan JOIN biasa) supaya baris yang pegawai_id-nya sudah tidak
 * cocok ke pegawai manapun (yatim piatu — mis. baris pegawai itu pernah terhapus tanpa sempat
 * di-repoint) tetap kelihatan dan dilaporkan terpisah, bukan diam-diam hilang dari perhitungan
 * (versi sebelumnya begitu, akibatnya sumber yang peserta-nya yatim piatu "diperbaiki" dengan
 * cuma menyalin ulang teks nama_peserta yang sudah usang apa adanya alih-alih nama kanonik).
 *
 * Dry-run secara default. Jalankan dengan --apply untuk benar-benar menyimpan perubahan.
 */
class SinkronSumberSetoran extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:sinkron-sumber-setoran';
    protected $description = 'Samakan ulang nama_peserta & sumber Pemasukan tertaut ke nama pegawai saat ini — dry-run default.';
    protected $usage       = 'app:sinkron-sumber-setoran [--apply]';
    protected $options     = [
        '--apply' => 'Simpan perubahan (bukan cuma menampilkan usulan).',
    ];

    public function run(array $params)
    {
        $apply = array_key_exists('apply', $params) || CLI::getOption('apply');
        $pesertaModel = new PerjalananDinasPesertaModel();

        $semua = $pesertaModel
            ->select('perjalanan_dinas_peserta.id, perjalanan_dinas_peserta.pegawai_id, perjalanan_dinas_peserta.nama_peserta, perjalanan_dinas_peserta.pemasukan_id, pegawai.nama AS nama_pegawai_sekarang, pemasukan.sumber AS sumber_sekarang')
            ->join('pegawai', 'pegawai.id = perjalanan_dinas_peserta.pegawai_id', 'left')
            ->join('pemasukan', 'pemasukan.id = perjalanan_dinas_peserta.pemasukan_id', 'left')
            ->findAll();

        $yatimPiatu = array_values(array_filter($semua, fn($r) => $r['nama_pegawai_sekarang'] === null));
        $adaPegawai = array_values(array_filter($semua, fn($r) => $r['nama_pegawai_sekarang'] !== null));

        $namaBeda = array_values(array_filter(
            $adaPegawai,
            fn($r) => trim((string)$r['nama_peserta']) !== trim((string)$r['nama_pegawai_sekarang'])
        ));

        $sumberBeda = array_values(array_filter(
            $adaPegawai,
            fn($r) => $r['pemasukan_id'] !== null && trim((string)$r['sumber_sekarang']) !== trim((string)$r['nama_pegawai_sekarang'])
        ));

        CLI::write('=== Sinkron Nama & Sumber Setoran Taktis ===', 'yellow');
        CLI::write('Total baris peserta: ' . count($semua));

        if (!empty($yatimPiatu)) {
            CLI::write('PERINGATAN — pegawai_id tidak cocok ke pegawai manapun (yatim piatu), TIDAK ikut diperbaiki otomatis: ' . count($yatimPiatu), 'red');
            foreach ($yatimPiatu as $r) {
                $tautan = $r['pemasukan_id'] ? " (tertaut ke Pemasukan #{$r['pemasukan_id']}, sumber saat ini: '{$r['sumber_sekarang']}')" : '';
                CLI::write("  Peserta #{$r['id']}: pegawai_id={$r['pegawai_id']}, nama_peserta '{$r['nama_peserta']}'{$tautan}");
            }
            CLI::write('  -> Cek manual: pegawai_id di atas mungkin salah/pernah terhapus. Perbaiki pegawai_id-nya dulu (lewat DB langsung atau re-save peserta-nya), baru jalankan tool ini lagi.', 'dark_gray');
            CLI::newLine();
        }

        CLI::write('nama_peserta yang beda dari nama pegawai saat ini: ' . count($namaBeda));
        foreach ($namaBeda as $r) {
            CLI::write("  Peserta #{$r['id']}: nama_peserta '{$r['nama_peserta']}' -> '{$r['nama_pegawai_sekarang']}'");
        }
        CLI::newLine();

        CLI::write('Sumber Pemasukan tertaut yang tidak sama persis dengan nama pegawai saat ini: ' . count($sumberBeda));
        foreach ($sumberBeda as $r) {
            CLI::write("  Pemasukan #{$r['pemasukan_id']} (peserta #{$r['id']}): sumber '{$r['sumber_sekarang']}' -> '{$r['nama_pegawai_sekarang']}'");
        }
        CLI::newLine();

        if (empty($namaBeda) && empty($sumberBeda)) {
            CLI::write('Semua yang bisa dicek otomatis sudah konsisten.' . (empty($yatimPiatu) ? '' : ' (Tapi lihat peringatan yatim piatu di atas.)'), 'green');
            return;
        }

        if (!$apply) {
            CLI::write('Mode DRY-RUN — belum ada perubahan yang disimpan. Jalankan lagi dengan --apply untuk menerapkan.', 'cyan');
            return;
        }

        foreach ($namaBeda as $r) {
            $pesertaModel->update($r['id'], ['nama_peserta' => $r['nama_pegawai_sekarang']]);
        }
        foreach ($sumberBeda as $r) {
            (new PemasukanModel())->update($r['pemasukan_id'], ['sumber' => $r['nama_pegawai_sekarang']]);
        }

        CLI::write(count($namaBeda) . ' nama_peserta & ' . count($sumberBeda) . ' sumber Pemasukan diperbarui.', 'green');
    }
}
