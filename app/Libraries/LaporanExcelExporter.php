<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class LaporanExcelExporter
{
    public function buildSheetDashboard(
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
    ): void {
        $sheet->setTitle('Dashboard');
        $fill = fn($rgb) => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];

        $sheet->setCellValue('A1', 'LAPORAN KEUANGAN INTERNAL');
        $sheet->setCellValue('A2', 'Balai Besar POM di Pangkal Pinang');
        $sheet->setCellValue('A3', 'Periode: ' . date('F Y', strtotime($bulanDari . '-01')) . ' s.d ' . date('F Y', strtotime($bulanSampai . '-01')));
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '0066B2']]]);
        $sheet->getStyle('A2')->applyFromArray(['font' => ['size' => 11, 'color' => ['rgb' => '475569']]]);
        $sheet->getStyle('A3')->applyFromArray(['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']]]);

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
            $sheet->getStyle("{$l}6")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $sheet->setCellValue('A8', 'Rasio Belanja terhadap Pemasukan');
        $sheet->setCellValue('B8', $rasio / 100);
        $sheet->getStyle('B8')->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('A8')->applyFromArray(['font' => ['bold' => true]]);

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

        $chartRow = max($endPemasukan, $endPengeluaran) + 3;

        if (!empty($kategoriPemasukan)) {
            $dataSeriesLabels = [new DataSeriesValues('String', 'Dashboard!$B$' . ($startPemasukan - 1), null, 1)];
            $xAxisTickValues  = [new DataSeriesValues('String', "Dashboard!\$A\${$startPemasukan}:\$A\${$endPemasukan}", null, $endPemasukan - $startPemasukan + 1)];
            $dataSeriesValues = [new DataSeriesValues('Number', "Dashboard!\$B\${$startPemasukan}:\$B\${$endPemasukan}", null, $endPemasukan - $startPemasukan + 1)];

            $series = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            $series->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotArea = new PlotArea(null, [$series]);
            $legend   = new Legend(Legend::POSITION_BOTTOM, null, false);
            $title    = new Title('Pemasukan per Kategori');

            $chart = new Chart('chartPemasukan', $title, $legend, $plotArea);
            $chart->setTopLeftPosition('A' . $chartRow);
            $chart->setBottomRightPosition('F' . ($chartRow + 17));
            $sheet->addChart($chart);
        }

        if (!empty($kategoriPengeluaran)) {
            $dataSeriesLabels = [new DataSeriesValues('String', 'Dashboard!$E$' . ($startPengeluaran - 1), null, 1)];
            $xAxisTickValues  = [new DataSeriesValues('String', "Dashboard!\$D\${$startPengeluaran}:\$D\${$endPengeluaran}", null, $endPengeluaran - $startPengeluaran + 1)];
            $dataSeriesValues = [new DataSeriesValues('Number', "Dashboard!\$E\${$startPengeluaran}:\$E\${$endPengeluaran}", null, $endPengeluaran - $startPengeluaran + 1)];

            $series = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            $series->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotArea = new PlotArea(null, [$series]);
            $legend   = new Legend(Legend::POSITION_BOTTOM, null, false);
            $title    = new Title('Pengeluaran per Kategori');

            $chart = new Chart('chartPengeluaran', $title, $legend, $plotArea);
            $chart->setTopLeftPosition('H' . $chartRow);
            $chart->setBottomRightPosition('M' . ($chartRow + 17));
            $sheet->addChart($chart);
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $c) $sheet->getColumnDimension($c)->setWidth(24);
    }

    public function buildSheetRincianBulanan($sheet, array $rincianBulanan, float $totalPemasukan, float $totalPengeluaran): void
    {
        $sheet->setTitle('Rincian Bulanan');
        $fill = fn($rgb) => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]];

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
        if (!empty($rincianBulanan)) {
            $sheet->setAutoFilter('A1:D' . ($r - 2));
        }
    }

    public function buildSheetTransaksi($sheet, string $judul, array $rows, string $tipe, float $total): void
    {
        $sheet->setTitle($tipe === 'pemasukan' ? 'Rincian Pemasukan' : 'Rincian Pengeluaran');
        $rgb  = $tipe === 'pemasukan' ? '059669' : 'DC2626';
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
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
            $sheet->getStyle("D{$r}:E{$r}")->applyFromArray(['font' => ['bold' => true], 'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]]]);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (['B', 'C', 'D', 'E', 'F'] as $c) $sheet->getColumnDimension($c)->setWidth(22);
        $sheet->freezePane('A3');
        if (!empty($rows)) {
            $sheet->setAutoFilter('A2:F' . ($r - 2));
        }
    }

    public function buildSheetGabungan($sheet, array $gabungan): void
    {
        $sheet->setTitle('Rincian Gabungan');
        $fill = fn($rgb) => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];

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
        if (!empty($gabungan)) {
            $sheet->setAutoFilter('A2:G' . ($r - 1));
        }
    }

    public function buildSheetPerjadin($sheet, array $rows, float $totalSpj): void
    {
        $sheet->setTitle('Perjalanan Dinas');
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];

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
        $r++;

        $headerRow = $r;
        $header = [
            'No', 'Maksud Perjalanan Dinas', 'No. Surat Tugas/Tgl. Surat Tugas', 'Kode MAK', 'No. SPM',
            'Nama Pelaksana Perjalanan Dinas (TANPA GELAR AKADEMIK)', 'Uang Harian', 'Biaya Paket Meeting Fullboard',
            'Biaya Paket Meeting Fullday', 'Uang Representasi (Eselon II)', 'Transportasi Lokal / Luar Kota / Taksi',
            'BBM (Jika Jalan Darat)', 'Maskapai', 'Pergi/Pulang', 'No Tiket', 'Kode Booking', 'No Penerbangan',
            'Tempat Asal', 'Tempat Tujuan', 'Tanggal Terbang', 'Harga Tiket (Rp)', 'Nama Hotel', 'Alamat Hotel',
            'No. Telepon Hotel', 'Tanggal Check-In Hotel', 'Tanggal Check-Out Hotel', 'Total Bill Hotel Yang Dibayarkan',
            'No Kamar (Room)', 'No. Invoice Hotel', 'Total Biaya Hotel (Jika 30%)',
            'TOTAL SPJ YANG DIBAYARKAN BENDAHARA', 'TAKTIS', 'LUNAS',
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
                    $sheet->setCellValue("L{$r}", (float) ($peserta['biaya_bbm'] ?? 0));

                    if ($hotel) {
                        $sheet->setCellValue("V{$r}", $hotel['nama_hotel'] ?? '');
                        $sheet->setCellValue("W{$r}", $hotel['alamat_hotel'] ?? '');
                        $sheet->setCellValue("X{$r}", $hotel['telepon_hotel'] ?? '');
                        $sheet->setCellValue("Y{$r}", !empty($hotel['check_in']) ? date('d/m/Y', strtotime($hotel['check_in'])) : '');
                        $sheet->setCellValue("Z{$r}", !empty($hotel['check_out']) ? date('d/m/Y', strtotime($hotel['check_out'])) : '');
                        $sheet->setCellValue("AA{$r}", (float) ($hotel['total_bill'] ?? 0));
                        $sheet->setCellValue("AB{$r}", $hotel['no_kamar'] ?? '');
                        $sheet->setCellValue("AC{$r}", $hotel['no_invoice'] ?? '');
                        $sheet->setCellValue("AD{$r}", (float) ($hotel['total_biaya_30persen'] ?? 0));
                    }

                    $sheet->setCellValue("AE{$r}", (float) ($peserta['total_spj'] ?? 0));
                    $sheet->setCellValue("AF{$r}", (float) ($peserta['dana_taktis'] ?? 0));

                    $statusLunas = $peserta['status_lunas'] ?? 'belum';
                    $statusText = $statusLunas === 'lunas' ? 'Lunas' : ($statusLunas === 'sebagian' ? 'Sebagian' : 'Belum');
                    if ($statusLunas === 'lunas' && !empty($peserta['tanggal_lunas'])) {
                        $statusText .= ' (' . date('d/m/Y', strtotime($peserta['tanggal_lunas'])) . ')';
                    }
                    $sheet->setCellValue("AG{$r}", $statusText);
                }

                if ($t) {
                    $sheet->setCellValue("M{$r}", $t['maskapai'] ?? '');
                    $sheet->setCellValue("N{$r}", $t['tipe_perjalanan'] ?? '');
                    $sheet->setCellValue("O{$r}", $t['no_tiket'] ?? '');
                    $sheet->setCellValue("P{$r}", $t['kode_booking'] ?? '');
                    $sheet->setCellValue("Q{$r}", $t['no_penerbangan'] ?? '');
                    $sheet->setCellValue("R{$r}", $t['kota_asal'] ?? '');
                    $sheet->setCellValue("S{$r}", $t['kota_tujuan'] ?? '');
                    $sheet->setCellValue("T{$r}", !empty($t['tanggal_penerbangan']) ? date('d/m/Y', strtotime($t['tanggal_penerbangan'])) : '');
                    $sheet->setCellValue("U{$r}", (float) ($t['harga_tiket'] ?? 0));
                }

                $sheet->getStyle("A{$r}:AG{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $r++;
            }
        }

        if (empty($rows)) {
            $sheet->setCellValue("A{$r}", 'Tidak ada data perjalanan dinas pada periode ini');
            $r++;
        } else {
            $sheet->setCellValue("A{$r}", 'TOTAL');
            $sheet->mergeCells("A{$r}:F{$r}");
            $sheet->setCellValue("AE{$r}", $totalSpjSum);
            $sheet->setCellValue("AF{$r}", $totalTaktisSum);

            $sheet->getStyle("A{$r}:AG{$r}")->applyFromArray([
                'font' => ['bold' => true],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE], 'bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle("AE{$r}:AF{$r}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $r++;
        }

        $sheet->freezePane('A' . ($headerRow + 1));
        if (!empty($rows)) {
            $sheet->setAutoFilter("A{$headerRow}:AG" . ($r - 2));
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(28);
        foreach (range('G', 'L') as $col) $sheet->getColumnDimension($col)->setWidth(16);
        foreach (range('M', 'U') as $col) $sheet->getColumnDimension($col)->setWidth(15);
        foreach (range('V', 'AD') as $col) $sheet->getColumnDimension($col)->setWidth(16);
        $sheet->getColumnDimension('AE')->setWidth(22);
        $sheet->getColumnDimension('AF')->setWidth(18);
        $sheet->getColumnDimension('AG')->setWidth(15);
    }

    public function buildSheetDanaTaktis(
        $sheet,
        array $rows,
        string $filterNamaTaktis,
        float $totalUangHarian,
        float $totalSpj,
        float $totalTaktis,
        float $totalBelumSetor
    ): void {
        $sheet->setTitle('Dana Taktis');
        $fillBlue  = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0066B2']];
        $fillAmber = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']];
        $fillGreen = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']];
        $fillGray  = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']];
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]];

        $judul = 'RINCIAN DANA TAKTIS — Diurutkan: Belum Lunas → Lunas (Urut Nama)';
        if ($filterNamaTaktis !== '') {
            $judul .= ' | Filter Nama: ' . $filterNamaTaktis;
        }
        $sheet->setCellValue('A1', $judul);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066B2']]]);

        $ringkasan = [
            ['Total Uang Harian',   $totalUangHarian, '"Rp" #,##0'],
            ['Total SPJ',           $totalSpj,         '"Rp" #,##0'],
            ['Dana Taktis (Total)', $totalTaktis,      '"Rp" #,##0'],
            ['Belum Disetor',       $totalBelumSetor,  '"Rp" #,##0'],
        ];
        $r = 3;
        foreach ($ringkasan as [$label, $nilai, $fmt]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $nilai);
            $sheet->getStyle("A{$r}")->applyFromArray(['font' => ['bold' => true]]);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($fmt);
            $r++;
        }
        $r++;

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
            usort($rows, function ($a, $b) {
                $aStatus = ($a['status_lunas'] ?? 'belum') === 'lunas' ? 1 : 0;
                $bStatus = ($b['status_lunas'] ?? 'belum') === 'lunas' ? 1 : 0;
                if ($aStatus !== $bStatus) return $aStatus - $bStatus;
                return strcmp($a['nama_peserta'] ?? '', $b['nama_peserta'] ?? '');
            });

            $grouped = [];
            foreach ($rows as $row) {
                $nama = $row['nama_peserta'] ?? 'Tidak Diketahui';
                $grouped[$nama][] = $row;
            }

            $no = 1;
            foreach ($grouped as $nama => $namaRows) {
                $subTaktis   = array_sum(array_column($namaRows, 'dana_taktis'));
                $subDisetor  = array_sum(array_column($namaRows, 'jumlah_disetor'));
                $subBelum    = 0.0;
                foreach ($namaRows as $nr) {
                    $st = $nr['status_lunas'] ?? 'belum';
                    if ($st === 'belum')        $subBelum += (float) $nr['dana_taktis'];
                    elseif ($st === 'sebagian') $subBelum += max(0.0, (float) $nr['dana_taktis'] - (float) ($nr['jumlah_disetor'] ?? 0));
                }
                $semuaLunas = $subBelum <= 0;

                foreach ($namaRows as $row) {
                    $status    = $row['status_lunas'] ?? 'belum';
                    $isLunas   = $status === 'lunas';
                    $rowFill   = $isLunas ? $fillGreen : $fillAmber;
                    $statusTxt = $isLunas ? 'Lunas' : ($status === 'sebagian' ? 'Sebagian' : 'Belum Lunas');
                    $disetor   = (float) ($row['jumlah_disetor'] ?? 0);
                    if ($isLunas) $disetor = (float) $row['dana_taktis'];

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
                $r++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->freezePane('A' . ($headerRow + 1));
        if (!empty($rows)) {
            $sheet->setAutoFilter("A{$headerRow}:G" . ($r - 1));
        }
    }
}
