<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;

use App\Models\PerjalananDinasPesertaModel;

class Laporan extends BaseController
{
    protected $pemasukanModel;
    protected $pengeluaranModel;
    protected $pengaturanModel;

    public function __construct()
    {
        $this->pemasukanModel  = new PemasukanModel();
        $this->pengeluaranModel = new PengeluaranModel();
        $this->pengaturanModel = new PengaturanModel();
    }

    /**
     * Hitung semua data laporan untuk satu periode. Dipakai bersama oleh index(), exportPdf(),
     * dan exportExcel() supaya angka yang ditampilkan di layar dan yang diekspor selalu konsisten
     * (sebelumnya export menghitung "saldo akhir" hanya dari selisih periode ini saja, tanpa
     * memperhitungkan saldo awal — beda dengan angka yang tampil di halaman layar).
     */
    private function hitungLaporan(string $bulanDari, string $bulanSampai): array
    {
        [$tahunSampai, $blnSampai] = explode('-', $bulanSampai);
        $tglAkhir = $bulanSampai . '-' . cal_days_in_month(CAL_GREGORIAN, $blnSampai, $tahunSampai);

        $pemasukans = $this->pemasukanModel
            ->where('tanggal >=', $bulanDari . '-01')
            ->where('tanggal <=', $tglAkhir)
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        $pengeluarans = $this->pengeluaranModel
            ->where('tanggal >=', $bulanDari . '-01')
            ->where('tanggal <=', $tglAkhir)
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        $totalPemasukan   = array_sum(array_column($pemasukans, 'jumlah_diterima'));
        $totalPengeluaran = array_sum(array_column($pengeluarans, 'jumlah'));
        $rasio            = $totalPemasukan > 0 ? round(($totalPengeluaran / $totalPemasukan) * 100, 1) : 0;

        $kategoriPemasukan  = [];
        foreach ($pemasukans as $p) {
            $kategoriPemasukan[$p['kategori']] = ($kategoriPemasukan[$p['kategori']] ?? 0) + (float)$p['jumlah_diterima'];
        }
        $kategoriPengeluaran = [];
        foreach ($pengeluarans as $e) {
            $kategoriPengeluaran[$e['kategori']] = ($kategoriPengeluaran[$e['kategori']] ?? 0) + (float)$e['jumlah'];
        }

        // Rincian per bulan dalam periode ini (pemasukan & pengeluaran tetap dihitung terpisah,
        // cuma dikelompokkan bersama per periode YYYY-MM supaya kelihatan tren waktunya)
        $rincianBulanan = [];
        foreach ($pemasukans as $p) {
            $ym = substr($p['tanggal'], 0, 7);
            $rincianBulanan[$ym]['pemasukan'] = ($rincianBulanan[$ym]['pemasukan'] ?? 0) + (float)$p['jumlah_diterima'];
        }
        foreach ($pengeluarans as $e) {
            $ym = substr($e['tanggal'], 0, 7);
            $rincianBulanan[$ym]['pengeluaran'] = ($rincianBulanan[$ym]['pengeluaran'] ?? 0) + (float)$e['jumlah'];
        }
        ksort($rincianBulanan);
        foreach ($rincianBulanan as &$row) {
            $row['pemasukan']   = $row['pemasukan'] ?? 0;
            $row['pengeluaran'] = $row['pengeluaran'] ?? 0;
        }
        unset($row);

        // Saldo awal periode = total diterima sebelum periode - total pengeluaran sebelum periode
        $awalDate = $bulanDari . '-01';
        $db = \Config\Database::connect();
        $sumP = $db->table('pemasukan')->selectSum('jumlah_diterima', 'total')->where('tanggal <', $awalDate)->get()->getRowArray();
        $sumE = $db->table('pengeluaran')->selectSum('jumlah', 'total')->where('tanggal <', $awalDate)->get()->getRowArray();
        $saldoAwal  = (float)($sumP['total'] ?? 0) - (float)($sumE['total'] ?? 0);
        $saldoAkhir = $saldoAwal + $totalPemasukan - $totalPengeluaran;

        // Rincian gabungan kronologis dengan saldo berjalan — format buku kas umum: satu baris
        // per transaksi, pemasukan/pengeluaran tetap kolom terpisah (tidak digabung jadi satu
        // nilai bertanda), diurutkan tanggal lalu id supaya transaksi di tanggal yang sama tetap
        // berurutan sesuai urutan aslinya.
        $gabungan = [];
        foreach ($pemasukans as $p) {
            $gabungan[] = [
                'sort'        => $p['tanggal'] . '-' . str_pad((string)$p['id'], 10, '0', STR_PAD_LEFT),
                'tanggal'     => $p['tanggal'],
                'kategori'    => $p['kategori'],
                'pemasukan'   => (float)$p['jumlah_diterima'],
                'pengeluaran' => 0.0,
                'keterangan'  => $p['keterangan'],
            ];
        }
        foreach ($pengeluarans as $e) {
            $gabungan[] = [
                'sort'        => $e['tanggal'] . '-' . str_pad((string)$e['id'], 10, '0', STR_PAD_LEFT),
                'tanggal'     => $e['tanggal'],
                'kategori'    => $e['kategori'],
                'pemasukan'   => 0.0,
                'pengeluaran' => (float)$e['jumlah'],
                'keterangan'  => $e['keterangan'],
            ];
        }
        usort($gabungan, fn($a, $b) => $a['sort'] <=> $b['sort']);
        $saldoBerjalan = $saldoAwal;
        foreach ($gabungan as &$row) {
            $saldoBerjalan += $row['pemasukan'] - $row['pengeluaran'];
            $row['saldo'] = $saldoBerjalan;
        }
        unset($row);

        return compact(
            'pemasukans', 'pengeluarans', 'totalPemasukan', 'totalPengeluaran',
            'rasio', 'kategoriPemasukan', 'kategoriPengeluaran', 'rincianBulanan', 'saldoAwal', 'saldoAkhir', 'gabungan'
        );
    }

    /**
     * Ambil & validasi bulan_dari/bulan_sampai dari GET. Rentang dibatasi maksimal 60 bulan
     * (5 tahun) — tanpa batas ini, admin bisa saja diam-diam meminta laporan seluruh histori
     * (mis. 2016-2030) yang menarik SELURUH data ke PHP sekaligus (lihat hitungLaporan(): rekap
     * kategori, rincian bulanan, dan buku-kas-umum "gabungan" semuanya diagregasi di PHP, bukan
     * SQL) — di skala puluhan ribu baris ini jadi lambat/berat tanpa peringatan apa pun ke user.
     * Kalau rentang yang diminta lebih lebar, bulan_dari otomatis dimajukan (bulan_sampai tetap)
     * supaya total tetap 60 bulan, dan flag 'dipangkas' dikembalikan untuk ditampilkan ke user.
     */
    private function resolvePeriode(): array
    {
        $tahun = $this->request->getGet('tahun');
        $bulan = $this->request->getGet('bulan');
        if (!empty($tahun)) {
            if (!empty($bulan)) {
                $bStr = str_pad((string)(int)$bulan, 2, '0', STR_PAD_LEFT);
                $bulanDari   = "{$tahun}-{$bStr}";
                $bulanSampai = "{$tahun}-{$bStr}";
            } else {
                $bulanDari   = "{$tahun}-01";
                $bulanSampai = "{$tahun}-12";
            }
        } else {
            $bulanDari   = $this->request->getGet('bulan_dari') ?? date('Y-m', strtotime('-5 months'));
            $bulanSampai = $this->request->getGet('bulan_sampai') ?? date('Y-m');
        }

        // Validasi format dasar (YYYY-MM) — GET param bisa apa saja dari luar.
        if (!preg_match('/^\d{4}-\d{2}$/', $bulanDari))   $bulanDari   = date('Y-m', strtotime('-5 months'));
        if (!preg_match('/^\d{4}-\d{2}$/', $bulanSampai)) $bulanSampai = date('Y-m');
        if ($bulanDari > $bulanSampai) [$bulanDari, $bulanSampai] = [$bulanSampai, $bulanDari];

        $maxBulan = 132; // hingga 11 tahun (menjangkau data histori sejak 2016)
        $selisih  = (new \DateTime($bulanSampai . '-01'))->diff(new \DateTime($bulanDari . '-01'))->m
                  + ((new \DateTime($bulanSampai . '-01'))->diff(new \DateTime($bulanDari . '-01'))->y * 12);
        $dipangkas = false;
        if ($selisih >= $maxBulan) {
            $bulanDari = date('Y-m', strtotime($bulanSampai . '-01 -' . ($maxBulan - 1) . ' months'));
            $dipangkas = true;
        }

        return [$bulanDari, $bulanSampai, $dipangkas, $maxBulan];
    }

    /**
     * Rincian opsional Perjalanan Dinas & Dana Taktis untuk periode laporan — dihitung terpisah
     * dari hitungLaporan() dan hanya dipanggil kalau salah satu checkbox "sertakan" dicentang,
     * supaya laporan dasar (pemasukan/pengeluaran) tidak ikut kena biaya query tambahan saat
     * keduanya tidak dipakai. Ini murni bagian INFORMASIONAL tambahan — tidak digabung ke
     * totalPemasukan/totalPengeluaran/saldo, karena setoran Dana Taktis yang sudah lunas SUDAH
     * ikut terhitung di sana lewat kategori "Setoran Taktis Pegawai" (lihat tandaiLunas() di
     * PerjalananDinasPesertaModel) — menjumlahkannya lagi di sini akan dobel hitung.
     */
    private function hitungPerjadinDanaTaktis(string $bulanDari, string $bulanSampai, string $filterNamaTaktis = ''): array
    {
        [$tahunSampai, $blnSampai] = explode('-', $bulanSampai);
        $tglAkhir = $bulanSampai . '-' . cal_days_in_month(CAL_GREGORIAN, $blnSampai, $tahunSampai);
        $tglMulai = $bulanDari . '-01';

        $rows = (new PerjalananDinasPesertaModel())->getForLaporan($tglMulai, $tglAkhir);

        $filterStatus = trim((string) $this->request->getGet('status'));
        if ($filterStatus === 'lunas' || $filterStatus === 'belum') {
            $rows = array_values(array_filter($rows, fn($r) => ($r['status_lunas'] ?? '') === $filterStatus));
        }

        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('filter_search')));
        if ($search !== '') {
            $rows = array_values(array_filter($rows, function($r) use ($search) {
                return stripos($r['nama_peserta'] ?? '', $search) !== false
                    || stripos($r['maksud'] ?? '', $search) !== false
                    || stripos($r['no_surat_tugas'] ?? '', $search) !== false
                    || stripos($r['kode_mak'] ?? '', $search) !== false
                    || stripos($r['no_spm'] ?? '', $search) !== false;
            }));
        }

        $trips = [];
        foreach ($rows as $r) {
            $tid = $r['perjalanan_dinas_id'];
            if (!isset($trips[$tid])) {
                $trips[$tid] = [
                    'no_surat_tugas'      => $r['no_surat_tugas'],
                    'maksud'              => $r['maksud'],
                    'tanggal_surat_tugas' => $r['tanggal_surat_tugas'],
                    'kode_mak'            => $r['kode_mak'],
                    'jumlah_peserta'      => 0,
                    'total_spj'           => 0.0,
                    'total_dana_taktis'   => 0.0,
                ];
            }
            $trips[$tid]['jumlah_peserta']++;
            $trips[$tid]['total_spj']         += (float) $r['total_spj'];
            $trips[$tid]['total_dana_taktis'] += (float) $r['dana_taktis'];
        }

        $danaTaktisRows = array_values(array_filter($rows, fn($r) => (float) $r['dana_taktis'] > 0));

        $filterNamaTaktis = trim($filterNamaTaktis);
        if ($filterNamaTaktis !== '') {
            $danaTaktisRows = array_values(array_filter(
                $danaTaktisRows,
                fn($r) => stripos($r['nama_peserta'], $filterNamaTaktis) !== false
            ));
        }

        // Ringkasan dengan rumus yang sama seperti summary bar Dana Taktis di web
        // (lihat dtAdmUpdateSummary() di tab_dana_taktis.php) supaya angkanya konsisten
        // di layar, PDF, dan Excel.
        $danaTaktisTotalUangHarian = 0.0;
        $danaTaktisTotalSpj        = 0.0;
        $danaTaktisTotalTaktis     = 0.0;
        $danaTaktisTotalBelumSetor = 0.0;
        foreach ($danaTaktisRows as $r) {
            $danaTaktisTotalUangHarian += (float) ($r['uang_harian'] ?? 0);
            $danaTaktisTotalSpj        += (float) ($r['total_spj'] ?? 0);
            $danaTaktisTotalTaktis     += (float) $r['dana_taktis'];
            if ($r['status_lunas'] !== 'lunas') {
                $danaTaktisTotalBelumSetor += (float) $r['dana_taktis'] - (float) ($r['jumlah_disetor'] ?? 0);
            }
        }

        return [
            'perjadinTrips'    => array_values($trips),
            'perjadinRows'     => $rows,
            'perjadinTotalSpj' => array_sum(array_column($trips, 'total_spj')),

            'danaTaktisRows'             => $danaTaktisRows,
            'filterNamaTaktis'           => $filterNamaTaktis,
            'danaTaktisTotalUangHarian'  => $danaTaktisTotalUangHarian,
            'danaTaktisTotalSpj'         => $danaTaktisTotalSpj,
            'danaTaktisTotalTaktis'      => $danaTaktisTotalTaktis,
            'danaTaktisTotalBelumSetor'  => $danaTaktisTotalBelumSetor,
        ];
    }

    public function index(): string
    {
        $isSuperAdmin = session()->get('admin_role') === 'super_admin';
        [$bulanDari, $bulanSampai, $dipangkas, $maxBulan] = $this->resolvePeriode();

        if (!$isSuperAdmin) {
            $sertakanPerjadin   = $this->request->getGet('sertakan_perjadin') !== null ? (bool) $this->request->getGet('sertakan_perjadin') : true;
            $sertakanDanaTaktis = $this->request->getGet('sertakan_dana_taktis') !== null ? (bool) $this->request->getGet('sertakan_dana_taktis') : true;
            if (!$sertakanPerjadin && !$sertakanDanaTaktis) {
                $sertakanPerjadin   = true;
                $sertakanDanaTaktis = true;
            }
        } else {
            $sertakanPerjadin   = (bool) $this->request->getGet('sertakan_perjadin');
            $sertakanDanaTaktis = (bool) $this->request->getGet('sertakan_dana_taktis');
        }
        $filterNamaTaktis   = trim((string) ($this->request->getGet('filter_nama_taktis') ?? $this->request->getGet('search')));

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        $data['isSuperAdmin']       = $isSuperAdmin;
        $data['bulanDari']          = $bulanDari;
        $data['bulanSampai']        = $bulanSampai;
        $data['periodeDipangkas']   = $dipangkas;
        $data['maxBulanPeriode']    = $maxBulan;
        $data['sertakanPerjadin']   = $sertakanPerjadin;
        $data['sertakanDanaTaktis'] = $sertakanDanaTaktis;
        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai, $filterNamaTaktis));
        }

        return view('admin/laporan', $data);
    }

    public function exportPdf()
    {
        // Gunakan dompdf jika tersedia, fallback ke print CSS
        $isSuperAdmin = session()->get('admin_role') === 'super_admin';
        [$bulanDari, $bulanSampai, $dipangkas, $maxBulan] = $this->resolvePeriode();
        $tipe               = trim((string) $this->request->getGet('tipe'));
        $search             = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('filter_nama_taktis') ?? ''));

        if (!$isSuperAdmin) {
            $sertakanPerjadin   = $tipe === 'perjadin' || ($this->request->getGet('sertakan_perjadin') !== null ? (bool) $this->request->getGet('sertakan_perjadin') : true);
            $sertakanDanaTaktis = $tipe === 'dana_taktis' || ($this->request->getGet('sertakan_dana_taktis') !== null ? (bool) $this->request->getGet('sertakan_dana_taktis') : true);
            if ($tipe === 'perjadin') $sertakanDanaTaktis = false;
            if ($tipe === 'dana_taktis') $sertakanPerjadin = false;
            if (!$sertakanPerjadin && !$sertakanDanaTaktis) {
                $sertakanPerjadin   = true;
                $sertakanDanaTaktis = true;
            }
            $hanyaPerjadin   = ($tipe === 'perjadin' || ($sertakanPerjadin && !$sertakanDanaTaktis));
            $hanyaDanaTaktis = ($tipe === 'dana_taktis' || (!$sertakanPerjadin && $sertakanDanaTaktis));
        } else {
            $sertakanPerjadin   = $tipe === 'perjadin' || (bool) $this->request->getGet('sertakan_perjadin');
            $sertakanDanaTaktis = $tipe === 'dana_taktis' || (bool) $this->request->getGet('sertakan_dana_taktis');
            $hanyaPerjadin      = ($tipe === 'perjadin');
            $hanyaDanaTaktis    = ($tipe === 'dana_taktis');
        }

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        $data['isSuperAdmin']       = $isSuperAdmin;
        $data['bulanDari']          = $bulanDari;
        $data['bulanSampai']        = $bulanSampai;
        $data['periodeDipangkas']   = $dipangkas;
        $data['maxBulanPeriode']    = $maxBulan;
        $data['sertakanPerjadin']   = $sertakanPerjadin;
        $data['sertakanDanaTaktis'] = $sertakanDanaTaktis;
        $data['hanyaPerjadin']      = $hanyaPerjadin;
        $data['hanyaDanaTaktis']    = $hanyaDanaTaktis;

        $filterParts = [];
        if ($search !== '') $filterParts[] = 'Pencarian: "' . $search . '"';
        $statusGet = trim((string) $this->request->getGet('status'));
        if ($statusGet !== '') $filterParts[] = 'Status: ' . ucfirst($statusGet);
        $data['filterInfo'] = implode(', ', $filterParts);

        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai, $search));
        }

        $html = view('admin/laporan_pdf', $data);

        $pdfFilename = 'laporan_keuangan_' . $bulanDari . '_' . $bulanSampai . '.pdf';
        if ($hanyaPerjadin) {
            $pdfFilename = 'laporan_perjalanan_dinas_' . $bulanDari . '_' . $bulanSampai . '.pdf';
        } elseif ($hanyaDanaTaktis) {
            $pdfFilename = 'laporan_dana_taktis_' . $bulanDari . '_' . $bulanSampai . '.pdf';
        } elseif (!$isSuperAdmin) {
            $pdfFilename = 'laporan_perjadin_dana_taktis_' . $bulanDari . '_' . $bulanSampai . '.pdf';
        }

        // Jika dompdf tersedia
        if (class_exists('Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', ($sertakanPerjadin || $hanyaPerjadin) ? 'landscape' : 'portrait');
            $dompdf->render();
            $dompdf->stream($pdfFilename, ['Attachment' => true]);
            exit;
        }

        // Fallback: tampilkan HTML untuk di-print
        return $this->response->setBody($html);
    }

    public function exportExcel()
    {
        $isSuperAdmin = session()->get('admin_role') === 'super_admin';
        [$bulanDari, $bulanSampai] = $this->resolvePeriode();
        $tipe               = trim((string) $this->request->getGet('tipe'));
        $search             = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('filter_nama_taktis') ?? ''));

        if (!$isSuperAdmin) {
            $sertakanPerjadin   = $tipe === 'perjadin' || ($this->request->getGet('sertakan_perjadin') !== null ? (bool) $this->request->getGet('sertakan_perjadin') : true);
            $sertakanDanaTaktis = $tipe === 'dana_taktis' || ($this->request->getGet('sertakan_dana_taktis') !== null ? (bool) $this->request->getGet('sertakan_dana_taktis') : true);
            if ($tipe === 'perjadin') $sertakanDanaTaktis = false;
            if ($tipe === 'dana_taktis') $sertakanPerjadin = false;
            if (!$sertakanPerjadin && !$sertakanDanaTaktis) {
                $sertakanPerjadin   = true;
                $sertakanDanaTaktis = true;
            }
        } else {
            $sertakanPerjadin   = $tipe === 'perjadin' || (bool) $this->request->getGet('sertakan_perjadin');
            $sertakanDanaTaktis = $tipe === 'dana_taktis' || (bool) $this->request->getGet('sertakan_dana_taktis');
        }

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai, $search));
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Keuangan Internal BBPOM di Pangkal Pinang')
            ->setTitle('Laporan ' . $bulanDari . ' s.d ' . $bulanSampai);

        if (!$isSuperAdmin) {
            if ($tipe === 'perjadin' || ($sertakanPerjadin && !$sertakanDanaTaktis)) {
                $sheetPerjadin = $spreadsheet->getActiveSheet();
                $this->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
                $filename = 'laporan_perjalanan_dinas_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
            } elseif ($tipe === 'dana_taktis' || (!$sertakanPerjadin && $sertakanDanaTaktis)) {
                $sheetDanaTaktis = $spreadsheet->getActiveSheet();
                $this->buildSheetDanaTaktis(
                    $sheetDanaTaktis,
                    $data['danaTaktisRows'] ?? [],
                    $data['filterNamaTaktis'] ?? '',
                    $data['danaTaktisTotalUangHarian'] ?? 0.0,
                    $data['danaTaktisTotalSpj'] ?? 0.0,
                    $data['danaTaktisTotalTaktis'] ?? 0.0,
                    $data['danaTaktisTotalBelumSetor'] ?? 0.0
                );
                $filename = 'laporan_dana_taktis_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
            } else {
                $sheetPerjadin = $spreadsheet->getActiveSheet();
                $this->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);

                $sheetDanaTaktis = $spreadsheet->createSheet();
                $this->buildSheetDanaTaktis(
                    $sheetDanaTaktis,
                    $data['danaTaktisRows'] ?? [],
                    $data['filterNamaTaktis'] ?? '',
                    $data['danaTaktisTotalUangHarian'] ?? 0.0,
                    $data['danaTaktisTotalSpj'] ?? 0.0,
                    $data['danaTaktisTotalTaktis'] ?? 0.0,
                    $data['danaTaktisTotalBelumSetor'] ?? 0.0
                );

                $spreadsheet->setActiveSheetIndex(0);
                $filename = 'laporan_perjadin_dana_taktis_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
            }
        } elseif ($tipe === 'perjadin') {
            $sheetPerjadin = $spreadsheet->getActiveSheet();
            $this->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
            $filename = 'laporan_perjalanan_dinas_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
        } elseif ($tipe === 'dana_taktis') {
            $sheetDanaTaktis = $spreadsheet->getActiveSheet();
            $this->buildSheetDanaTaktis(
                $sheetDanaTaktis,
                $data['danaTaktisRows'] ?? [],
                $data['filterNamaTaktis'] ?? '',
                $data['danaTaktisTotalUangHarian'] ?? 0.0,
                $data['danaTaktisTotalSpj'] ?? 0.0,
                $data['danaTaktisTotalTaktis'] ?? 0.0,
                $data['danaTaktisTotalBelumSetor'] ?? 0.0
            );
            $filename = 'laporan_dana_taktis_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
        } else {
            $this->buildSheetDashboard(
                $spreadsheet->getActiveSheet(),
                $bulanDari,
                $bulanSampai,
                $data['totalPemasukan'],
                $data['totalPengeluaran'],
                $data['saldoAwal'],
                $data['saldoAkhir'],
                $data['rasio'],
                $data['kategoriPemasukan'],
                $data['kategoriPengeluaran']
            );

            $sheetBulanan = $spreadsheet->createSheet();
            $this->buildSheetRincianBulanan($sheetBulanan, $data['rincianBulanan'], $data['totalPemasukan'], $data['totalPengeluaran']);

            $sheetPemasukan = $spreadsheet->createSheet();
            $this->buildSheetTransaksi($sheetPemasukan, 'Rincian Pemasukan', $data['pemasukans'], 'pemasukan', $data['totalPemasukan']);

            $sheetPengeluaran = $spreadsheet->createSheet();
            $this->buildSheetTransaksi($sheetPengeluaran, 'Rincian Pengeluaran', $data['pengeluarans'], 'pengeluaran', $data['totalPengeluaran']);

            $sheetGabungan = $spreadsheet->createSheet();
            $this->buildSheetGabungan($sheetGabungan, $data['gabungan']);

            if ($sertakanPerjadin) {
                $sheetPerjadin = $spreadsheet->createSheet();
                $this->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
            }

            if ($sertakanDanaTaktis) {
                $sheetDanaTaktis = $spreadsheet->createSheet();
                $this->buildSheetDanaTaktis(
                    $sheetDanaTaktis,
                    $data['danaTaktisRows'],
                    $data['filterNamaTaktis'],
                    $data['danaTaktisTotalUangHarian'],
                    $data['danaTaktisTotalSpj'],
                    $data['danaTaktisTotalTaktis'],
                    $data['danaTaktisTotalBelumSetor']
                );
            }

            $spreadsheet->setActiveSheetIndex(0);
            $filename = 'laporan_keuangan_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
        }
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        // Wajib di-set true, kalau tidak grafik yang sudah ditambahkan via addChart() tidak akan
        // ikut ditulis ke file output sama sekali (defaultnya false di PhpSpreadsheet).
        $writer->setIncludeCharts(true);

        $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setHeader('Cache-Control', 'max-age=0');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response->setBody($content);
    }

    /** Sheet 1: ringkasan ala dashboard (kartu ringkasan, breakdown kategori, grafik batang). */
    private function buildSheetDashboard(
        $sheet,
        string $bulanDari,
        string $bulanSampai,
        float $totalPemasukan,
        float $totalPengeluaran,
        float $saldoAwal,
        float $saldoAkhir,
        float $rasio,
        array $kategoriPemasukan,
        array $kategoriPengeluaran
    ) {
        $sheet->setTitle('Dashboard');
        $fill = fn($rgb) => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];

        $sheet->setCellValue('A1', 'LAPORAN KEUANGAN INTERNAL');
        $sheet->setCellValue('A2', 'Balai Besar POM di Pangkal Pinang');
        $sheet->setCellValue('A3', 'Periode: ' . date('F Y', strtotime($bulanDari . '-01')) . ' s.d ' . date('F Y', strtotime($bulanSampai . '-01')));
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '0066B2']]]);
        $sheet->getStyle('A2')->applyFromArray(['font' => ['size' => 11, 'color' => ['rgb' => '475569']]]);
        $sheet->getStyle('A3')->applyFromArray(['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']]]);

        // Kartu ringkasan periode ini
        $cards = [
            ['label' => 'Saldo Awal Periode',  'value' => $saldoAwal,        'rgb' => '64748B'],
            ['label' => 'Total Pemasukan',     'value' => $totalPemasukan,   'rgb' => '059669'],
            ['label' => 'Total Pengeluaran',   'value' => $totalPengeluaran, 'rgb' => 'DC2626'],
            ['label' => 'Saldo Akhir Periode', 'value' => $saldoAkhir,       'rgb' => '0066B2'],
        ];
        $col = 0;
        $letters = ['A', 'B', 'C', 'D'];
        foreach ($cards as $card) {
            $l = $letters[$col];
            $sheet->setCellValue("{$l}5", $card['label']);
            $sheet->setCellValue("{$l}6", (float)$card['value']);
            $sheet->getStyle("{$l}5")->applyFromArray(['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill($card['rgb']), 'alignment' => ['horizontal' => 'center']]);
            $sheet->getStyle("{$l}6")->applyFromArray(['font' => ['bold' => true, 'size' => 12], 'numberFormat' => ['formatCode' => '"Rp" #,##0'], 'alignment' => ['horizontal' => 'center']]);
            $sheet->getStyle("{$l}6")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $col++;
        }
        $sheet->setCellValue('A8', 'Rasio Belanja terhadap Pemasukan');
        $sheet->setCellValue('B8', $rasio / 100);
        $sheet->getStyle('B8')->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('A8')->applyFromArray(['font' => ['bold' => true]]);

        // Breakdown kategori pemasukan
        $r = 11;
        $sheet->setCellValue("A{$r}", 'RINCIAN PER KATEGORI — PEMASUKAN');
        $sheet->mergeCells("A{$r}:B{$r}");
        $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('059669')]);
        $r++;
        $startPemasukan = $r;
        foreach ($kategoriPemasukan as $kat => $total) {
            $sheet->setCellValue("A{$r}", $kat);
            $sheet->setCellValue("B{$r}", $total);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r++;
        }
        $endPemasukan = $r - 1;
        if (empty($kategoriPemasukan)) { $sheet->setCellValue("A{$r}", 'Tidak ada data'); $r++; }

        // Breakdown kategori pengeluaran (kolom D-E, sejajar)
        $r2 = 11;
        $sheet->setCellValue("D{$r2}", 'RINCIAN PER KATEGORI — PENGELUARAN');
        $sheet->mergeCells("D{$r2}:E{$r2}");
        $sheet->getStyle("D{$r2}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('DC2626')]);
        $r2++;
        $startPengeluaran = $r2;
        foreach ($kategoriPengeluaran as $kat => $total) {
            $sheet->setCellValue("D{$r2}", $kat);
            $sheet->setCellValue("E{$r2}", $total);
            $sheet->getStyle("E{$r2}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r2++;
        }
        $endPengeluaran = $r2 - 1;
        if (empty($kategoriPengeluaran)) { $sheet->setCellValue("D{$r2}", 'Tidak ada data'); $r2++; }

        // Grafik batang perbandingan kategori — pemasukan & pengeluaran berdampingan (native Excel chart)
        $chartRow = max($endPemasukan, $endPengeluaran) + 3;

        if (!empty($kategoriPemasukan)) {
            $dataSeriesLabels = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Dashboard!$B$' . ($startPemasukan - 1), null, 1)];
            $xAxisTickValues  = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "Dashboard!\$A\${$startPemasukan}:\$A\${$endPemasukan}", null, $endPemasukan - $startPemasukan + 1)];
            $dataSeriesValues = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "Dashboard!\$B\${$startPemasukan}:\$B\${$endPemasukan}", null, $endPemasukan - $startPemasukan + 1)];

            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);

            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $legend   = new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_BOTTOM, null, false);
            $title    = new \PhpOffice\PhpSpreadsheet\Chart\Title('Pemasukan per Kategori');

            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart('chartPemasukan', $title, $legend, $plotArea);
            $chart->setTopLeftPosition('A' . $chartRow);
            $chart->setBottomRightPosition('F' . ($chartRow + 17));
            $sheet->addChart($chart);
        }

        if (!empty($kategoriPengeluaran)) {
            $dataSeriesLabels = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Dashboard!$E$' . ($startPengeluaran - 1), null, 1)];
            $xAxisTickValues  = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "Dashboard!\$D\${$startPengeluaran}:\$D\${$endPengeluaran}", null, $endPengeluaran - $startPengeluaran + 1)];
            $dataSeriesValues = [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "Dashboard!\$E\${$startPengeluaran}:\$E\${$endPengeluaran}", null, $endPengeluaran - $startPengeluaran + 1)];

            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);

            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
            $legend   = new \PhpOffice\PhpSpreadsheet\Chart\Legend(\PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_BOTTOM, null, false);
            $title    = new \PhpOffice\PhpSpreadsheet\Chart\Title('Pengeluaran per Kategori');

            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart('chartPengeluaran', $title, $legend, $plotArea);
            $chart->setTopLeftPosition('H' . $chartRow);
            $chart->setBottomRightPosition('M' . ($chartRow + 17));
            $sheet->addChart($chart);
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $c) $sheet->getColumnDimension($c)->setWidth(24);
    }

    /** Sheet: rincian per bulan — pemasukan & pengeluaran tetap kolom terpisah, dikelompokkan per periode. */
    private function buildSheetRincianBulanan($sheet, array $rincianBulanan, float $totalPemasukan, float $totalPengeluaran)
    {
        $sheet->setTitle('Rincian Bulanan');
        $fill = fn($rgb) => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]];

        $header = ['Bulan', 'Pemasukan', 'Pengeluaran', 'Selisih'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:D1')->applyFromArray(array_merge($thinBorder, [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => $fill('0066B2'),
            'alignment' => ['horizontal' => 'center'],
        ]));

        $r = 2;
        foreach ($rincianBulanan as $ym => $b) {
            $selisih = $b['pemasukan'] - $b['pengeluaran'];
            $sheet->setCellValue("A{$r}", date('F Y', strtotime($ym . '-01')));
            $sheet->setCellValue("B{$r}", $b['pemasukan']);
            $sheet->setCellValue("C{$r}", $b['pengeluaran']);
            $sheet->setCellValue("D{$r}", $selisih);
            foreach (['B', 'C', 'D'] as $c) $sheet->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("A{$r}:D{$r}")->applyFromArray($thinBorder);
            $r++;
        }

        if (empty($rincianBulanan)) {
            $sheet->setCellValue('A2', 'Tidak ada data pada periode ini');
            $r = 3;
        } else {
            $sheet->setCellValue("A{$r}", 'TOTAL');
            $sheet->setCellValue("B{$r}", $totalPemasukan);
            $sheet->setCellValue("C{$r}", $totalPengeluaran);
            $sheet->setCellValue("D{$r}", $totalPemasukan - $totalPengeluaran);
            foreach (['B', 'C', 'D'] as $c) $sheet->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("A{$r}:D{$r}")->applyFromArray(array_merge($thinBorder, ['font' => ['bold' => true]]));
        }

        foreach (['A', 'B', 'C', 'D'] as $c) $sheet->getColumnDimension($c)->setWidth(20);
        $sheet->freezePane('A2');
    }

    /** Sheet rincian satu jenis transaksi saja (dipakai untuk sheet Pemasukan & Pengeluaran terpisah). */
    private function buildSheetTransaksi($sheet, string $judul, array $rows, string $tipe, float $total)
    {
        $sheet->setTitle($tipe === 'pemasukan' ? 'Rincian Pemasukan' : 'Rincian Pengeluaran');
        $rgb  = $tipe === 'pemasukan' ? '059669' : 'DC2626';
        $fill = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $whiteBold = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill];
        $kolomLawan = $tipe === 'pemasukan' ? 'Sumber' : 'Tujuan';
        $fieldLawan = $tipe === 'pemasukan' ? 'sumber' : 'tujuan';
        $fieldJumlah = $tipe === 'pemasukan' ? 'jumlah_diterima' : 'jumlah';

        $sheet->setCellValue('A1', strtoupper($judul));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $rgb]]]);

        $header = ['No', 'Tanggal', 'Kategori', $kolomLawan, 'Jumlah', 'Keterangan'];
        $sheet->fromArray($header, null, 'A2');
        $sheet->getStyle('A2:F2')->applyFromArray($whiteBold);

        $r = 3;
        foreach ($rows as $i => $row) {
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $row['tanggal']);
            $sheet->setCellValue("C{$r}", $row['kategori']);
            $sheet->setCellValue("D{$r}", $row[$fieldLawan] ?? '');
            $sheet->setCellValue("E{$r}", (float)$row[$fieldJumlah]);
            $sheet->setCellValue("F{$r}", $row['keterangan']);
            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r++;
        }

        if (empty($rows)) {
            $sheet->setCellValue('A3', 'Tidak ada data pada periode ini');
            $r = 4;
        } else {
            $sheet->setCellValue("D{$r}", 'TOTAL');
            $sheet->setCellValue("E{$r}", $total);
            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("D{$r}:E{$r}")->applyFromArray(['font' => ['bold' => true], 'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]]);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (['B', 'C', 'D', 'E', 'F'] as $c) $sheet->getColumnDimension($c)->setWidth(22);
        $sheet->freezePane('A3');
    }

    /** Sheet gabungan kronologis ala buku kas umum, dengan kolom saldo berjalan. */
    private function buildSheetGabungan($sheet, array $gabungan)
    {
        $sheet->setTitle('Rincian Gabungan');
        $fill = fn($rgb) => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];

        $sheet->setCellValue('A1', 'RINCIAN GABUNGAN — BUKU KAS UMUM (KRONOLOGIS, SALDO BERJALAN)');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        $header = ['No', 'Tanggal', 'Kategori', 'Pemasukan', 'Pengeluaran', 'Saldo', 'Keterangan'];
        $sheet->fromArray($header, null, 'A2');
        $sheet->getStyle('A2:C2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('0066B2')]);
        $sheet->getStyle('D2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('059669')]);
        $sheet->getStyle('E2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('DC2626')]);
        $sheet->getStyle('F2:G2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill('0066B2')]);

        $r = 3;
        foreach ($gabungan as $i => $g) {
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $g['tanggal']);
            $sheet->setCellValue("C{$r}", $g['kategori']);
            if ($g['pemasukan'] > 0) $sheet->setCellValue("D{$r}", $g['pemasukan']);
            if ($g['pengeluaran'] > 0) $sheet->setCellValue("E{$r}", $g['pengeluaran']);
            $sheet->setCellValue("F{$r}", $g['saldo']);
            $sheet->setCellValue("G{$r}", $g['keterangan']);
            foreach (['D', 'E', 'F'] as $c) $sheet->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("F{$r}")->getFont()->setBold(true);
            $r++;
        }

        if (empty($gabungan)) {
            $sheet->setCellValue('A3', 'Tidak ada data pada periode ini');
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $c) $sheet->getColumnDimension($c)->setWidth(20);
        $sheet->freezePane('A3');
    }

    /** Sheet opsional: rincian Perjalanan Dinas lengkap persis format spreadsheet instansi tanpa ada kolom yang diringkas. */
    private function buildSheetPerjadin($sheet, array $rows, float $totalSpj)
    {
        $sheet->setTitle('Perjalanan Dinas');
        $fill = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];

        $sheet->setCellValue('A1', 'RINCIAN PERJALANAN DINAS (SPJ)');
        $sheet->mergeCells('A1:AG1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        $uniqueTrips = count(array_unique(array_filter(array_column($rows, 'perjalanan_dinas_id'))));
        $totalPeserta = count($rows);
        $totalTaktisSum = array_sum(array_column($rows, 'dana_taktis'));
        $totalBelumLunasSum = 0.0;
        foreach ($rows as $pr) {
            $status = $pr['status_lunas'] ?? 'belum';
            if ($status === 'belum') {
                $totalBelumLunasSum += (float) ($pr['dana_taktis'] ?? 0);
            } elseif ($status === 'sebagian') {
                $totalBelumLunasSum += max(0.0, (float) ($pr['dana_taktis'] ?? 0) - (float) ($pr['jumlah_disetor'] ?? 0));
            }
        }

        $ringkasan = [
            ['Total Kegiatan / ST', $uniqueTrips, '#,##0'],
            ['Total Pelaksana (Peserta)', $totalPeserta, '#,##0'],
            ['Total SPJ', $totalSpj, '"Rp" #,##0'],
            ['Total Dana Taktis', $totalTaktisSum, '"Rp" #,##0'],
            ['Dana Taktis Belum Lunas', $totalBelumLunasSum, '"Rp" #,##0'],
        ];

        $r = 3;
        foreach ($ringkasan as [$label, $nilai, $fmt]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $nilai);
            $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true]]);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($fmt);
            $r++;
        }
        $r++; // baris kosong pemisah

        $headerRow = $r;
        $header = [
            'No',
            'Maksud Perjalanan Dinas',
            'No. Surat Tugas/Tgl. Surat Tugas',
            'Kode MAK',
            'No. SPM',
            'Nama Pelaksana Perjalanan Dinas (TANPA GELAR AKADEMIK)',
            'Uang Harian',
            'Biaya Paket Meeting Fullboard',
            'Biaya Paket Meeting Fullday',
            'Uang Representasi (Eselon II)',
            'Transportasi Lokal / Transportasi Luar Kota / Taksi',
            'BBM (Jika Jalan Darat)',
            'Maskapai',
            'Pergi/Pulang',
            'No Tiket',
            'Kode Booking',
            'No Penerbangan',
            'Tempat Asal',
            'Tempat Tujuan',
            'Tanggal Terbang',
            'Harga Tiket (Rp)',
            'Nama Hotel',
            'Alamat Hotel',
            'No. Telepon Hotel',
            'Tanggal Check-In Hotel (Arrival Date)',
            'Tanggal Check-Out Hotel (Departure Date)',
            'Total Bill Hotel Yang Dibayarkan',
            'No Kamar (Room)',
            'No. Invoice Hotel',
            'Total Biaya Hotel (Jika 30%)',
            'TOTAL SPJ YANG DIBAYARKAN OLEH BENDAHARA PENGELUARAN',
            'TAKTIS',
            'LUNAS',
        ];
        $sheet->fromArray($header, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:AG{$headerRow}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill]);

        $r = $headerRow + 1;
        $nomorUrut = 0;
        $totalTaktisSum = 0.0;
        $totalSpjSum = 0.0;

        foreach ($rows as $peserta) {
            $nomorUrut++;
            $tikets = !empty($peserta['tiket']) ? $peserta['tiket'] : [];
            $hotel  = !empty($peserta['hotel']) ? $peserta['hotel'] : null;

            $totalSpjSum += (float) ($peserta['total_spj'] ?? 0);
            $totalTaktisSum += (float) ($peserta['dana_taktis'] ?? 0);

            $maxLines = max(1, count($tikets));

            for ($k = 0; $k < $maxLines; $k++) {
                $t = $tikets[$k] ?? null;

                $tglSt = !empty($peserta['tanggal_surat_tugas']) ? date('d/m/Y', strtotime($peserta['tanggal_surat_tugas'])) : '';
                $noStDanTgl = trim(($peserta['no_surat_tugas'] ?? '') . ($tglSt !== '' ? " / {$tglSt}" : ''));

                if ($k === 0) {
                    $sheet->setCellValue("A{$r}", $nomorUrut);
                    $sheet->setCellValue("B{$r}", $peserta['maksud'] ?? '');
                    $sheet->setCellValue("C{$r}", $noStDanTgl);
                    $sheet->setCellValue("D{$r}", $peserta['kode_mak'] ?? '');
                    $sheet->setCellValue("E{$r}", $peserta['no_spm'] ?? '');
                    $sheet->setCellValue("F{$r}", $peserta['nama_peserta'] ?? '');

                    $sheet->setCellValue("G{$r}", (float) ($peserta['uang_harian'] ?? 0));
                    $sheet->setCellValue("H{$r}", (float) ($peserta['meeting_fullboard'] ?? 0));
                    $sheet->setCellValue("I{$r}", (float) ($peserta['meeting_fullday'] ?? 0));
                    $sheet->setCellValue("J{$r}", (float) ($peserta['uang_representasi'] ?? 0));
                    $sheet->setCellValue("K{$r}", (float) ($peserta['transport_lokal'] ?? 0));
                    $sheet->setCellValue("L{$r}", (float) ($peserta['bbm'] ?? 0));

                    if ($hotel) {
                        $sheet->setCellValue("V{$r}", $hotel['nama_hotel'] ?? '');
                        $sheet->setCellValue("W{$r}", $hotel['alamat_hotel'] ?? '');
                        $sheet->setCellValue("X{$r}", $hotel['telp_hotel'] ?? '');
                        $sheet->setCellValue("Y{$r}", $hotel['checkin'] ?? '');
                        $sheet->setCellValue("Z{$r}", $hotel['checkout'] ?? '');
                        $sheet->setCellValue("AA{$r}", (float) ($hotel['total_bill'] ?? 0));
                        $sheet->setCellValue("AB{$r}", $hotel['no_kamar'] ?? '');
                        $sheet->setCellValue("AC{$r}", $hotel['no_invoice'] ?? '');
                        $sheet->setCellValue("AD{$r}", (float) ($hotel['total_biaya_30persen'] ?? 0));
                    }

                    $sheet->setCellValue("AE{$r}", (float) ($peserta['total_spj'] ?? 0));
                    $sheet->setCellValue("AF{$r}", (float) ($peserta['dana_taktis'] ?? 0));
                    $sheet->setCellValue("AG{$r}", ($peserta['status_lunas'] ?? '') === 'lunas' ? 'LUNAS' : 'BELUM');
                } else {
                    $sheet->setCellValue("A{$r}", '');
                    $sheet->setCellValue("B{$r}", '');
                    $sheet->setCellValue("C{$r}", '');
                    $sheet->setCellValue("D{$r}", '');
                    $sheet->setCellValue("E{$r}", '');
                    $sheet->setCellValue("F{$r}", $peserta['nama_peserta'] ?? '');
                }

                if ($t) {
                    $sheet->setCellValue("M{$r}", $t['maskapai'] ?? '');
                    $sheet->setCellValue("N{$r}", $t['arah'] ?? '');
                    $sheet->setCellValue("O{$r}", $t['no_tiket'] ?? '');
                    $sheet->setCellValue("P{$r}", $t['kode_booking'] ?? '');
                    $sheet->setCellValue("Q{$r}", $t['no_penerbangan'] ?? '');
                    $sheet->setCellValue("R{$r}", $t['tempat_asal'] ?? '');
                    $sheet->setCellValue("S{$r}", $t['tempat_tujuan'] ?? '');
                    $sheet->setCellValue("T{$r}", $t['tanggal_terbang'] ?? '');
                    $sheet->setCellValue("U{$r}", (float) ($t['harga_tiket'] ?? 0));
                }

                foreach (['G', 'H', 'I', 'J', 'K', 'L', 'U', 'AA', 'AD', 'AE', 'AF'] as $col) {
                    $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
                }

                $r++;
            }
        }

        if (empty($rows)) {
            $sheet->setCellValue("A{$r}", 'Tidak ada data pada periode ini');
            $r++;
        } else {
            $sheet->setCellValue("F{$r}", 'TOTAL');
            $sheet->setCellValue("AE{$r}", $totalSpjSum);
            $sheet->setCellValue("AF{$r}", $totalTaktisSum);
            $sheet->getStyle("AE{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("AF{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("F{$r}:AG{$r}")->applyFromArray([
                'font' => ['bold' => true],
                'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(25);
        foreach (['G', 'H', 'I', 'J', 'K', 'L'] as $c) $sheet->getColumnDimension($c)->setWidth(18);
        foreach (['M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'] as $c) $sheet->getColumnDimension($c)->setWidth(16);
        foreach (['V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD'] as $c) $sheet->getColumnDimension($c)->setWidth(16);
        $sheet->getColumnDimension('AE')->setWidth(22);
        $sheet->getColumnDimension('AF')->setWidth(16);
        $sheet->getColumnDimension('AG')->setWidth(14);
        $sheet->freezePane('A' . ($headerRow + 1));
    }

    /** Sheet opsional: rincian setoran Dana Taktis per peserta pada periode ini.
     * Data diurutkan: Belum Lunas dahulu (urut nama), kemudian Lunas (urut nama).
     * Dilengkapi sub-total per nama pegawai.
     */
    private function buildSheetDanaTaktis(
        $sheet,
        array $rows,
        string $filterNamaTaktis,
        float $totalUangHarian,
        float $totalSpj,
        float $totalTaktis,
        float $totalBelumSetor
    ) {
        $sheet->setTitle('Dana Taktis');
        $fillBlue  = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];
        $fillAmber = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']];
        $fillGreen = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']];
        $fillGray  = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']];
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]];

        $judul = 'RINCIAN DANA TAKTIS — Diurutkan: Belum Lunas → Lunas (Urut Nama)';
        if ($filterNamaTaktis !== '') {
            $judul .= ' | Filter Nama: ' . $filterNamaTaktis;
        }
        $sheet->setCellValue('A1', $judul);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        // --- Summary sesuai filter ---
        $ringkasan = [
            ['Total Uang Harian', $totalUangHarian, '"Rp" #,##0'],
            ['Total SPJ',         $totalSpj,         '"Rp" #,##0'],
            ['Dana Taktis (Total)', $totalTaktis,    '"Rp" #,##0'],
            ['Belum Disetor',    $totalBelumSetor,   '"Rp" #,##0'],
        ];
        $r = 3;
        foreach ($ringkasan as [$label, $nilai, $fmt]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $nilai);
            $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true]]);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($fmt);
            $r++;
        }
        $r++; // baris kosong pemisah

        // --- Header tabel ---
        $headerRow = $r;
        $header = ['No', 'Tanggal ST', 'Nama Peserta', 'Maksud Perjalanan', 'Dana Taktis', 'Sudah Disetor', 'Status'];
        $sheet->fromArray($header, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray(array_merge($thinBorder, [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => $fillBlue,
            'alignment' => ['horizontal' => 'center', 'wrapText' => true],
        ]));
        $r++;

        if (empty($rows)) {
            $sheet->setCellValue("A{$r}", 'Tidak ada data pada periode' . ($filterNamaTaktis !== '' ? ' / filter nama' : '') . ' ini');
        } else {
            // Urutkan: Belum Lunas/Sebagian dulu (abjad nama), kemudian Lunas (abjad nama)
            usort($rows, function ($a, $b) {
                $aStatus = ($a['status_lunas'] ?? 'belum') === 'lunas' ? 1 : 0;
                $bStatus = ($b['status_lunas'] ?? 'belum') === 'lunas' ? 1 : 0;
                if ($aStatus !== $bStatus) return $aStatus - $bStatus; // belum dulu
                return strcmp($a['nama_peserta'] ?? '', $b['nama_peserta'] ?? '');
            });

            // Kelompokkan per nama untuk sub-total
            $grouped = [];
            foreach ($rows as $row) {
                $nama = $row['nama_peserta'] ?? 'Tidak Diketahui';
                $grouped[$nama][] = $row;
            }

            $no = 1;
            foreach ($grouped as $nama => $namaRows) {
                // Sub-total per nama
                $subTaktis   = array_sum(array_column($namaRows, 'dana_taktis'));
                $subDisetor  = array_sum(array_column($namaRows, 'jumlah_disetor'));
                $subBelum    = 0.0;
                foreach ($namaRows as $nr) {
                    $st = $nr['status_lunas'] ?? 'belum';
                    if ($st === 'belum')    $subBelum += (float) $nr['dana_taktis'];
                    elseif ($st === 'sebagian') $subBelum += max(0.0, (float) $nr['dana_taktis'] - (float) ($nr['jumlah_disetor'] ?? 0));
                }
                $semuaLunas = $subBelum <= 0;

                foreach ($namaRows as $row) {
                    $status    = $row['status_lunas'] ?? 'belum';
                    $isLunas   = $status === 'lunas';
                    $rowFill   = $isLunas ? $fillGreen : $fillAmber;
                    $statusTxt = $isLunas ? 'Lunas' : ($status === 'sebagian' ? 'Sebagian' : 'Belum Lunas');
                    $disetor   = (float) ($row['jumlah_disetor'] ?? 0);
                    if ($isLunas) $disetor = (float) $row['dana_taktis']; // lunas penuh

                    $sheet->setCellValue("A{$r}", $no++);
                    $sheet->setCellValue("B{$r}", $row['tanggal_surat_tugas']);
                    $sheet->setCellValue("C{$r}", $row['nama_peserta']);
                    $sheet->setCellValue("D{$r}", $row['maksud']);
                    $sheet->setCellValue("E{$r}", (float) $row['dana_taktis']);
                    $sheet->setCellValue("F{$r}", $disetor);
                    $sheet->setCellValue("G{$r}", $statusTxt);
                    $sheet->getStyle("E{$r}:F{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray(array_merge($thinBorder, ['fill' => $rowFill]));
                    $r++;
                }

                // Baris sub-total per nama
                $sheet->setCellValue("C{$r}", '↳ Subtotal: ' . $nama);
                $sheet->setCellValue("E{$r}", $subTaktis);
                $sheet->setCellValue("F{$r}", $subDisetor);
                $sheet->setCellValue("G{$r}", $semuaLunas ? 'LUNAS SEMUA' : ('Sisa: Rp ' . number_format($subBelum, 0, ',', '.')));
                $sheet->getStyle("A{$r}:G{$r}")->applyFromArray(array_merge($thinBorder, [
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => $fillGray,
                ]));
                foreach (['E', 'F'] as $col) $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
                $r++;

                // Baris pemisah kosong antar nama
                $r++;
            }
        }

        // Lebar kolom
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->freezePane('A' . ($headerRow + 1));
    }
}
