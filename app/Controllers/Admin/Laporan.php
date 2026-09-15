<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\PengaturanModel;
use App\Models\NotifikasiModel;
use App\Models\PerjalananDinasPesertaModel;

class Laporan extends BaseController
{
    protected $pemasukanModel;
    protected $pengeluaranModel;
    protected $pengaturanModel;
    protected $notifikasiModel;

    public function __construct()
    {
        $this->pemasukanModel  = new PemasukanModel();
        $this->pengeluaranModel = new PengeluaranModel();
        $this->pengaturanModel = new PengaturanModel();
        $this->notifikasiModel = new NotifikasiModel();
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
        $bulanDari   = $this->request->getGet('bulan_dari') ?? date('Y-m', strtotime('-5 months'));
        $bulanSampai = $this->request->getGet('bulan_sampai') ?? date('Y-m');

        // Validasi format dasar (YYYY-MM) — GET param bisa apa saja dari luar.
        if (!preg_match('/^\d{4}-\d{2}$/', $bulanDari))   $bulanDari   = date('Y-m', strtotime('-5 months'));
        if (!preg_match('/^\d{4}-\d{2}$/', $bulanSampai)) $bulanSampai = date('Y-m');
        if ($bulanDari > $bulanSampai) [$bulanDari, $bulanSampai] = [$bulanSampai, $bulanDari];

        $maxBulan = 60;
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
    private function hitungPerjadinDanaTaktis(string $bulanDari, string $bulanSampai): array
    {
        [$tahunSampai, $blnSampai] = explode('-', $bulanSampai);
        $tglAkhir = $bulanSampai . '-' . cal_days_in_month(CAL_GREGORIAN, $blnSampai, $tahunSampai);
        $tglMulai = $bulanDari . '-01';

        $rows = (new PerjalananDinasPesertaModel())->getForLaporan($tglMulai, $tglAkhir);

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

        return [
            'perjadinTrips'        => array_values($trips),
            'perjadinTotalSpj'     => array_sum(array_column($trips, 'total_spj')),
            'danaTaktisRows'       => $danaTaktisRows,
            'danaTaktisTotalLunas' => array_sum(array_map(fn($r) => (float) $r['dana_taktis'],
                array_filter($danaTaktisRows, fn($r) => $r['status_lunas'] === 'lunas'))),
            'danaTaktisTotalBelum' => array_sum(array_map(fn($r) => (float) $r['dana_taktis'],
                array_filter($danaTaktisRows, fn($r) => $r['status_lunas'] === 'belum'))),
        ];
    }

    public function index(): string
    {
        [$bulanDari, $bulanSampai, $dipangkas, $maxBulan] = $this->resolvePeriode();
        $sertakanPerjadin   = (bool) $this->request->getGet('sertakan_perjadin');
        $sertakanDanaTaktis = (bool) $this->request->getGet('sertakan_dana_taktis');

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        $data['bulanDari']   = $bulanDari;
        $data['bulanSampai'] = $bulanSampai;
        $data['periodeDipangkas'] = $dipangkas;
        $data['maxBulanPeriode']  = $maxBulan;
        $data['notifCount']  = $this->notifikasiModel->countUnread();
        $data['sertakanPerjadin']   = $sertakanPerjadin;
        $data['sertakanDanaTaktis'] = $sertakanDanaTaktis;
        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai));
        }

        return view('admin/laporan', $data);
    }

    public function exportPdf()
    {
        // Gunakan dompdf jika tersedia, fallback ke print CSS
        [$bulanDari, $bulanSampai, $dipangkas, $maxBulan] = $this->resolvePeriode();
        $sertakanPerjadin   = (bool) $this->request->getGet('sertakan_perjadin');
        $sertakanDanaTaktis = (bool) $this->request->getGet('sertakan_dana_taktis');

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        $data['bulanDari']   = $bulanDari;
        $data['bulanSampai'] = $bulanSampai;
        $data['periodeDipangkas'] = $dipangkas;
        $data['maxBulanPeriode']  = $maxBulan;
        $data['sertakanPerjadin']   = $sertakanPerjadin;
        $data['sertakanDanaTaktis'] = $sertakanDanaTaktis;
        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai));
        }

        $html = view('admin/laporan_pdf', $data);

        // Jika dompdf tersedia
        if (class_exists('Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('laporan_keuangan_' . $bulanDari . '_' . $bulanSampai . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Fallback: tampilkan HTML untuk di-print
        return $this->response->setBody($html);
    }

    public function exportExcel()
    {
        [$bulanDari, $bulanSampai] = $this->resolvePeriode();
        $sertakanPerjadin   = (bool) $this->request->getGet('sertakan_perjadin');
        $sertakanDanaTaktis = (bool) $this->request->getGet('sertakan_dana_taktis');

        $data = $this->hitungLaporan($bulanDari, $bulanSampai);
        if ($sertakanPerjadin || $sertakanDanaTaktis) {
            $data = array_merge($data, $this->hitungPerjadinDanaTaktis($bulanDari, $bulanSampai));
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Keuangan Internal BBPOM di Pangkal Pinang')
            ->setTitle('Laporan Keuangan ' . $bulanDari . ' s.d ' . $bulanSampai);

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
            $this->buildSheetPerjadin($sheetPerjadin, $data['perjadinTrips'], $data['perjadinTotalSpj']);
        }

        if ($sertakanDanaTaktis) {
            $sheetDanaTaktis = $spreadsheet->createSheet();
            $this->buildSheetDanaTaktis($sheetDanaTaktis, $data['danaTaktisRows'], $data['danaTaktisTotalLunas'], $data['danaTaktisTotalBelum']);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'laporan_keuangan_' . $bulanDari . '_' . $bulanSampai . '.xlsx';
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

    /** Sheet opsional: rincian trip Perjalanan Dinas pada periode ini (satu baris per trip). */
    private function buildSheetPerjadin($sheet, array $trips, float $totalSpj)
    {
        $sheet->setTitle('Perjalanan Dinas');
        $fill = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];

        $sheet->setCellValue('A1', 'RINCIAN PERJALANAN DINAS');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        $header = ['No', 'Tanggal', 'No. Surat Tugas', 'Maksud', 'Jumlah Peserta', 'Total SPJ'];
        $sheet->fromArray($header, null, 'A2');
        $sheet->getStyle('A2:F2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill]);

        $r = 3;
        foreach ($trips as $i => $t) {
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $t['tanggal_surat_tugas']);
            $sheet->setCellValue("C{$r}", $t['no_surat_tugas'] ?: '-');
            $sheet->setCellValue("D{$r}", $t['maksud']);
            $sheet->setCellValue("E{$r}", $t['jumlah_peserta']);
            $sheet->setCellValue("F{$r}", $t['total_spj']);
            $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r++;
        }

        if (empty($trips)) {
            $sheet->setCellValue('A3', 'Tidak ada data pada periode ini');
            $r = 4;
        } else {
            $sheet->setCellValue("D{$r}", 'TOTAL');
            $sheet->setCellValue("F{$r}", $totalSpj);
            $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("D{$r}:F{$r}")->applyFromArray(['font' => ['bold' => true], 'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]]);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (['B', 'C', 'D', 'E', 'F'] as $c) $sheet->getColumnDimension($c)->setWidth(22);
        $sheet->freezePane('A3');
    }

    /** Sheet opsional: rincian setoran Dana Taktis per peserta pada periode ini. */
    private function buildSheetDanaTaktis($sheet, array $rows, float $totalLunas, float $totalBelum)
    {
        $sheet->setTitle('Dana Taktis');
        $fill = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];

        $sheet->setCellValue('A1', 'RINCIAN DANA TAKTIS');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        $header = ['No', 'Tanggal', 'Nama Peserta', 'Maksud Perjalanan', 'Dana Taktis', 'Status'];
        $sheet->fromArray($header, null, 'A2');
        $sheet->getStyle('A2:F2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $fill]);

        $r = 3;
        foreach ($rows as $i => $row) {
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $row['tanggal_surat_tugas']);
            $sheet->setCellValue("C{$r}", $row['nama_peserta']);
            $sheet->setCellValue("D{$r}", $row['maksud']);
            $sheet->setCellValue("E{$r}", (float) $row['dana_taktis']);
            $sheet->setCellValue("F{$r}", $row['status_lunas'] === 'lunas' ? 'Lunas' : 'Belum Lunas');
            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r++;
        }

        if (empty($rows)) {
            $sheet->setCellValue('A3', 'Tidak ada data pada periode ini');
            $r = 4;
        } else {
            $sheet->setCellValue("D{$r}", 'TOTAL LUNAS');
            $sheet->setCellValue("E{$r}", $totalLunas);
            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("D{$r}:E{$r}")->applyFromArray(['font' => ['bold' => true]]);
            $r++;
            $sheet->setCellValue("D{$r}", 'TOTAL BELUM LUNAS');
            $sheet->setCellValue("E{$r}", $totalBelum);
            $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("D{$r}:E{$r}")->applyFromArray(['font' => ['bold' => true]]);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (['B', 'C', 'D', 'E', 'F'] as $c) $sheet->getColumnDimension($c)->setWidth(22);
        $sheet->freezePane('A3');
    }
}
