<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── PUBLIC ROUTES ────────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('/transaksi/ajax', 'Home::getTransaksiAjax');
$routes->get('/kpi-periode', 'Home::kpiPeriode');
$routes->get('/chart-tren', 'Home::chartTren');

// Auth
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::doLogin');
$routes->get('/logout', 'Auth::logout');

// ── ADMIN ROUTES (protected by auth filter) ───────────────────────────────────
$routes->group('admin', ['filter' => 'auth'], function ($routes) {

    // Dashboard
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->get('dashboard/kpi-periode', 'Admin\Dashboard::kpiPeriode');
    $routes->get('dashboard/chart-tren', 'Admin\Dashboard::chartTren');

    // Data Keuangan
    $routes->get('keuangan', 'Admin\DataKeuangan::index');
    $routes->get('keuangan/ajax', 'Admin\DataKeuangan::ajaxList');
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

    // Rencana Keuangan
    $routes->get('rencana', 'Admin\RencanaKeuangan::index');
    $routes->post('rencana/pemasukan', 'Admin\RencanaKeuangan::storeRencanaPemasukan');
    $routes->post('rencana/pemasukan/update/(:num)', 'Admin\RencanaKeuangan::updateRencanaPemasukan/$1');
    $routes->post('rencana/pemasukan/delete/(:num)', 'Admin\RencanaKeuangan::deleteRencanaPemasukan/$1');
    $routes->post('rencana/pemasukan/realisasi/(:num)', 'Admin\RencanaKeuangan::realisasiPemasukan/$1');
    $routes->post('rencana/pengeluaran', 'Admin\RencanaKeuangan::storeRencanaPengeluaran');
    $routes->post('rencana/pengeluaran/update/(:num)', 'Admin\RencanaKeuangan::updateRencanaPengeluaran/$1');
    $routes->post('rencana/pengeluaran/delete/(:num)', 'Admin\RencanaKeuangan::deleteRencanaPengeluaran/$1');
    $routes->post('rencana/pengeluaran/realisasi/(:num)', 'Admin\RencanaKeuangan::realisasiPengeluaran/$1');

    // Laporan
    $routes->get('laporan', 'Admin\Laporan::index');
    $routes->get('laporan/export-pdf', 'Admin\Laporan::exportPdf');
    $routes->get('laporan/export-excel', 'Admin\Laporan::exportExcel');

    // Pengaturan
    $routes->get('pengaturan', 'Admin\Pengaturan::index');
    $routes->post('pengaturan', 'Admin\Pengaturan::update');

    // Notifikasi
    $routes->get('notifikasi', 'Admin\Notifikasi::getUnread'); // AJAX dropdown
    $routes->get('notifikasi/semua', 'Admin\Notifikasi::index'); // halaman penuh + filter
    $routes->post('notifikasi/read/(:num)', 'Admin\Notifikasi::markRead/$1');
    $routes->post('notifikasi/read-all', 'Admin\Notifikasi::markAllRead');
});
