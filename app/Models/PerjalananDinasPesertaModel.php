<?php

namespace App\Models;

use CodeIgniter\Model;

class PerjalananDinasPesertaModel extends Model
{
    protected $table      = 'perjalanan_dinas_peserta';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'perjalanan_dinas_id', 'pegawai_id', 'nama_peserta', 'uang_harian', 'meeting_fullboard',
        'meeting_fullday', 'uang_representasi', 'transport_lokal', 'bbm', 'total_spj',
        'dana_taktis', 'status_lunas', 'tanggal_lunas', 'pemasukan_id'
    ];
    protected $useTimestamps = true;

    public function getByPerjalanan(int $perjalananDinasId): array
    {
        return $this->where('perjalanan_dinas_id', $perjalananDinasId)->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Hitung ulang Total SPJ (semua komponen biaya milik peserta ini, termasuk seluruh
     * tiket & hotelnya) dan Dana Taktis (10% dari Uang Harian saja — TIDAK dari total SPJ,
     * sesuai rumus di spreadsheet sumber). Dipanggil setiap kali peserta/tiket/hotel disimpan.
     */
    public function recalculate(int $pesertaId): void
    {
        $peserta = $this->find($pesertaId);
        if (!$peserta) return;

        $totalTiket = (new PerjalananDinasTiketModel())->getSumHargaByPeserta($pesertaId);
        $hotel      = (new PerjalananDinasHotelModel())->getByPeserta($pesertaId);
        $totalHotel = $hotel ? ((float)$hotel['total_bill'] + (float)$hotel['total_biaya_30persen']) : 0.0;

        $totalSpj = (float)$peserta['uang_harian']
            + (float)$peserta['meeting_fullboard']
            + (float)$peserta['meeting_fullday']
            + (float)$peserta['uang_representasi']
            + (float)$peserta['transport_lokal']
            + (float)$peserta['bbm']
            + $totalTiket
            + $totalHotel;

        $danaTaktis = round((float)$peserta['uang_harian'] * 0.10);

        $this->update($pesertaId, [
            'total_spj'   => $totalSpj,
            'dana_taktis' => $danaTaktis,
        ]);
    }

    /**
     * Tandai setoran Dana Taktis peserta ini sebagai lunas: otomatis membukukan nilainya
     * sebagai Pemasukan (kategori "Setoran Taktis Pegawai") supaya Dashboard/Laporan ikut
     * akurat tanpa input dobel. ID pemasukan yang dibuat disimpan di kolom pemasukan_id
     * supaya bisa dibatalkan (lihat batalkanLunas()) kalau statusnya di-toggle balik.
     */
    public function tandaiLunas(int $id, string $tanggalLunas): bool
    {
        $peserta = $this->find($id);
        if (!$peserta || $peserta['status_lunas'] === 'lunas') return false;

        $pemasukanId = null;
        if ((float)$peserta['dana_taktis'] > 0) {
            $trip = (new PerjalananDinasModel())->find($peserta['perjalanan_dinas_id']);
            $pemasukanId = (new PemasukanModel())->insert([
                'tanggal'         => $tanggalLunas,
                'kategori'        => 'Setoran Taktis Pegawai',
                'jumlah'          => $peserta['dana_taktis'],
                'jumlah_diterima' => $peserta['dana_taktis'],
                'status_dana'     => 'diterima',
                'sumber'          => $peserta['nama_peserta'],
                'keterangan'      => 'Setoran Dana Taktis 10% Uang Harian — ' . ($trip['maksud'] ?? ('Perjalanan Dinas #' . $peserta['perjalanan_dinas_id'])),
            ]);
        }

        return $this->update($id, [
            'status_lunas'  => 'lunas',
            'tanggal_lunas' => $tanggalLunas,
            'pemasukan_id'  => $pemasukanId,
        ]);
    }

    /** Batalkan status lunas & hapus balik pemasukan otomatis yang tadi dibuat. */
    public function batalkanLunas(int $id): bool
    {
        $peserta = $this->find($id);
        if (!$peserta || $peserta['status_lunas'] !== 'lunas') return false;

        if (!empty($peserta['pemasukan_id'])) {
            (new PemasukanModel())->delete($peserta['pemasukan_id']);
        }

        return $this->update($id, [
            'status_lunas'  => 'belum',
            'tanggal_lunas' => null,
            'pemasukan_id'  => null,
        ]);
    }

    /** Rekap Dana Taktis milik satu pegawai — dipakai di halaman "Dana Taktis". */
    public function getRekapDanaTaktisByPegawai(int $pegawaiId): array
    {
        $rows = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas_peserta.pegawai_id', $pegawaiId)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'DESC')
            ->findAll();

        $totalUangHarian = 0.0;
        $totalSpj        = 0.0;
        $totalDanaTaktis = 0.0;
        $belumDibayar    = 0.0;

        foreach ($rows as $r) {
            $totalUangHarian += (float)$r['uang_harian'];
            $totalSpj        += (float)$r['total_spj'];
            $totalDanaTaktis += (float)$r['dana_taktis'];
            if ($r['status_lunas'] === 'belum') $belumDibayar += (float)$r['dana_taktis'];
        }

        return [
            'rows'              => $rows,
            'total_uang_harian' => $totalUangHarian,
            'total_spj'         => $totalSpj,
            'total_dana_taktis' => $totalDanaTaktis,
            'belum_dibayar'     => $belumDibayar,
        ];
    }

    /** Semua setoran Dana Taktis yang belum lunas, lintas pegawai — untuk overview di halaman Dana Taktis. */
    public function getAllBelumDibayar(): array
    {
        return $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas_peserta.status_lunas', 'belum')
            ->where('perjalanan_dinas_peserta.dana_taktis >', 0)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'ASC')
            ->findAll();
    }
}
