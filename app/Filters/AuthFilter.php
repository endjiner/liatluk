<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('is_admin')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Kritikal: cegah browser menyimpan cache halaman admin di riwayat (bfcache),
        // supaya tombol Back setelah logout tidak menampilkan halaman lama yang sudah tidak sah.
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                 ->setHeader('Pragma', 'no-cache')
                 ->setHeader('Expires', '0');
    }
}
