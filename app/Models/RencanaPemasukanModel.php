<?php

namespace App\Models;

use CodeIgniter\Model;

class RencanaPemasukanModel extends Model
{
    protected $table      = 'rencana_pemasukan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'tanggal_rencana', 'kategori', 'jumlah_rencana', 'jumlah_terealisasi', 'keterangan', 'status', 'file_bukti'
    ];
    protected $useTimestamps = true;

    public function getAktif()
    {
        return $this->where('status', 'aktif')->orderBy('tanggal_rencana', 'ASC')->findAll();
    }

    /**
     * Realisasikan rencana: insert ke pemasukans pakai nominal AKTUAL yang benar-benar
     * diterima (bukan estimasi rencana). PENTING: jumlah_rencana TIDAK PERNAH diubah — itu
     * adalah nilai rencana asli yang harus tetap jadi acuan historis. Progres realisasi
     * (bisa dicicil berkali-kali lewat beberapa transaksi) dilacak terpisah di
     * jumlah_terealisasi, supaya sisa = jumlah_rencana - jumlah_terealisasi selalu bisa
     * dihitung dengan benar tanpa merusak angka rencana aslinya.
     */
    public function realisasi($id, float $jumlahAktual, string $scope = 'semua')
    {
        $rencana = $this->find($id);
        if (!$rencana) return false;

        $pemasukanModel = new PemasukanModel();
        $pemasukanModel->insert([
            'tanggal'         => $rencana['tanggal_rencana'],
            'kategori'        => $rencana['kategori'],
            'jumlah'          => $jumlahAktual,
            'jumlah_diterima' => $jumlahAktual,
            'status_dana'     => 'diterima',
            'keterangan'      => 'Realisasi dari rencana: ' . $rencana['keterangan'],
        ]);

        $sudahTerealisasi = (float)($rencana['jumlah_terealisasi'] ?? 0) + $jumlahAktual;
        $sisa = (float)$rencana['jumlah_rencana'] - $sudahTerealisasi;

        $update = ['jumlah_terealisasi' => $sudahTerealisasi];
        // Selesai kalau memang scope-nya "semua", atau kalau realisasi bertahap sudah menutup seluruh rencana
        if ($scope !== 'sebagian' || $sisa <= 0) {
            $update['status'] = 'terealisasi';
        }

        return $this->update($id, $update);
    }

    /** Total SISA rencana aktif (jumlah_rencana asli dikurangi yang sudah direalisasi sebagian). */
    public function getTotalRencana()
    {
        $row = $this->select('SUM(jumlah_rencana - jumlah_terealisasi) as sisa')
            ->where('status', 'aktif')
            ->get()->getRow();
        return (float)($row->sisa ?? 0);
    }
}
