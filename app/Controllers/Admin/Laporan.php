<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Libraries\LaporanExcelExporter;

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
    private function hitungPerjadinDanaTaktis(string $bulanDari, string $bulanSampai, string $filterNamaTaktis = '', bool $withDetails = false): array
    {
        [$tahunSampai, $blnSampai] = explode('-', $bulanSampai);
        $tglAkhir = $bulanSampai . '-' . cal_days_in_month(CAL_GREGORIAN, $blnSampai, $tahunSampai);
        $tglMulai = $bulanDari . '-01';

        $rows = (new PerjalananDinasPesertaModel())->getForLaporan($tglMulai, $tglAkhir, $withDetails);

        $filterStatus = trim((string) $this->request->getGet('status'));
        if ($filterStatus === 'lunas' || $filterStatus === 'belum') {
            $rows = array_values(array_filter($rows, fn($r) => ($r['status_lunas'] ?? '') === $filterStatus));
        }

        // Filter nama pegawai / dana taktis
        $filterNamaTaktis = trim((string) ($filterNamaTaktis !== '' ? $filterNamaTaktis : ($this->request->getGet('filter_nama_taktis') ?: ($this->request->getGet('search') ?: ''))));
        if ($filterNamaTaktis !== '') {
            $rows = array_values(array_filter($rows, function($r) use ($filterNamaTaktis) {
                return stripos($r['nama_peserta'] ?? '', $filterNamaTaktis) !== false;
            }));
        }

        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('filter_search') ?? ''));
        if ($search !== '' && $search !== $filterNamaTaktis) {
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

        $cache = service('cache');
        $cacheKey = 'lap_adm_' . md5("{$bulanDari}_{$bulanSampai}_{$sertakanPerjadin}_{$sertakanDanaTaktis}_{$filterNamaTaktis}");
        $cachedData = $cache->get($cacheKey);

        if (is_array($cachedData)) {
            $data = $cachedData;
        } else {
            $data = $this->hitungLaporan($bulanDari, $bulanSampai);
            if ($sertakanPerjadin || $sertakanDanaTaktis) {
                $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai, $filterNamaTaktis, false));
            }
            $cache->save($cacheKey, $data, 300);
        }

        $data['isSuperAdmin']       = $isSuperAdmin;
        $data['bulanDari']          = $bulanDari;
        $data['bulanSampai']        = $bulanSampai;
        $data['periodeDipangkas']   = $dipangkas;
        $data['maxBulanPeriode']    = $maxBulan;
        $data['sertakanPerjadin']   = $sertakanPerjadin;
        $data['sertakanDanaTaktis'] = $sertakanDanaTaktis;

        return view('admin/laporan', $data);
    }

    public function exportPdf()
    {
        // Gunakan dompdf jika tersedia, fallback ke print CSS
        $isSuperAdmin = session()->get('admin_role') === 'super_admin';
        [$bulanDari, $bulanSampai, $dipangkas, $maxBulan] = $this->resolvePeriode();
        $tipe               = trim((string) $this->request->getGet('tipe'));
        $search             = trim((string) ($this->request->getGet('filter_nama_taktis') ?: ($this->request->getGet('search') ?: '')));

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
        if ($search !== '') $filterParts[] = 'Nama/Pencarian: "' . $search . '"';
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
        $search             = trim((string) ($this->request->getGet('filter_nama_taktis') ?: ($this->request->getGet('search') ?: '')));

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
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai, $search, true));
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Keuangan Internal BBPOM di Pangkal Pinang')
            ->setTitle('Laporan ' . $bulanDari . ' s.d ' . $bulanSampai);

        $exporter = new LaporanExcelExporter();

        if (!$isSuperAdmin) {
            if ($tipe === 'perjadin' || ($sertakanPerjadin && !$sertakanDanaTaktis)) {
                $sheetPerjadin = $spreadsheet->getActiveSheet();
                $exporter->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
                $filename = 'laporan_perjalanan_dinas_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
            } elseif ($tipe === 'dana_taktis' || (!$sertakanPerjadin && $sertakanDanaTaktis)) {
                $sheetDanaTaktis = $spreadsheet->getActiveSheet();
                $exporter->buildSheetDanaTaktis(
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
                $exporter->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);

                $sheetDanaTaktis = $spreadsheet->createSheet();
                $exporter->buildSheetDanaTaktis(
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
            $exporter->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
            $filename = 'laporan_perjalanan_dinas_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
        } elseif ($tipe === 'dana_taktis') {
            $sheetDanaTaktis = $spreadsheet->getActiveSheet();
            $exporter->buildSheetDanaTaktis(
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
            $exporter->buildSheetDashboard(
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
            $exporter->buildSheetRincianBulanan($sheetBulanan, $data['rincianBulanan'], $data['totalPemasukan'], $data['totalPengeluaran']);

            $sheetPemasukan = $spreadsheet->createSheet();
            $exporter->buildSheetTransaksi($sheetPemasukan, 'Rincian Pemasukan', $data['pemasukans'], 'pemasukan', $data['totalPemasukan']);

            $sheetPengeluaran = $spreadsheet->createSheet();
            $exporter->buildSheetTransaksi($sheetPengeluaran, 'Rincian Pengeluaran', $data['pengeluarans'], 'pengeluaran', $data['totalPengeluaran']);

            $sheetGabungan = $spreadsheet->createSheet();
            $exporter->buildSheetGabungan($sheetGabungan, $data['gabungan']);

            if ($sertakanPerjadin) {
                $sheetPerjadin = $spreadsheet->createSheet();
                $exporter->buildSheetPerjadin($sheetPerjadin, $data['perjadinRows'] ?? [], $data['perjadinTotalSpj'] ?? 0.0);
            }

            if ($sertakanDanaTaktis) {
                $sheetDanaTaktis = $spreadsheet->createSheet();
                $exporter->buildSheetDanaTaktis(
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

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
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
}
