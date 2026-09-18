<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table      = 'pegawai';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['nama', 'nip', 'aktif'];
    protected $useTimestamps = true;

    public function getAktifList()
    {
        return $this->where('aktif', 1)->orderBy('nama', 'ASC')->findAll();
    }
}
