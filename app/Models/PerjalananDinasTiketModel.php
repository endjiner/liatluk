<?php

namespace App\Models;

use CodeIgniter\Model;

class PerjalananDinasTiketModel extends Model
{
    protected $table      = 'perjalanan_dinas_tiket';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'peserta_id', 'maskapai', 'arah', 'no_tiket', 'kode_booking', 'no_penerbangan',
        'tempat_asal', 'tempat_tujuan', 'tanggal_terbang', 'harga_tiket'
    ];
    protected $useTimestamps = true;

    public function getByPeserta(int $pesertaId): array
    {
        return $this->where('peserta_id', $pesertaId)->orderBy('id', 'ASC')->findAll();
    }

    public function getSumHargaByPeserta(int $pesertaId): float
    {
        $row = $this->selectSum('harga_tiket')->where('peserta_id', $pesertaId)->get()->getRow();
        return (float)($row->harga_tiket ?? 0);
    }

    public function replaceForPeserta(int $pesertaId, array $tikets): void
    {
        $this->where('peserta_id', $pesertaId)->delete();
        foreach ($tikets as $t) {
            if (empty(array_filter($t, fn($v) => $v !== null && $v !== ''))) continue;
            $t['peserta_id'] = $pesertaId;
            $this->insert($t);
        }
    }
}
