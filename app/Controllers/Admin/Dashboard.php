<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\RencanaPemasukanModel;
use App\Models\RencanaPengeluaranModel;
use App\Models\PengaturanModel;
use App\Models\NotifikasiModel;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PegawaiModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $pemasukanModel      = new PemasukanModel();
        $pengeluaranModel    = new PengeluaranModel();
        $rencanaPemasukan    = new RencanaPemasukanModel();
        $rencanaPengeluaran  = new RencanaPengeluaranModel();
        $pengaturanModel     = new PengaturanModel();
        $notifikasiModel     = new NotifikasiModel();

        $setting  = $pengaturanModel->getSetting();

        $bulan = (int)date('m');
        $tahun = (int)date('Y');

        $totalPemasukan      = $pemasukanModel->getTotalDiterima();
        $totalPengeluaran    = $pengeluaranModel->getTotalPengeluaran();
        $saldoAkhir          = $totalPemasukan - $totalPengeluaran;
        $pemasukanTahunIni   = $pemasukanModel->getTotalDiterima(null, $tahun);
        $pengeluaranTahunIni = $pengeluaranModel->getTotalPengeluaran(null, $tahun);
        $pemasukanBulanIni   = $pemasukanModel->getTotalDiterima($bulan, $tahun);
        $pengeluaranBulanIni = $pengeluaranModel->getTotalPengeluaran($bulan, $tahun);
        $rasio               = $totalPemasukan > 0 ? round(($totalPengeluaran / $totalPemasukan) * 100, 1) : 0;

        // Chart data
        $pBulan = $pemasukanModel->getDataPerBulan($tahun);
        $eBulan = $pengeluaranModel->getDataPerBulan($tahun);
        $chartPemasukan  = array_fill(0, 12, 0);
        $chartPengeluaran = array_fill(0, 12, 0);
        foreach ($pBulan as $row) $chartPemasukan[(int)$row['bulan'] - 1] = (float)$row['total'];
        foreach ($eBulan as $row) $chartPengeluaran[(int)$row['bulan'] - 1] = (float)$row['total'];

        // Pie chart kategori pengeluaran
        $pieData = $pengeluaranModel->getDataPerKategori(null, $tahun);

        // Nilai untuk kartu peringatan (hanya tampil jika nilainya > 0, lihat view)
        $danaBelumDiterima = $pemasukanModel->getTotalBelumDiterima();
        $totalRencanaPemasukan = $rencanaPemasukan->getTotalRencana();
        $totalRencanaPengeluaran = $rencanaPengeluaran->getTotalRencana();
        $danaTaktisBelumDisetor = (new PerjalananDinasPesertaModel())->getBelumDibayarSummary();

        // Rencana aktif mendatang
        $rencanaAktif = array_merge(
            array_map(fn($r) => array_merge($r, ['tipe' => 'pemasukan']), $rencanaPemasukan->getAktif()),
            array_map(fn($r) => array_merge($r, ['tipe' => 'pengeluaran']), $rencanaPengeluaran->getAktif())
        );
        usort($rencanaAktif, fn($a, $b) => strtotime($a['tanggal_rencana']) - strtotime($b['tanggal_rencana']));
        $rencanaAktif = array_slice($rencanaAktif, 0, 5);

        // Notifikasi
        $notifCount = $notifikasiModel->countUnread();

        // Cek threshold
        if ($setting && $saldoAkhir < (float)$setting['threshold_notif'] && $setting['notif_saldo_rendah']) {
            if ($notifikasiModel->where('tipe', 'saldo_rendah')->where('is_read', 0)->countAllResults() === 0) {
                $notifikasiModel->tambahNotifikasi(
                    'Perhatian: Saldo kas mendekati batas minimum (Rp ' . number_format($setting['threshold_notif'], 0, ',', '.') . ')',
                    'saldo_rendah',
                    'sistem'
                );
            }
        }

        // Pengingat rencana keuangan yang akan jatuh tempo dalam 3 hari ke depan
        $this->cekPengingatRencana($rencanaPemasukan, $rencanaPengeluaran, $notifikasiModel);

        return view('admin/dashboard', [
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
            'rencanaAktif'        => $rencanaAktif,
            'danaBelumDiterima'   => $danaBelumDiterima,
            'totalRencanaPemasukan'   => $totalRencanaPemasukan,
            'totalRencanaPengeluaran' => $totalRencanaPengeluaran,
            'danaTaktisBelumDisetor'  => $danaTaktisBelumDisetor,
            'notifCount'          => $notifCount,
            'pegawaiList'         => (new PegawaiModel())->getAktifList(),
            'tahunListPerjadin'   => (new PerjalananDinasModel())->getAvailableYears(),
        ]);
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

    /**
     * Buat notifikasi "pengingat" untuk rencana keuangan aktif, tepat 3 hari sebelum
     * jatuh tempo (H-3) dan tepat pada hari-H saja (bukan rentang tiap hari).
     */
    private function cekPengingatRencana($rencanaPemasukanModel, $rencanaPengeluaranModel, $notifikasiModel)
    {
        $tanggalH3 = date('Y-m-d', strtotime('+3 days'));
        $tanggalH0 = date('Y-m-d');

        $daftar = [
            'pemasukan'   => ['model' => $rencanaPemasukanModel, 'kode' => 'RP', 'label' => 'pemasukan'],
            'pengeluaran' => ['model' => $rencanaPengeluaranModel, 'kode' => 'RE', 'label' => 'pengeluaran'],
        ];

        foreach ($daftar as $item) {
            foreach ([$tanggalH3 => 'H3', $tanggalH0 => 'H0'] as $tanggalTarget => $kodeHari) {
                $rencanaJatuhTempo = $item['model']
                    ->where('status', 'aktif')
                    ->where('tanggal_rencana', $tanggalTarget)
                    ->findAll();

                foreach ($rencanaJatuhTempo as $r) {
                    $penanda = "({$item['kode']}#{$r['id']}-{$kodeHari})";
                    if ($notifikasiModel->pengingatSudahAda($penanda)) continue;

                    $pesanWaktu = $kodeHari === 'H3' ? '3 hari lagi' : 'HARI INI';
                    $notifikasiModel->tambahNotifikasi(
                        "Pengingat: Rencana {$item['label']} '{$r['kategori']}' senilai Rp " .
                            number_format($r['jumlah_rencana'], 0, ',', '.') .
                            " jatuh tempo {$pesanWaktu} (" . date('d M Y', strtotime($r['tanggal_rencana'])) . ") {$penanda}",
                        'pengingat',
                        'rencana'
                    );
                }
            }
        }
    }
}
