<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Dipasang di atas AuthFilter (jadi sudah pasti login) untuk rute yang hanya
 * boleh diakses role super_admin — role 'admin' (terbatas) dilempar balik ke
 * dashboard dengan pesan, bukan 403 mentah, supaya konsisten dengan gaya
 * AuthFilter yang sudah ada.
 */
class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('admin_role') !== 'super_admin') {
            return redirect()->to('/admin')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
