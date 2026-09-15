<?php

namespace App\Models;

use CodeIgniter\Model;

class PerjalananDinasModel extends Model
{
    protected $table      = 'perjalanan_dinas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['maksud', 'no_surat_tugas', 'tanggal_surat_tugas', 'kode_mak', 'no_spm'];
    protected $useTimestamps = true;

    /** Daftar tahun yang punya data, untuk selector rekap. */
    public function getAvailableYears(): array
    {
        $rows = $this->select('YEAR(tanggal_surat_tugas) as tahun')->distinct()->orderBy('tahun', 'DESC')->findAll();
        return array_map(fn($r) => (int)$r['tahun'], $rows);
    }

    private function applyPeriode($builder, $bulan, $tahun)
    {
        if ($tahun && $bulan) {
            $start = sprintf('%04d-%02d-01', $tahun, $bulan);
            $end   = date('Y-m-d', strtotime($start . ' +1 month'));
            $builder->where('tanggal_surat_tugas >=', $start)->where('tanggal_surat_tugas <', $end);
        } elseif ($tahun) {
            $builder->where('tanggal_surat_tugas >=', $tahun . '-01-01')->where('tanggal_surat_tugas <', ($tahun + 1) . '-01-01');
        } elseif ($bulan) {
            $builder->where('MONTH(tanggal_surat_tugas)', $bulan);
        }
        return $builder;
    }

    public function getFiltered($filters = [], $limit = 20, $offset = 0)
    {
        $builder = $this->applyPeriode($this->builder(), $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('maksud', $filters['search'])
                ->orLike('no_surat_tugas', $filters['search'])
                ->orLike('kode_mak', $filters['search'])
                ->groupEnd();
        }
        // ASC (lama -> baru): urutan & penomoran "No" mengikuti urutan sheet sumbernya.
        return $builder->orderBy('tanggal_surat_tugas', 'ASC')->orderBy('id', 'ASC')->get($limit, $offset)->getResultArray();
    }

    public function countFiltered($filters = [])
    {
        $builder = $this->applyPeriode($this->builder(), $filters['bulan'] ?? null, $filters['tahun'] ?? null);
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('maksud', $filters['search'])
                ->orLike('no_surat_tugas', $filters['search'])
                ->orLike('kode_mak', $filters['search'])
                ->groupEnd();
        }
        return $builder->countAllResults();
    }
}
