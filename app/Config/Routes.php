<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── PUBLIC ROUTES ────────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('/transaksi/ajax', 'Home::getTransaksiAjax');
$routes->get('/kpi-periode', 'Home::kpiPeriode');
$routes->get('/chart-tren', 'Home::chartTren');
$routes->get('/perjalanan-dinas', 'Home::perjalananDinas');
$routes->get('/perjalanan-dinas/ajax', 'Home::perjalananDinasAjax');
$routes->get('/perjalanan-dinas/dana-taktis', 'Home::danaTaktis');
$routes->get('/perjalanan-dinas/dana-taktis/ajax', 'Home::danaTaktisAjax');

// Auth
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::doLogin');
$routes->get('/logout', 'Auth::logout');

// ── ADMIN ROUTES (protected by auth filter) ───────────────────────────────────
// Role 'admin' (terbatas) hanya boleh mengelola Perjalanan Dinas & Dana Taktis;
// 'super_admin' bisa semua. Rute yang harus super_admin dikelompokkan lagi di
// bawah filter 'superadmin' (ditumpuk di atas 'auth' — lihat SuperAdminFilter).
$routes->group('admin', ['filter' => 'auth'], function ($routes) {

    // Dashboard (kedua role — kontennya menyesuaikan role di controller/view)
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // Perjalanan Dinas (rekap SPJ & Dana Taktis) + Kelola Pegawai — kedua role
    $routes->get('perjalanan-dinas', 'Admin\PerjalananDinas::index');
    $routes->get('perjalanan-dinas/ajax', 'Admin\PerjalananDinas::ajaxList');
    $routes->post('perjalanan-dinas', 'Admin\PerjalananDinas::store');
    $routes->post('perjalanan-dinas/update/(:num)', 'Admin\PerjalananDinas::update/$1');
    $routes->post('perjalanan-dinas/delete/(:num)', 'Admin\PerjalananDinas::delete/$1');
    $routes->post('perjalanan-dinas/peserta', 'Admin\PerjalananDinas::storePeserta');
    $routes->post('perjalanan-dinas/peserta/update/(:num)', 'Admin\PerjalananDinas::updatePeserta/$1');
    $routes->post('perjalanan-dinas/peserta/delete/(:num)', 'Admin\PerjalananDinas::deletePeserta/$1');
    $routes->post('perjalanan-dinas/lunas/(:num)', 'Admin\PerjalananDinas::toggleLunas/$1');
    $routes->get('perjalanan-dinas/dana-taktis', 'Admin\PerjalananDinas::danaTaktis');
    $routes->get('perjalanan-dinas/dana-taktis/list', 'Admin\PerjalananDinas::danaTaktisList');
    $routes->get('perjalanan-dinas/dana-taktis/data/(:num)', 'Admin\PerjalananDinas::danaTaktisData/$1');
    $routes->get('perjalanan-dinas/pegawai', 'Admin\PerjalananDinas::pegawaiList');
    $routes->post('perjalanan-dinas/pegawai', 'Admin\PerjalananDinas::pegawaiStore');
    $routes->post('perjalanan-dinas/pegawai/update/(:num)', 'Admin\PerjalananDinas::pegawaiUpdate/$1');
    $routes->post('perjalanan-dinas/pegawai/delete/(:num)', 'Admin\PerjalananDinas::pegawaiDelete/$1');
    $routes->post('perjalanan-dinas/pegawai/nonaktifkan/(:num)', 'Admin\PerjalananDinas::pegawaiNonaktifkan/$1');


    // Akun sendiri (kedua role — ganti password sendiri)
    $routes->post('ganti-password', 'Admin\Akun::gantiPasswordSaya');

    // Dashboard: AJAX finansial (KPI period-picker, chart tren, list transaksi) — kedua role
    $routes->get('dashboard/kpi-periode', 'Admin\Dashboard::kpiPeriode');
    $routes->get('dashboard/chart-tren', 'Admin\Dashboard::chartTren');
    $routes->get('keuangan/ajax', 'Admin\DataKeuangan::ajaxList');

    // Laporan & Export (kedua role — termasuk ekspor Perjalanan Dinas & Dana Taktis)
    $routes->get('laporan', 'Admin\Laporan::index');
    $routes->get('laporan/export-pdf', 'Admin\Laporan::exportPdf');
    $routes->get('laporan/export-excel', 'Admin\Laporan::exportExcel');

    // ── Fitur khusus Super Admin ───────────────────────────────────────────
    $routes->group('', ['filter' => 'superadmin'], function ($routes) {

        // Data Keuangan (kelola & mutasi)
        $routes->get('keuangan', 'Admin\DataKeuangan::index');
        $routes->post('keuangan/pemasukan', 'Admin\DataKeuangan::storePemasukan');
        $routes->post('keuangan/pemasukan/update/(:num)', 'Admin\DataKeuangan::updatePemasukan/$1');
        $routes->post('keuangan/pemasukan/delete/(:num)', 'Admin\DataKeuangan::deletePemasukan/$1');
        $routes->post('keuangan/pemasukan/status/(:num)', 'Admin\DataKeuangan::updateStatusDana/$1');
        $routes->post('keuangan/pengeluaran', 'Admin\DataKeuangan::storePengeluaran');
        $routes->post('keuangan/pengeluaran/update/(:num)', 'Admin\DataKeuangan::updatePengeluaran/$1');
        $routes->post('keuangan/pengeluaran/delete/(:num)', 'Admin\DataKeuangan::deletePengeluaran/$1');
        $routes->post('keuangan/bulk-delete', 'Admin\DataKeuangan::bulkDelete');
        $routes->post('keuangan/import', 'Admin\DataKeuangan::import');
        $routes->post('keuangan/import-csv', 'Admin\DataKeuangan::importCsv'); // alias lama
        $routes->get('keuangan/template-universal-csv', 'Admin\DataKeuangan::downloadTemplateUniversalCsv');
        $routes->get('keuangan/template-universal-excel', 'Admin\DataKeuangan::downloadTemplateUniversalExcel');

        // Pengaturan
        $routes->get('pengaturan', 'Admin\Pengaturan::index');
        $routes->post('pengaturan', 'Admin\Pengaturan::update');

        // Kelola Akun Admin
        $routes->get('admin-akun', 'Admin\AdminAkun::list');
        $routes->post('admin-akun', 'Admin\AdminAkun::store');
        $routes->post('admin-akun/update/(:num)', 'Admin\AdminAkun::update/$1');
        $routes->post('admin-akun/toggle-aktif/(:num)', 'Admin\AdminAkun::toggleAktif/$1');
    });
});
