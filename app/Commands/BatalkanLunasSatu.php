<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PemasukanModel;

/**
 * Batalkan status lunas SATU peserta lewat CLI — pakai method model yang sama persis
 * dengan tautan "batalkan" di halaman Perjalanan Dinas (batalkanLunas()), jadi otomatis
 * aman dari bug lama yang bisa ikut menghapus Pemasukan yang seharusnya tidak dihapus.
 * Berguna untuk membereskan tautan yang ditemukan lewat app:audit-tanggal-lunas tanpa
 * harus mencari-cari barisnya di UI.
 *
 * Dry-run secara default: cuma menampilkan detail apa yang AKAN terjadi. Tambahkan
 * --confirm untuk benar-benar menjalankan.
 */
class BatalkanLunasSatu extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:batalkan-lunas-satu';
    protected $description = 'Batalkan status lunas 1 peserta by ID (dry-run tanpa --confirm).';
    protected $usage       = 'app:batalkan-lunas-satu <peserta_id> [--confirm]';

    public function run(array $params)
    {
        $pesertaId = (int) ($params[0] ?? 0);
        if ($pesertaId <= 0) {
            CLI::error('Wajib isi peserta_id. Contoh: php spark app:batalkan-lunas-satu 133');
            return;
        }

        $pesertaModel = new PerjalananDinasPesertaModel();
        $peserta = $pesertaModel
            ->select('perjalanan_dinas_peserta.*, perjalanan_dinas.tanggal_surat_tugas')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->find($pesertaId);

        if (!$peserta) {
            CLI::error("Peserta #$pesertaId tidak ditemukan.");
            return;
        }
        if ($peserta['status_lunas'] !== 'lunas') {
            CLI::write("Peserta #$pesertaId ({$peserta['nama_peserta']}) statusnya sudah 'belum' — tidak ada yang perlu dibatalkan.", 'yellow');
            return;
        }

        $pemasukan = !empty($peserta['pemasukan_id']) ? (new PemasukanModel())->find($peserta['pemasukan_id']) : null;

        CLI::write('=== Detail yang akan dibatalkan ===', 'yellow');
        CLI::write("Peserta #$pesertaId: {$peserta['nama_peserta']}, trip #{$peserta['perjalanan_dinas_id']} tgl {$peserta['tanggal_surat_tugas']}");
        if ($pemasukan) {
            CLI::write(sprintf(
                'Tertaut ke Pemasukan #%d [%s, Rp%s, %s]',
                $pemasukan['id'],
                $pemasukan['sumber'],
                number_format((float) $pemasukan['jumlah'], 0, ',', '.'),
                $pemasukan['tanggal']
            ));
            CLI::write((int) $pemasukan['dari_tandai_lunas'] === 1
                ? 'Pemasukan ini dibuat otomatis oleh tandaiLunas() — kalau tidak dipakai peserta lain, akan IKUT TERHAPUS.'
                : 'Pemasukan ini sudah ada sebelumnya (bukan auto-generate) — TIDAK akan ikut terhapus, cuma di-unlink.');
        } else {
            CLI::write('Tidak ada Pemasukan yang tertaut.');
        }
        CLI::newLine();

        if (!CLI::getOption('confirm')) {
            CLI::write('Mode DRY-RUN — belum ada perubahan. Tambahkan --confirm untuk menjalankan.', 'cyan');
            return;
        }

        $hasil = $pesertaModel->batalkanLunas($pesertaId);
        if ($hasil['ok']) {
            CLI::write(
                'Selesai — status lunas dibatalkan.' . ($hasil['pemasukan_dihapus']
                    ? ' Pemasukan ikut terhapus.'
                    : ' Pemasukan TIDAK dihapus, tetap ada di pembukuan.'),
                'green'
            );
        } else {
            CLI::error('Gagal membatalkan (kemungkinan status sudah berubah sejak dry-run di atas).');
        }
    }
}
