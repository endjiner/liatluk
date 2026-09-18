<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminAkunModel extends Model
{
    protected $table      = 'admin_akun';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['username', 'password', 'role', 'aktif'];
    protected $useTimestamps = true;

    public const ROLES = ['super_admin', 'admin'];

    public function verifyLogin(string $username, string $password): array|false
    {
        $akun = $this->where('username', $username)->where('aktif', 1)->first();
        if (!$akun || !password_verify($password, $akun['password'])) {
            return false;
        }
        return $akun;
    }

    public function getAllAkun(): array
    {
        return $this->orderBy('role', 'ASC')->orderBy('username', 'ASC')->findAll();
    }

    public function countAktifSuperAdmin(?int $kecualiId = null): int
    {
        $builder = $this->where('role', 'super_admin')->where('aktif', 1);
        if ($kecualiId !== null) {
            $builder->where('id !=', $kecualiId);
        }
        return $builder->countAllResults();
    }

    public function usernameDipakai(string $username, ?int $kecualiId = null): bool
    {
        $builder = $this->where('username', $username);
        if ($kecualiId !== null) {
            $builder->where('id !=', $kecualiId);
        }
        return $builder->countAllResults() > 0;
    }
}
