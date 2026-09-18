<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminAkunModel;

/**
 * Kelola akun admin lain (super_admin only — digerbang oleh filter 'superadmin'
 * di Routes.php). Untuk ganti password akun sendiri, lihat Admin\Akun.
 */
class AdminAkun extends BaseController
{
    protected AdminAkunModel $model;

    public function __construct()
    {
        $this->model = new AdminAkunModel();
    }

    public function list()
    {
        $rows = array_map(function ($r) {
            unset($r['password']);
            return $r;
        }, $this->model->getAllAkun());

        return $this->response->setJSON(['success' => true, 'data' => $rows]);
    }

    public function store()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[6]',
            'role'     => 'required|in_list[' . implode(',', AdminAkunModel::ROLES) . ']',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $username = trim((string) $this->request->getPost('username'));
        if ($this->model->usernameDipakai($username)) {
            return $this->response->setJSON(['success' => false, 'errors' => ['username' => 'Username sudah dipakai akun lain.']]);
        }

        $id = $this->model->insert([
            'username' => $username,
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'role'     => $this->request->getPost('role'),
            'aktif'    => 1,
        ]);

        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Akun admin berhasil ditambahkan']);
    }

    public function update($id)
    {
        $akun = $this->model->find($id);
        if (!$akun) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akun tidak ditemukan']);
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'role'     => 'required|in_list[' . implode(',', AdminAkunModel::ROLES) . ']',
        ];
        $newPassword = trim((string) $this->request->getPost('password'));
        if ($newPassword !== '') {
            $rules['password'] = 'min_length[6]';
        }
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $username = trim((string) $this->request->getPost('username'));
        if ($this->model->usernameDipakai($username, (int) $id)) {
            return $this->response->setJSON(['success' => false, 'errors' => ['username' => 'Username sudah dipakai akun lain.']]);
        }

        $roleBaru = $this->request->getPost('role');
        if ($akun['role'] === 'super_admin' && $roleBaru !== 'super_admin' && $this->model->countAktifSuperAdmin((int) $id) === 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak bisa mengubah role — ini satu-satunya Super Admin aktif.']);
        }

        $data = ['username' => $username, 'role' => $roleBaru];
        if ($newPassword !== '') {
            $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }
        $this->model->update($id, $data);

        // Kalau admin sedang mengubah akunnya sendiri, sinkronkan session
        if ((int) session()->get('admin_id') === (int) $id) {
            session()->set(['admin_username' => $username, 'admin_role' => $roleBaru]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Akun admin berhasil diupdate']);
    }

    public function toggleAktif($id)
    {
        $akun = $this->model->find($id);
        if (!$akun) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akun tidak ditemukan']);
        }

        if ((int) $id === (int) session()->get('admin_id')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak bisa menonaktifkan akun yang sedang Anda pakai.']);
        }

        $aktifBaru = $akun['aktif'] ? 0 : 1;
        if ($akun['role'] === 'super_admin' && $aktifBaru === 0 && $this->model->countAktifSuperAdmin((int) $id) === 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak bisa menonaktifkan — ini satu-satunya Super Admin aktif.']);
        }

        $this->model->update($id, ['aktif' => $aktifBaru]);
        return $this->response->setJSON([
            'success' => true,
            'message' => $aktifBaru ? 'Akun diaktifkan kembali' : 'Akun dinonaktifkan',
        ]);
    }
}
