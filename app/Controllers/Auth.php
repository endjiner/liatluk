<?php

namespace App\Controllers;

use App\Models\PengaturanModel;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->request->getGet('logout') === '1') {
            session()->destroy();
            return redirect()->to('/login')->with('info', 'Sesi Anda telah diakhiri. Silakan login kembali.');
        }

        return view('auth/login', [
            'isAlreadyLoggedIn' => (bool) session()->get('is_admin'),
        ]);
    }

    /** Cache key CI4 menolak karakter {}()/\@: — IPv6 loopback (::1) mengandung ':',
     *  jadi harus disaring dulu sebelum dipakai sebagai bagian key throttler. */
    private function sanitizeCacheKeyPart(string $part): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $part);
    }

    public function doLogin()
    {
        $username = trim($this->request->getPost('username') ?? '');
        $password = trim($this->request->getPost('password') ?? '');

        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Username dan password wajib diisi.')->withInput();
        }

        $throttler = service('throttler');
        $ip = $this->sanitizeCacheKeyPart($this->request->getIPAddress());
        $userKey = $this->sanitizeCacheKeyPart(strtolower($username));
        if ($throttler->check('login_ip_' . $ip, 10, 300) === false
            || $throttler->check('login_user_' . $userKey, 5, 300) === false) {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.')->withInput();
        }

        $pengaturanModel = new PengaturanModel();

        if ($pengaturanModel->verifyAdmin($username, $password)) {
            $throttler->remove('login_ip_' . $ip)->remove('login_user_' . $userKey);
            session()->regenerate(true);
            session()->set([
                'is_admin'       => true,
                'admin_username' => $username,
            ]);
            return redirect()->to('/admin')->with('success', 'Selamat datang kembali, ' . $username . '!');
        }

        return redirect()->back()->with('error', 'Username atau password yang Anda masukkan salah.')->withInput();
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login?logout=1')
            ->setHeader('Clear-Site-Data', '"cookies","storage"');
    }
}
