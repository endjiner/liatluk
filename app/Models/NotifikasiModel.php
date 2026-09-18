<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table      = 'notifikasi';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['pesan', 'tipe', 'kategori', 'is_read'];
    protected $useTimestamps = true;

    public function getUnread()
    {
        return $this->where('is_read', 0)->orderBy('created_at', 'DESC')->findAll(20);
    }

    public function countUnread()
    {
        return $this->where('is_read', 0)->countAllResults();
    }

    public function markAllRead()
    {
        return $this->where('is_read', 0)->set('is_read', 1)->update();
    }

    public function markRead($id)
    {
        return $this->update($id, ['is_read' => 1]);
    }

    public function tambahNotifikasi($pesan, $tipe = 'info', $kategori = 'sistem')
    {
        try {
            return $this->insert(['pesan' => $pesan, 'tipe' => $tipe, 'kategori' => $kategori, 'is_read' => 0]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menambah notifikasi: ' . $e->getMessage());
            return false;
        }
    }

    public function pengingatSudahAda(string $penanda): bool
    {
        try {
            return $this->where('tipe', 'pengingat')
                ->like('pesan', $penanda)
                ->countAllResults() > 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function applyFilters($builder, array $filters)
    {
        $tahun   = $filters['tahun'] ?? date('Y');
        $periode = $filters['periode'] ?? 'tahun';
        $bulan   = $filters['bulan'] ?? null;
        $jenis   = $filters['jenis'] ?? 'semua';

        if ($periode === 'semester') {
            $batasAwal = date('Y-m-d 00:00:00', strtotime('-6 months'));
            $builder->where('created_at >=', $batasAwal);
        } elseif ($periode === 'bulan' && $bulan) {
            $builder->where('YEAR(created_at)', $tahun)->where('MONTH(created_at)', $bulan);
        } else {
            $builder->where('YEAR(created_at)', $tahun);
        }

        if ($jenis !== 'semua') {
            $builder->where('kategori', $jenis);
        }

        return $builder;
    }

    public function getFiltered(array $filters, int $limit = 20, int $offset = 0)
    {
        $builder = $this->applyFilters($this, $filters);
        return $builder->orderBy('created_at', 'DESC')->findAll($limit, $offset);
    }

    public function countFilteredAll(array $filters): int
    {
        $builder = $this->applyFilters($this->builder(), $filters);
        return (int) $builder->countAllResults();
    }
}