<?php

namespace App\Models;

use CodeIgniter\Model;

class PemasukanModel extends Model
{
    protected $table      = 'pemasukan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tanggal', 'kategori', 'jumlah', 'jumlah_diterima',
        'status_dana', 'sumber', 'keterangan', 'file_bukti', 'catatan_internal', 'dari_tandai_lunas'
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

    /**
     * Total pemasukan (semua jumlah, bukan hanya yang diterima)
     */
    public function getTotalPemasukan($bulan = null, $tahun = null)
    {
        $builder = $this->applyPeriode($this->selectSum('jumlah'), $bulan, $tahun);
        return (float) $builder->get()->getRow()->jumlah ?? 0;
    }

    /**
     * Total pemasukan yang sudah diterima (untuk kalkulasi saldo)
     */
    public function getTotalDiterima($bulan = null, $tahun = null)
    {
        $builder = $this->applyPeriode($this->selectSum('jumlah_diterima'), $bulan, $tahun);
        return (float) $builder->get()->getRow()->jumlah_diterima ?? 0;
    }

    /**
     * Data chart per bulan (12 bulan terakhir)
     */
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

    /**
     * Data per kategori untuk pie chart
     */
    public function getDataPerKategori($bulan = null, $tahun = null)
    {
        $builder = $this->applyPeriode($this->select("kategori, SUM(jumlah) as total")->groupBy('kategori'), $bulan, $tahun);
        return $builder->findAll();
    }

    /**
     * Filter untuk tabel transaksi publik
     */
    /** Total dana yang belum masuk ke kas (selisih jumlah vs jumlah_diterima untuk status belum/sebagian). */
    public function getTotalBelumDiterima(): float
    {
        $row = $this->select('SUM(jumlah - jumlah_diterima) as sisa')
            ->whereIn('status_dana', ['belum_diterima', 'sebagian'])
            ->get()->getRow();
        return max(0, (float)($row->sisa ?? 0));
    }

    public function getFiltered($filters = [], $limit = 10, $offset = 0)
    {
        $builder = $this;
        if (!empty($filters['kategori'])) {
            $builder = $builder->whereIn('kategori', $filters['kategori']);
        }
        $builder = $this->applyPeriode($builder, $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                ->like('kategori', $filters['search'])
                ->orLike('sumber', $filters['search'])
                ->orLike('keterangan', $filters['search'])
                ->groupEnd();
        }
        return $builder->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->findAll($limit, $offset);
    }

    public function countFiltered($filters = [])
    {
        $builder = $this->builder();
        if (!empty($filters['kategori'])) {
            $builder->whereIn('kategori', $filters['kategori']);
        }
        $this->applyPeriode($builder, $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('kategori', $filters['search'])
                ->orLike('sumber', $filters['search'])
                ->orLike('keterangan', $filters['search'])
                ->groupEnd();
        }
        return $builder->countAllResults();
    }

    public function getKategoriList()
    {
        return $this->select('kategori')->distinct()->findAll();
    }
}
