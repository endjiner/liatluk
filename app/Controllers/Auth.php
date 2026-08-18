<?php

namespace App\Controllers;

use App\Models\PengaturanModel;

class Auth extends BaseController
{
    public function login()
    {
        // Parameter logout paksa
        if ($this->request->getGet('logout') === '1') {
            session()->destroy();
            return redirect()->to('/login')->with('info', 'Sesi Anda telah diakhiri. Silakan login kembali.');
        }

        return view('auth/login', [
            'isAlreadyLoggedIn' => (bool) session()->get('is_admin'),
        ]);
    }

    public function doLogin()
    {
        $username = trim($this->request->getPost('username') ?? '');
        $password = trim($this->request->getPost('password') ?? '');

        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Username dan password wajib diisi.')->withInput();
        }

        // Batasi percobaan login per-IP (10/5 menit) dan per-username (5/5 menit — lebih
        // ketat karena cuma ada satu akun admin sah) supaya tidak bisa di-brute-force.
        $throttler = service('throttler');
        $ip = $this->request->getIPAddress();
        if ($throttler->check('login_ip_' . $ip, 10, 300) === false
            || $throttler->check('login_user_' . strtolower($username), 5, 300) === false) {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.')->withInput();
        }

        $pengaturanModel = new PengaturanModel();

        if ($pengaturanModel->verifyAdmin($username, $password)) {
            $throttler->remove('login_ip_' . $ip)->remove('login_user_' . strtolower($username));
            session()->regenerate(true); // rotasi session ID, buang sesi lama sepenuhnya
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
        // Pastikan cookie sesi lama dihapus dari browser sepenuhnya
        return redirect()->to('/login?logout=1')
            ->setHeader('Clear-Site-Data', '"cookies","storage"');
    }
}
