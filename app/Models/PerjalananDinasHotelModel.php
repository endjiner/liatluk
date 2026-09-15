<?php

namespace App\Models;

use CodeIgniter\Model;

class PerjalananDinasHotelModel extends Model
{
    protected $table      = 'perjalanan_dinas_hotel';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'peserta_id', 'nama_hotel', 'alamat_hotel', 'telp_hotel', 'checkin', 'checkout',
        'total_bill', 'no_kamar', 'no_invoice', 'total_biaya_30persen'
    ];
    protected $useTimestamps = true;

    public function getByPeserta(int $pesertaId): ?array
    {
        return $this->where('peserta_id', $pesertaId)->first();
    }

    /** Satu peserta maksimal punya satu blok hotel — hapus lalu insert ulang supaya sederhana. */
    public function replaceForPeserta(int $pesertaId, ?array $hotel): void
    {
        $this->where('peserta_id', $pesertaId)->delete();
        if (!$hotel) return;
        $adaIsi = !empty(array_filter($hotel, fn($v) => $v !== null && $v !== ''));
        if (!$adaIsi) return;
        $hotel['peserta_id'] = $pesertaId;
        $this->insert($hotel);
    }
}
