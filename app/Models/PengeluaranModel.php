<?php

namespace App\Models;

use CodeIgniter\Model;

class PengeluaranModel extends Model
{
    protected $table      = 'pengeluaran';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tanggal', 'kategori', 'jumlah', 'tujuan', 'keterangan', 'file_bukti', 'catatan_internal'
    ];
    protected $useTimestamps = true;

    /** Daftar tahun yang punya data, untuk selector tren di dashboard publik. */
    public function getAvailableYears(): array
    {
        $rows = $this->select('YEAR(tanggal) as tahun')->distinct()->orderBy('tahun', 'DESC')->findAll();
        return array_map(fn($r) => (int)$r['tahun'], $rows);
    }

    /**
     * Terapkan filter bulan/tahun sebagai range tanggal (sargable, bisa pakai index pada
     * kolom tanggal) kalau tahun diisi. "Bulan tanpa tahun" (lintas semua tahun) tetap
     * pakai MONTH() karena tidak bisa dinyatakan sebagai satu range tanggal yang kontinu.
     */
    private function applyPeriode($builder, $bulan, $tahun)
    {
        if ($tahun && $bulan) {
            $start = sprintf('%04d-%02d-01', $tahun, $bulan);
            $end   = date('Y-m-d', strtotime($start . ' +1 month'));
            $builder->where('tanggal >=', $start)->where('tanggal <', $end);
        } elseif ($tahun) {
            $builder->where('tanggal >=', $tahun . '-01-01')->where('tanggal <', ($tahun + 1) . '-01-01');
        } elseif ($bulan) {
            $builder->where('MONTH(tanggal)', $bulan);
        }
        return $builder;
    }

    public function getTotalPengeluaran($bulan = null, $tahun = null)
    {
        $builder = $this->applyPeriode($this->selectSum('jumlah'), $bulan, $tahun);
        return (float) $builder->get()->getRow()->jumlah ?? 0;
    }

    public function getDataPerBulan($tahun = null)
    {
        $tahun = $tahun ?? date('Y');
        return $this->select("MONTH(tanggal) as bulan, SUM(jumlah) as total")
            ->where('tanggal >=', $tahun . '-01-01')
            ->where('tanggal <', ($tahun + 1) . '-01-01')
            ->groupBy('MONTH(tanggal)')
            ->orderBy('bulan', 'ASC')
            ->findAll();
    }

    public function getDataPerKategori($bulan = null, $tahun = null)
    {
        $builder = $this->applyPeriode($this->select("kategori, SUM(jumlah) as total")->groupBy('kategori'), $bulan, $tahun);
        return $builder->findAll();
    }

    /** Pencarian kategori/tujuan/keterangan — query PENDEK (<3 huruf) sengaja DIBATASI ke
     *  tujuan saja (field paling mirip "nama/identitas"). Kalau semua field ikut dicari
     *  untuk query sependek 2 huruf, kata umum yang muncul di banyak kategori/keterangan
     *  bisa bikin hasilnya cocok ke hampir semua baris — pencarian jadi terasa tidak
     *  menyaring apa-apa. Query 3+ huruf tetap dicari di semua field. */
    private function applyPencarian($builder, ?string $search)
    {
        if (empty($search)) return $builder;
        if (mb_strlen($search) < 3) {
            return $builder->like('tujuan', $search);
        }
        return $builder->groupStart()
            ->like('kategori', $search)
            ->orLike('tujuan', $search)
            ->orLike('keterangan', $search)
            ->groupEnd();
    }

    public function getFiltered($filters = [], $limit = 10, $offset = 0)
    {
        $builder = $this;
        if (!empty($filters['kategori'])) {
            $builder = $builder->whereIn('kategori', $filters['kategori']);
        }
        $builder = $this->applyPeriode($builder, $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        $builder = $this->applyPencarian($builder, $filters['search'] ?? null);
        return $builder->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->findAll($limit, $offset);
    }

    public function countFiltered($filters = [])
    {
        $builder = $this->builder();
        if (!empty($filters['kategori'])) {
            $builder->whereIn('kategori', $filters['kategori']);
        }
        $this->applyPeriode($builder, $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        $this->applyPencarian($builder, $filters['search'] ?? null);
        return $builder->countAllResults();
    }

    public function getKategoriList()
    {
        return $this->select('kategori')->distinct()->findAll();
    }
}
