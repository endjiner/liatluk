<?php

namespace App\Controllers;

use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;

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

        // Perjalanan Dinas & Dana Taktis — ditampilkan sebagai tab di halaman yang sama
        // (lihat #panel-perjadin di public/dashboard.php), bukan halaman terpisah. Daftarnya
        // sepenuhnya dimuat lewat AJAX (perjalananDinasAjax), jadi tidak dihitung di sini.

        // Ringkasan Dana Taktis belum dibayar — ditampilkan sebagai KPI card di samping Saldo Kas.
        $danaTaktisBelumDibayar = (new PerjalananDinasPesertaModel())->getBelumDibayarSummary();

        return view('public/dashboard', [
            'saldoAkhir'              => $saldoAkhir,
            'totalPemasukan'          => $totalPemasukan,
            'totalPengeluaran'        => $totalPengeluaran,
            'pemasukanTahunIni'       => $pemasukanTahunIni,
            'pengeluaranTahunIni'     => $pengeluaranTahunIni,
            'pemasukanBulanIni'       => $pemasukanBulanIni,
            'pengeluaranBulanIni'     => $pengeluaranBulanIni,
            'rasio'                   => $rasio,
            'chartPemasukan'          => json_encode($chartPemasukan),
            'chartPengeluaran'        => json_encode($chartPengeluaran),
            'pieData'                 => json_encode($pieData),
            'tahunTren'               => $tahunTren,
            'tahunTersedia'           => $tahunTersedia,
            'tahunListPerjadin'       => (new PerjalananDinasModel())->getAvailableYears(),
            'danaTaktisBelumDibayar'  => $danaTaktisBelumDibayar,
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

        $result = \App\Services\TransaksiService::getTransaksiGabungan(
            $filters,
            $showPemasukan,
            $showPengeluaran,
            $perPage,
            $page,
            true
        );

        return $this->response->setJSON($result);
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

    // ── Rekap Perjalanan Dinas & Dana Taktis (publik, read-only, tanpa login) ────────

    /** 'tahun' sengaja tidak default ke tahun berjalan — lihat catatan yang sama di
     *  Admin\PerjalananDinas::ambilFilterGet(). */
    private function ambilFilterPerjalananDinasGet(): array
    {
        $perPage = (int)($this->request->getGet('per_page') ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;

        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;

        return [
            'bulan'    => $this->request->getGet('bulan'),
            'tahun'    => $this->request->getGet('tahun'),
            'status'   => $this->request->getGet('status'),
            'search'   => $this->request->getGet('search'),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /** Halaman terpisah lama — sekarang jadi tab "Perjalanan Dinas" di halaman utama
     *  (lihat Home::index() & #panel-perjadin di public/dashboard.php). Redirect
     *  dipertahankan supaya tautan lama tidak mati. */
    public function perjalananDinas()
    {
        return redirect()->to(base_url('#perjadin'));
    }

    /** JSON untuk fetch() dari filter/search/pagination publik — tabel datar (1 baris =
     *  1 peserta), sama seperti Admin\PerjalananDinas::ajaxList() tapi read-only. */
    public function perjalananDinasAjax()
    {
        $filters      = $this->ambilFilterPerjalananDinasGet();
        $pesertaModel = new PerjalananDinasPesertaModel();
        $result       = $pesertaModel->getPaginatedPerjalananDinas($filters);

        return $this->response->setJSON(['success' => true] + $result);
    }

    /** Halaman terpisah lama — sekarang jadi tab "Dana Taktis" tersendiri di halaman
     *  utama. Redirect dipertahankan supaya tautan lama tidak mati. */
    public function danaTaktis()
    {
        return redirect()->to(base_url('#dana-taktis'));
    }

    /** JSON untuk fetch() dari filter/search/pagination publik — tabel datar semua pegawai,
     *  sama seperti Admin\PerjalananDinas::danaTaktisList() tapi read-only (tanpa aksi
     *  tandai-lunas). */
    public function danaTaktisAjax()
    {
        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;

        $filters = [
            'search'   => $this->request->getGet('search'),
            'status'   => $this->request->getGet('status'),
            'tahun'    => $this->request->getGet('tahun'),
            'bulan'    => $this->request->getGet('bulan'),
            'page'     => $page,
            'per_page' => (int)($this->request->getGet('per_page') ?? 10),
        ];

        $pesertaModel = new PerjalananDinasPesertaModel();
        $result       = $pesertaModel->getPaginatedDanaTaktis($filters);

        return $this->response->setJSON(['success' => true] + $result);
    }
}
