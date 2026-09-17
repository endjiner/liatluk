<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerjalananDinasHistorisSeeder extends Seeder
{
    public function run()
    {
        $path = __DIR__ . '/data/perjadin_historis.json';
        $data = json_decode(file_get_contents($path), true);

        $this->db->transStart();

        $pegawaiIdMap = $this->importPegawai($data['pegawai']);
        [$tripCount, $pesertaCount, $tiketCount, $hotelCount] = $this->importTrips($data['trips'], $pegawaiIdMap);

        $this->db->transComplete();

        echo "Import selesai: {$tripCount} perjalanan dinas, {$pesertaCount} peserta, "
            . "{$tiketCount} tiket, {$hotelCount} hotel.\n";
    }

    /** @return array<int,int> index lokal (posisi di JSON) -> id pegawai di DB */
    private function importPegawai(array $pegawaiList): array
    {
        $map = [];
        foreach ($pegawaiList as $idx => $p) {
            $existing = $this->db->table('pegawai')
                ->where('LOWER(nama)', strtolower($p['nama']))
                ->get()->getRowArray();

            if ($existing) {
                $map[$idx] = (int) $existing['id'];
                continue;
            }

            $this->db->table('pegawai')->insert([
                'nama'       => $p['nama'],
                'nip'        => $p['nip'],
                'aktif'      => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $map[$idx] = (int) $this->db->insertID();
        }
        return $map;
    }

    /** @return array{0:int,1:int,2:int,3:int} [trip, peserta, tiket, hotel] count */
    private function importTrips(array $trips, array $pegawaiIdMap): array
    {
        $tripCount = $pesertaCount = $tiketCount = $hotelCount = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($trips as $trip) {
            $this->db->table('perjalanan_dinas')->insert([
                'maksud'              => $trip['maksud'],
                'no_surat_tugas'      => $trip['no_surat_tugas'],
                'tanggal_surat_tugas' => $trip['tanggal_surat_tugas'],
                'kode_mak'            => $trip['kode_mak'],
                'no_spm'              => $trip['no_spm'],
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $tripId = (int) $this->db->insertID();
            $tripCount++;

            foreach ($trip['peserta'] as $p) {
                $this->db->table('perjalanan_dinas_peserta')->insert([
                    'perjalanan_dinas_id' => $tripId,
                    'pegawai_id'          => $pegawaiIdMap[$p['pegawai_idx']],
                    'nama_peserta'        => $p['nama_peserta'],
                    'uang_harian'         => $p['uang_harian'],
                    'meeting_fullboard'   => $p['meeting_fullboard'],
                    'meeting_fullday'     => $p['meeting_fullday'],
                    'uang_representasi'   => $p['uang_representasi'],
                    'transport_lokal'     => $p['transport_lokal'],
                    'bbm'                 => $p['bbm'],
                    'total_spj'           => $p['total_spj'],
                    'dana_taktis'         => $p['dana_taktis'],
                    'status_lunas'        => $p['status_lunas'],
                    'tanggal_lunas'       => $p['tanggal_lunas'],
                    'pemasukan_id'        => null,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                $pesertaId = (int) $this->db->insertID();
                $pesertaCount++;

                foreach ($p['tiket'] as $t) {
                    $this->db->table('perjalanan_dinas_tiket')->insert([
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
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                    $tiketCount++;
                }

                if ($p['hotel']) {
                    $h = $p['hotel'];
                    $this->db->table('perjalanan_dinas_hotel')->insert([
                        'peserta_id'            => $pesertaId,
                        'nama_hotel'            => $h['nama_hotel'],
                        'alamat_hotel'          => $h['alamat_hotel'],
                        'telp_hotel'            => $h['telp_hotel'],
                        'checkin'               => $h['checkin'],
                        'checkout'              => $h['checkout'],
                        'total_bill'            => $h['total_bill'],
                        'no_kamar'              => $h['no_kamar'],
                        'no_invoice'            => $h['no_invoice'],
                        'total_biaya_30persen'  => $h['total_biaya_30persen'],
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ]);
                    $hotelCount++;
                }
            }
        }

        return [$tripCount, $pesertaCount, $tiketCount, $hotelCount];
    }
}