<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;
use App\Models\NotifikasiModel;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PegawaiModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $isSuperAdmin    = session()->get('admin_role') === 'super_admin';

        // Data Dana Taktis/Perjadin dibutuhkan kedua role
        $data = [
            'isSuperAdmin'           => $isSuperAdmin,
            'danaTaktisBelumDisetor' => (new PerjalananDinasPesertaModel())->getBelumDibayarSummary(),
            'pegawaiList'            => (new PegawaiModel())->getAktifList(),
            'tahunListPerjadin'      => (new PerjalananDinasModel())->getAvailableYears(),
        ];

        // Data finansial (Summary transaksi & arus kas) ditampilkan untuk kedua role
        $pemasukanModel     = new PemasukanModel();
        $pengeluaranModel   = new PengeluaranModel();
        $pengaturanModel    = new PengaturanModel();
        $notifikasiModel    = new NotifikasiModel();

        $setting = $pengaturanModel->getSetting();
        $bulan   = (int)date('m');
        $tahun   = (int)date('Y');

        $totalPemasukan   = $pemasukanModel->getTotalDiterima();
        $totalPengeluaran = $pengeluaranModel->getTotalPengeluaran();
        $saldoAkhir       = $totalPemasukan - $totalPengeluaran;

        $pBulan = $pemasukanModel->getDataPerBulan($tahun);
        $eBulan = $pengeluaranModel->getDataPerBulan($tahun);
        $chartPemasukan   = array_fill(0, 12, 0);
        $chartPengeluaran = array_fill(0, 12, 0);
        foreach ($pBulan as $row) $chartPemasukan[(int)$row['bulan'] - 1] = (float)$row['total'];
        foreach ($eBulan as $row) $chartPengeluaran[(int)$row['bulan'] - 1] = (float)$row['total'];

        $data += [
            'saldoAkhir'          => $saldoAkhir,
            'totalPemasukan'      => $totalPemasukan,
            'totalPengeluaran'    => $totalPengeluaran,
            'pemasukanTahunIni'   => $pemasukanModel->getTotalDiterima(null, $tahun),
            'pengeluaranTahunIni' => $pengeluaranModel->getTotalPengeluaran(null, $tahun),
            'pemasukanBulanIni'   => $pemasukanModel->getTotalDiterima($bulan, $tahun),
            'pengeluaranBulanIni' => $pengeluaranModel->getTotalPengeluaran($bulan, $tahun),
            'rasio'               => $totalPemasukan > 0 ? round(($totalPengeluaran / $totalPemasukan) * 100, 1) : 0,
            'chartPemasukan'      => json_encode($chartPemasukan),
            'chartPengeluaran'    => json_encode($chartPengeluaran),
            'pieData'             => json_encode($pengeluaranModel->getDataPerKategori(null, $tahun)),
            'danaBelumDiterima'   => $pemasukanModel->getTotalBelumDiterima(),
        ];

        // Cek threshold saldo rendah (hanya super_admin yang memicu notifikasi)
        if ($isSuperAdmin && $setting && $saldoAkhir < (float)$setting['threshold_notif'] && $setting['notif_saldo_rendah']) {
            if ($notifikasiModel->where('tipe', 'saldo_rendah')->where('is_read', 0)->countAllResults() === 0) {
                $notifikasiModel->tambahNotifikasi(
                    'Perhatian: Saldo kas mendekati batas minimum (Rp ' . number_format($setting['threshold_notif'], 0, ',', '.') . ')',
                    'saldo_rendah',
                    'sistem'
                );
            }
        }

        return view('admin/dashboard', $data);
    }

    /**
     * AJAX endpoint: query total pemasukan & pengeluaran untuk periode spesifik.
     * Query params: ?scope=semua|tahun|bulan &tahun=YYYY &bulan=MM
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
     * AJAX endpoint: data tren per bulan berdasarkan tahun (untuk chart admin).
     * Query param: ?tahun=YYYY
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
}
