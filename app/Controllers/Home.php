<?php

namespace App\Controllers;

use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;

class Home extends BaseController
{
    public function index(): string
    {
        $pemasukanModel  = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $pengaturanModel = new PengaturanModel();

        $setting = $pengaturanModel->getSetting();

        // Total keseluruhan (semua waktu)
        $totalPemasukan   = $pemasukanModel->getTotalDiterima();
        $totalPengeluaran = $pengeluaranModel->getTotalPengeluaran();
        $saldoAkhir       = $totalPemasukan - $totalPengeluaran;
        $rasio            = $totalPemasukan > 0 ? round(($totalPengeluaran / $totalPemasukan) * 100, 1) : 0;

        // Total per periode (tahun ini & bulan ini) untuk KPI toggle
        $bulan = (int)date('m');
        $tahun = (int)date('Y');
        $pemasukanTahunIni   = $pemasukanModel->getTotalDiterima(null, $tahun);
        $pengeluaranTahunIni = $pengeluaranModel->getTotalPengeluaran(null, $tahun);
        $pemasukanBulanIni   = $pemasukanModel->getTotalDiterima($bulan, $tahun);
        $pengeluaranBulanIni = $pengeluaranModel->getTotalPengeluaran($bulan, $tahun);

        // Tren pemasukan/pengeluaran bisa disortir per tahun; default tahun berjalan
        $tahunTren = (int) ($this->request->getGet('tahun_tren') ?? date('Y'));
        $tahunTersedia = array_unique(array_merge(
            $pemasukanModel->getAvailableYears(),
            $pengeluaranModel->getAvailableYears(),
            [(int) date('Y')]
        ));
        rsort($tahunTersedia);

        $pBulan = $pemasukanModel->getDataPerBulan($tahunTren);
        $eBulan = $pengeluaranModel->getDataPerBulan($tahunTren);

        $chartPemasukan  = array_fill(0, 12, 0);
        $chartPengeluaran = array_fill(0, 12, 0);
        foreach ($pBulan as $row) $chartPemasukan[(int)$row['bulan'] - 1] = (float)$row['total'];
        foreach ($eBulan as $row) $chartPengeluaran[(int)$row['bulan'] - 1] = (float)$row['total'];

        // Pie chart kategori pengeluaran (mengikuti tahun tren yang dipilih)
        $pieData = $pengeluaranModel->getDataPerKategori(null, $tahunTren);

        // Catatan: daftar transaksi sepenuhnya dimuat lewat AJAX (getTransaksiAjax), jadi
        // tidak perlu dihitung lagi di sini.

        return view('public/dashboard', [
            'saldoAkhir'          => $saldoAkhir,
            'totalPemasukan'      => $totalPemasukan,
            'totalPengeluaran'    => $totalPengeluaran,
            'pemasukanTahunIni'   => $pemasukanTahunIni,
            'pengeluaranTahunIni' => $pengeluaranTahunIni,
            'pemasukanBulanIni'   => $pemasukanBulanIni,
            'pengeluaranBulanIni' => $pengeluaranBulanIni,
            'rasio'               => $rasio,
            'chartPemasukan'      => json_encode($chartPemasukan),
            'chartPengeluaran'    => json_encode($chartPengeluaran),
            'pieData'             => json_encode($pieData),
            'tahunTren'           => $tahunTren,
            'tahunTersedia'       => $tahunTersedia,
        ]);
    }

    /**
     * AJAX endpoint untuk daftar transaksi publik.
     * per_page sekarang total gabungan (misal 100 = 100 transaksi total, bukan 100+100).
     */
    public function getTransaksiAjax()
    {
        $pemasukanModel   = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();

        $filters = [
            'search'   => $this->request->getGet('search'),
            'bulan'    => $this->request->getGet('bulan'),
            'tahun'    => $this->request->getGet('tahun'),
            'kategori' => (array)($this->request->getGet('kategori') ?? []),
        ];

        // Filter tipe (checkbox): defaultnya kedua-duanya aktif
        $showP = $this->request->getGet('show_pemasukan');
        $showE = $this->request->getGet('show_pengeluaran');
        $showPemasukan   = ($showP === null || $showP === '1');
        $showPengeluaran = ($showE === null || $showE === '1');

        // page dibiarkan null kalau tidak dikirim — biar fetchTransaksiGabungan() otomatis
        // memilih halaman TERAKHIR (transaksi terbaru) sebagai default.
        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;
        $perPage = (int)($this->request->getGet('per_page') ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;

        $result = $this->fetchTransaksiGabungan(
            $pemasukanModel,
            $pengeluaranModel,
            $filters,
            $showPemasukan,
            $showPengeluaran,
            $perPage,
            $page
        );

        return $this->response->setJSON([
            'data'        => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'per_page'    => $perPage,
            'total_pages' => $result['total_pages'],
        ]);
    }

    /**
     * AJAX endpoint: KPI total pemasukan & pengeluaran untuk periode spesifik (publik).
     */
    public function kpiPeriode()
    {
        $scope = $this->request->getGet('scope') ?? 'semua';
        $tahun = (int)($this->request->getGet('tahun') ?? date('Y'));
        $bulan = (int)($this->request->getGet('bulan') ?? date('m'));

        $pemasukanModel   = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();

        if ($scope === 'bulan') {
            $totalP = $pemasukanModel->getTotalDiterima($bulan, $tahun);
            $totalE = $pengeluaranModel->getTotalPengeluaran($bulan, $tahun);
        } elseif ($scope === 'tahun') {
            $totalP = $pemasukanModel->getTotalDiterima(null, $tahun);
            $totalE = $pengeluaranModel->getTotalPengeluaran(null, $tahun);
        } else {
            $totalP = $pemasukanModel->getTotalDiterima();
            $totalE = $pengeluaranModel->getTotalPengeluaran();
        }

        return $this->response->setJSON([
            'success'          => true,
            'total_pemasukan'  => (float)$totalP,
            'total_pengeluaran'=> (float)$totalE,
        ]);
    }

    /**
     * AJAX endpoint: data tren per bulan berdasarkan tahun (untuk chart publik).
     */
    public function chartTren()
    {
        $tahun = (int)($this->request->getGet('tahun') ?? date('Y'));
        $pemasukanModel   = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();

        $pBulan = $pemasukanModel->getDataPerBulan($tahun);
        $eBulan = $pengeluaranModel->getDataPerBulan($tahun);

        $chartP = array_fill(0, 12, 0);
        $chartE = array_fill(0, 12, 0);
        foreach ($pBulan as $r) $chartP[(int)$r['bulan'] - 1] = (float)$r['total'];
        foreach ($eBulan as $r) $chartE[(int)$r['bulan'] - 1] = (float)$r['total'];

        return $this->response->setJSON([
            'success'     => true,
            'tahun'       => $tahun,
            'pemasukan'   => $chartP,
            'pengeluaran' => $chartE,
        ]);
    }

    /**
     * Ambil transaksi gabungan sesuai filter + tipe yang di-check + per_page sebagai
     * total gabungan (bukan per-tipe). Menyaring kategori sesuai daftar per tipe supaya
     * tidak salah filter (kategori pemasukan tidak nyasar ke pengeluaran, dst).
     *
     * Penomoran baris SELALU ascending dari transaksi paling awal (nomor 1 = transaksi
     * tertua). Tapi kalau $page tidak diisi (null), yang ditampilkan default adalah
     * HALAMAN TERAKHIR — yaitu transaksi-transaksi terbaru — supaya user tetap melihat
     * aktivitas terkini begitu halaman dibuka, walau nomor barisnya tetap urut dari awal.
     *
     * Query tetap diambil dalam urutan DESC (termurah untuk data terbaru) lalu dibalik jadi
     * ASC untuk ditampilkan — jadi kasus paling umum (halaman default/terbaru) tetap murah;
     * yang jadi mahal cuma saat user sengaja menjelajah jauh ke histori paling awal.
     */
    private function fetchTransaksiGabungan($pemasukanModel, $pengeluaranModel, $filters, $showPemasukan, $showPengeluaran, $perPage, $page)
    {
        // Pisahkan kategori per tipe agar filter tidak nyasar
        $kat = $filters['kategori'] ?? [];
        $katP = !empty($kat) ? array_values(array_intersect($kat, \App\Config\Kategori::$pemasukan)) : [];
        $katE = !empty($kat) ? array_values(array_intersect($kat, \App\Config\Kategori::$pengeluaran)) : [];

        // Kalau user memilih kategori dan checkbox tipe masih aktif, filter memaksa hanya tipe yang ada kategorinya
        $filtersP = array_merge($filters, ['kategori' => $katP]);
        $filtersE = array_merge($filters, ['kategori' => $katE]);

        // Kalau ada filter kategori tapi tidak ada kategori yang cocok untuk tipe itu, skip
        $skipP = !$showPemasukan || (!empty($kat) && empty($katP));
        $skipE = !$showPengeluaran || (!empty($kat) && empty($katE));

        // Total sebenarnya lewat COUNT query di DB
        $total = ($skipP ? 0 : $pemasukanModel->countFiltered($filtersP))
               + ($skipE ? 0 : $pengeluaranModel->countFiltered($filtersE));

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = $page ?? $totalPages; // default: halaman terakhir (data terbaru)
        $page = max(1, min($page, $totalPages));

        // Rentang nomor baris ascending yang perlu ditampilkan di halaman ini
        $startNum = ($page - 1) * $perPage + 1;
        $endNum   = min($total, $page * $perPage);
        $take     = max(0, $endNum - $startNum + 1);

        // Terjemahkan ke posisi DESC: descOffset = jarak dari transaksi TERBARU ke ujung
        // akhir rentang halaman ini.
        $descOffset = max(0, $total - $endNum);
        $fetchLimit = $descOffset + $take;

        // 'catatan_internal' disengaja hanya untuk admin (labelnya "Admin Only" di form) —
        // endpoint ini publik & tanpa login, jadi field itu wajib dibuang sebelum dikirim,
        // meskipun getFiltered() mengembalikan semua kolom (dipakai bersama oleh admin juga).
        $data = [];
        if (!$skipP) {
            foreach ($pemasukanModel->getFiltered($filtersP, $fetchLimit, 0) as $r) {
                unset($r['catatan_internal']);
                $data[] = array_merge($r, ['tipe' => 'pemasukan']);
            }
        }
        if (!$skipE) {
            foreach ($pengeluaranModel->getFiltered($filtersE, $fetchLimit, 0) as $r) {
                unset($r['catatan_internal']);
                $data[] = array_merge($r, ['tipe' => 'pengeluaran']);
            }
        }

        // Urutkan berdasarkan tanggal terbaru, secondary id terbaru (DESC)
        usort($data, function ($a, $b) {
            $t = strtotime($b['tanggal']) - strtotime($a['tanggal']);
            if ($t !== 0) return $t;
            return ((int)($b['id'] ?? 0)) - ((int)($a['id'] ?? 0));
        });

        // Ambil persis bagian yang dibutuhkan (masih DESC), lalu balik ke ASC supaya nomor
        // baris berjalan naik dari transaksi paling awal.
        $slice = array_reverse(array_slice($data, $descOffset, $take));
        foreach ($slice as $i => &$row) {
            $row['nomor'] = $startNum + $i;
        }
        unset($row);

        return ['data' => $slice, 'total' => $total, 'page' => $page, 'total_pages' => $totalPages];
    }
}
