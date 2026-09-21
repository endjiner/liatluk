<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PerjalananDinasTiketModel;
use App\Models\PerjalananDinasHotelModel;
use App\Models\PegawaiModel;

/**
 * Tambah trip Perjalanan Dinas baru dari data/perjadin_baru.json — dipakai tiap kali ada
 * baris baru di spreadsheet "A. Rekap Perjalanan Dinas 2026 - OTOMATIS" yang belum masuk ke
 * database (mis. trip terbaru yang belum sempat di-seed). Idempoten: trip yang no_surat_tugas-nya
 * sudah ada di database dilewati, jadi aman dijalankan berkali-kali / di database yang sudah
 * terisi sebagian.
 *
 * Nama peserta dicocokkan ke pegawai yang SUDAH ADA (exact match dulu, lalu fallback tanpa
 * gelar) sebelum membuat pegawai baru — supaya tidak menduplikasi orang yang sama dengan
 * ejaan nama berbeda (mis. "Muhammad Herpi Akbar" harus nyambung ke pegawai yang sama
 * dengan snapshot lama "Muhammad Herpi Akbar, S.Farm, Apt., M.Farm").
 *
 * Dry-run secara default (hanya melaporkan). Tambahkan --confirm untuk benar-benar menulis.
 */
class TambahPerjadinBaru extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:tambah-perjadin-baru';
    protected $description = 'Tambah trip Perjalanan Dinas baru dari data/perjadin_baru.json (dry-run tanpa --confirm).';
    protected $usage       = 'app:tambah-perjadin-baru [--confirm]';

    private const GELAR = [
        'apt', 's farm', 's t p', 's t', 's e', 's si', 'm farm', 'dr', 's kom', 's h',
        's ip', 's sos', 'm m', 'm si', 's pd', 's km', 'a md', 'm kes', 's psi', 'm ap',
    ];

    private function normNama(?string $s): string
    {
        $n = strtolower((string) $s);
        $n = str_replace(['.', ',', '-', '(', ')', '/'], ' ', $n);
        foreach (self::GELAR as $g) {
            $n = preg_replace('/\b' . preg_quote($g, '/') . '\b/', ' ', $n);
        }
        $n = preg_replace('/\s+/', ' ', $n);
        return trim($n);
    }

    private function normSurat(?string $s): string
    {
        return preg_replace('/\s+/', '', strtolower(trim((string) $s)));
    }

    public function run(array $params)
    {
        $path = __DIR__ . '/data/perjadin_baru.json';
        $trips = json_decode(file_get_contents($path), true);
        if (!is_array($trips)) {
            CLI::error("Gagal membaca sumber di $path");
            return;
        }

        $tripModel    = new PerjalananDinasModel();
        $pesertaModel = new PerjalananDinasPesertaModel();
        $tiketModel   = new PerjalananDinasTiketModel();
        $hotelModel   = new PerjalananDinasHotelModel();
        $pegawaiModel = new PegawaiModel();

        $dbTrips = $tripModel->findAll();
        $existingSurat = [];
        foreach ($dbTrips as $t) {
            $existingSurat[$this->normSurat($t['no_surat_tugas'])] = true;
        }

        $pegawaiList = $pegawaiModel->findAll();
        $pegawaiByExact = [];
        $pegawaiByNorm  = [];
        foreach ($pegawaiList as $p) {
            $pegawaiByExact[strtolower(trim($p['nama']))] = $p;
            $pegawaiByNorm[$this->normNama($p['nama'])] = $p;
        }

        $confirm = (bool) CLI::getOption('confirm');
        $tripsToInsert = [];

        foreach ($trips as $trip) {
            $key = $this->normSurat($trip['no_surat_tugas']);
            if (isset($existingSurat[$key])) {
                CLI::write("LEWATI (sudah ada): {$trip['no_surat_tugas']} — {$trip['maksud']}", 'yellow');
                continue;
            }

            CLI::write("BARU: {$trip['no_surat_tugas']} ({$trip['tanggal_surat_tugas']}) — {$trip['maksud']}", 'green');

            $resolvedPeserta = [];
            foreach ($trip['peserta'] as $p) {
                $exactKey = strtolower(trim($p['nama_peserta']));
                $normKey  = $this->normNama($p['nama_peserta']);
                $match = $pegawaiByExact[$exactKey] ?? $pegawaiByNorm[$normKey] ?? null;

                $totalTiket = array_sum(array_column($p['tiket'], 'harga_tiket'));
                $totalHotel = ($p['hotel']['total_bill'] ?? 0) + ($p['hotel']['total_biaya_30persen'] ?? 0);
                $totalSpj = (float) $p['uang_harian'] + (float) $p['meeting_fullboard'] + (float) $p['meeting_fullday']
                    + (float) $p['uang_representasi'] + (float) $p['transport_lokal'] + (float) $p['bbm']
                    + $totalTiket + $totalHotel;

                if ($match) {
                    CLI::write("  - {$p['nama_peserta']} -> cocok dengan pegawai #{$match['id']} ({$match['nama']}) | Total SPJ: Rp " . number_format($totalSpj, 0, ',', '.'));
                } else {
                    CLI::write("  - {$p['nama_peserta']} -> PEGAWAI BARU (belum ada di database) | Total SPJ: Rp " . number_format($totalSpj, 0, ',', '.'), 'cyan');
                }

                $resolvedPeserta[] = ['data' => $p, 'pegawai' => $match, 'total_spj' => $totalSpj];
            }

            $tripsToInsert[] = ['trip' => $trip, 'peserta' => $resolvedPeserta];
        }

        if (empty($tripsToInsert)) {
            CLI::write("\nTidak ada trip baru untuk ditambahkan.", 'green');
            return;
        }

        if (!$confirm) {
            CLI::write("\nMode DRY-RUN — belum ada perubahan. Tambahkan --confirm untuk menyimpan " . count($tripsToInsert) . " trip di atas.", 'cyan');
            return;
        }

        $db = \Config\Database::connect();
        $db->transException(true);
        $db->transStart();

        foreach ($tripsToInsert as $item) {
            $trip = $item['trip'];
            $tripId = $tripModel->insert([
                'maksud'              => $trip['maksud'],
                'no_surat_tugas'      => $trip['no_surat_tugas'],
                'tanggal_surat_tugas' => $trip['tanggal_surat_tugas'],
                'kode_mak'            => $trip['kode_mak'],
                'no_spm'              => $trip['no_spm'],
                'no_pd'               => $trip['no_pd'] ?? null,
            ], true);

            foreach ($item['peserta'] as $rp) {
                $p = $rp['data'];
                $pegawaiId = $rp['pegawai']['id'] ?? null;
                if (!$pegawaiId) {
                    $pegawaiId = $pegawaiModel->insert(['nama' => $p['nama_peserta'], 'nip' => null, 'aktif' => 1], true);
                }

                $pesertaId = $pesertaModel->insert([
                    'perjalanan_dinas_id' => $tripId,
                    'pegawai_id'          => $pegawaiId,
                    'nama_peserta'        => $p['nama_peserta'],
                    'uang_harian'         => $p['uang_harian'],
                    'meeting_fullboard'   => $p['meeting_fullboard'],
                    'meeting_fullday'     => $p['meeting_fullday'],
                    'uang_representasi'   => $p['uang_representasi'],
                    'transport_lokal'     => $p['transport_lokal'],
                    'bbm'                 => $p['bbm'],
                    'total_spj'           => $rp['total_spj'],
                    'dana_taktis'         => $p['dana_taktis'],
                    'status_lunas'        => $p['status_lunas'],
                    'tanggal_lunas'       => null,
                    'pemasukan_id'        => null,
                ], true);

                foreach ($p['tiket'] as $t) {
                    $tiketModel->insert([
                        'peserta_id'      => $pesertaId,
                        'maskapai'        => $t['maskapai'],
                        'arah'            => $t['arah'],
                        'no_tiket'        => $t['no_tiket'],
                        'kode_booking'    => $t['kode_booking'],
                        'no_penerbangan'  => $t['no_penerbangan'],
                        'tempat_asal'     => $t['tempat_asal'],
                        'tempat_tujuan'   => $t['tempat_tujuan'],
                        'tanggal_terbang' => $t['tanggal_terbang'],
                        'harga_tiket'     => $t['harga_tiket'],
                    ]);
                }

                if (!empty($p['hotel'])) {
                    $h = $p['hotel'];
                    $hotelModel->insert([
                        'peserta_id'           => $pesertaId,
                        'nama_hotel'           => $h['nama_hotel'],
                        'alamat_hotel'         => $h['alamat_hotel'],
                        'telp_hotel'           => $h['telp_hotel'],
                        'checkin'              => $h['checkin'],
                        'checkout'             => $h['checkout'],
                        'total_bill'           => $h['total_bill'],
                        'no_kamar'             => $h['no_kamar'],
                        'no_invoice'           => $h['no_invoice'],
                        'total_biaya_30persen' => $h['total_biaya_30persen'] ?? 0,
                    ]);
                }
            }
        }

        $db->transComplete();
        CLI::write("\nSelesai! " . count($tripsToInsert) . " trip baru ditambahkan.", 'green');
    }
}
