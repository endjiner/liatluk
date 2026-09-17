<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Keuangan BBPOM di Pangkal Pinang (<?= $bulanDari ?> s/d <?= $bulanSampai ?>)</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/laporan-pdf.css') ?>">
  <?php if (!empty($sertakanPerjadin)): ?>
  <style>
    @page { size: landscape; margin: 10mm; }
    body { padding: 10px; }
  </style>
  <?php endif; ?>
</head>
<body onload="window.print()">

<div class="header">
  <h2>Balai Besar Pengawas Obat dan Makanan di Pangkal Pinang</h2>
  <h3>LAPORAN REKAPITULASI KEUANGAN INTERNAL</h3>
  <p>Periode: <?= date('F Y', strtotime($bulanDari . '-01')) ?> s/d <?= date('F Y', strtotime($bulanSampai . '-01')) ?></p>
  <?php if (!empty($periodeDipangkas)): ?>
  <p class="periode-note">Catatan: rentang periode yang diminta dibatasi maksimal <?= $maxBulanPeriode ?> bulan supaya laporan tetap cepat dibuat.</p>
  <?php endif; ?>
</div>

<table class="kpi-table">
  <tr>
    <td>
      <div class="kpi-title">Saldo Awal Periode</div>
      <div class="kpi-val"><?= 'Rp ' . number_format($saldoAwal, 0, ',', '.') ?></div>
    </td>
    <td>
      <div class="kpi-title">Pemasukan Diterima</div>
      <div class="kpi-val text-success"><?= 'Rp ' . number_format($totalPemasukan, 0, ',', '.') ?></div>
    </td>
    <td>
      <div class="kpi-title">Pengeluaran</div>
      <div class="kpi-val text-danger"><?= 'Rp ' . number_format($totalPengeluaran, 0, ',', '.') ?></div>
    </td>
    <td>
      <div class="kpi-title">Saldo Akhir Periode</div>
      <div class="kpi-val"><?= 'Rp ' . number_format($saldoAkhir, 0, ',', '.') ?></div>
    </td>
  </tr>
</table>
<p class="rasio-note">Rasio belanja terhadap pemasukan periode ini: <strong><?= number_format($rasio, 1, ',', '.') ?>%</strong></p>

<div class="section-head">Rekapitulasi per Kategori</div>
<table class="data-table data-table-split">
  <thead>
    <tr>
      <th colspan="2" class="col-header-success">PEMASUKAN</th>
      <th class="col-spacer"></th>
      <th colspan="2" class="col-header-danger">PENGELUARAN</th>
    </tr>
    <tr>
      <th class="col-w30">Kategori</th>
      <th class="col-w20">Total</th>
      <th class="col-spacer"></th>
      <th class="col-w30">Kategori</th>
      <th class="col-w20">Total</th>
    </tr>
  </thead>
  <tbody>
    <?php
      $katP = array_keys($kategoriPemasukan);
      $katE = array_keys($kategoriPengeluaran);
      $maxKat = max(count($katP), count($katE));
      if ($maxKat === 0) $maxKat = 1;
    ?>
    <?php for ($i = 0; $i < $maxKat; $i++): ?>
    <tr>
      <?php if (isset($katP[$i])): $k = $katP[$i]; ?>
      <td><?= esc($k) ?></td>
      <td class="text-right text-success"><?= number_format($kategoriPemasukan[$k], 0, ',', '.') ?></td>
      <?php else: ?>
      <td colspan="2"></td>
      <?php endif; ?>
      <td class="col-spacer"></td>
      <?php if (isset($katE[$i])): $k = $katE[$i]; ?>
      <td><?= esc($k) ?></td>
      <td class="text-right text-danger"><?= number_format($kategoriPengeluaran[$k], 0, ',', '.') ?></td>
      <?php else: ?>
      <td colspan="2"></td>
      <?php endif; ?>
    </tr>
    <?php endfor; ?>
    <?php if (empty($kategoriPemasukan) && empty($kategoriPengeluaran)): ?>
    <tr><td colspan="5" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php endif; ?>
  </tbody>
  <?php if (!empty($kategoriPemasukan) || !empty($kategoriPengeluaran)): ?>
  <tfoot>
    <tr>
      <td class="font-bold">TOTAL</td>
      <td class="text-right text-success font-bold"><?= number_format($totalPemasukan, 0, ',', '.') ?></td>
      <td class="col-spacer"></td>
      <td class="font-bold">TOTAL</td>
      <td class="text-right text-danger font-bold"><?= number_format($totalPengeluaran, 0, ',', '.') ?></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<div class="section-head">Rincian per Bulan</div>
<table class="data-table">
  <thead>
    <tr>
      <th class="col-w30">Bulan</th>
      <th class="col-w25">Pemasukan</th>
      <th class="col-w25">Pengeluaran</th>
      <th class="col-w20">Selisih</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rincianBulanan)): ?>
    <tr><td colspan="4" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($rincianBulanan as $ym => $b): $selisihBulan = $b['pemasukan'] - $b['pengeluaran']; ?>
    <tr>
      <td><?= esc(date('F Y', strtotime($ym . '-01'))) ?></td>
      <td class="text-right text-success"><?= number_format($b['pemasukan'], 0, ',', '.') ?></td>
      <td class="text-right text-danger"><?= number_format($b['pengeluaran'], 0, ',', '.') ?></td>
      <td class="text-right <?= $selisihBulan >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($selisihBulan, 0, ',', '.') ?></td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($rincianBulanan)): ?>
  <tfoot>
    <tr class="font-bold">
      <td>TOTAL</td>
      <td class="text-right text-success"><?= number_format($totalPemasukan, 0, ',', '.') ?></td>
      <td class="text-right text-danger"><?= number_format($totalPengeluaran, 0, ',', '.') ?></td>
      <td class="text-right <?= ($totalPemasukan - $totalPengeluaran) >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($totalPemasukan - $totalPengeluaran, 0, ',', '.') ?></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<div class="section-head">Rincian Pemasukan</div>
<table class="data-table">
  <thead>
    <tr>
      <th class="col-w5">No</th>
      <th class="col-w12">Tanggal</th>
      <th class="col-w25">Kategori</th>
      <th class="col-w20">Sumber</th>
      <th class="col-w15">Jumlah</th>
      <th>Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pemasukans)): ?>
    <tr><td colspan="6" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($pemasukans as $i => $p): ?>
    <tr>
      <td class="text-center"><?= $i + 1 ?></td>
      <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
      <td><?= esc($p['kategori']) ?></td>
      <td><?= esc($p['sumber'] ?? '-') ?></td>
      <td class="text-right text-success"><?= number_format($p['jumlah_diterima'], 0, ',', '.') ?></td>
      <td><?= esc($p['keterangan'] ?? '-') ?></td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($pemasukans)): ?>
  <tfoot>
    <tr>
      <td colspan="4" class="text-right font-bold">TOTAL PEMASUKAN</td>
      <td class="text-right text-success font-bold"><?= number_format($totalPemasukan, 0, ',', '.') ?></td>
      <td></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<div class="section-head">Rincian Pengeluaran</div>
<table class="data-table">
  <thead>
    <tr>
      <th class="col-w5">No</th>
      <th class="col-w12">Tanggal</th>
      <th class="col-w25">Kategori</th>
      <th class="col-w20">Tujuan</th>
      <th class="col-w15">Jumlah</th>
      <th>Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pengeluarans)): ?>
    <tr><td colspan="6" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($pengeluarans as $i => $e): ?>
    <tr>
      <td class="text-center"><?= $i + 1 ?></td>
      <td><?= date('d/m/Y', strtotime($e['tanggal'])) ?></td>
      <td><?= esc($e['kategori']) ?></td>
      <td><?= esc($e['tujuan'] ?? '-') ?></td>
      <td class="text-right text-danger"><?= number_format($e['jumlah'], 0, ',', '.') ?></td>
      <td><?= esc($e['keterangan'] ?? '-') ?></td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($pengeluarans)): ?>
  <tfoot>
    <tr>
      <td colspan="4" class="text-right font-bold">TOTAL PENGELUARAN</td>
      <td class="text-right text-danger font-bold"><?= number_format($totalPengeluaran, 0, ',', '.') ?></td>
      <td></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<div class="section-head">Rincian Gabungan — Buku Kas Umum (Kronologis, Saldo Berjalan)</div>
<table class="data-table data-table-gabungan">
  <thead>
    <tr>
      <th class="col-w5">No</th>
      <th class="col-w12">Tanggal</th>
      <th class="col-w25">Kategori</th>
      <th class="col-w15 col-header-success">Pemasukan</th>
      <th class="col-w15 col-header-danger">Pengeluaran</th>
      <th class="col-w15">Saldo</th>
      <th>Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($gabungan)): ?>
    <tr><td colspan="7" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($gabungan as $i => $g): ?>
    <tr>
      <td class="text-center"><?= $i + 1 ?></td>
      <td><?= date('d/m/Y', strtotime($g['tanggal'])) ?></td>
      <td><?= esc($g['kategori']) ?></td>
      <td class="text-right text-success"><?= $g['pemasukan'] > 0 ? number_format($g['pemasukan'], 0, ',', '.') : '' ?></td>
      <td class="text-right text-danger"><?= $g['pengeluaran'] > 0 ? number_format($g['pengeluaran'], 0, ',', '.') : '' ?></td>
      <td class="text-right font-bold"><?= number_format($g['saldo'], 0, ',', '.') ?></td>
      <td><?= esc($g['keterangan'] ?? '-') ?></td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<?php if (!empty($sertakanPerjadin)): ?>
<div class="section-head">Rincian Perjalanan Dinas</div>
<table class="data-table data-table-perjadin">
  <thead>
    <tr>
      <th style="width:4%">No. PD</th>
      <th style="width:10%">Tgl & No. ST</th>
      <th style="width:13%">Maksud Perjalanan</th>
      <th style="width:10%">MAK / SPM</th>
      <th style="width:11%">Pelaksana</th>
      <th class="text-right" style="width:6.5%">Uang Harian</th>
      <th class="text-right" style="width:6%">Meeting FB</th>
      <th class="text-right" style="width:6%">Meeting FD</th>
      <th class="text-right" style="width:6%">Uang Repr.</th>
      <th class="text-right" style="width:6.5%">Transp Lokal</th>
      <th class="text-right" style="width:5.5%">BBM</th>
      <th class="text-right" style="width:6.5%">Tiket</th>
      <th class="text-right" style="width:6.5%">Hotel</th>
      <th class="text-right font-bold" style="width:7.5%">Total SPJ</th>
      <th class="text-right" style="width:6%">Taktis</th>
      <th class="text-center" style="width:4%">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($perjadinRows)): ?>
    <tr><td colspan="16" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($perjadinRows as $idx => $r): ?>
    <tr>
      <td class="text-center font-bold">
        <?= !empty($r['no_pd']) ? esc($r['no_pd']) : ($idx + 1) ?>
      </td>
      <td>
        <?= !empty($r['tanggal_surat_tugas']) ? date('d/m/Y', strtotime($r['tanggal_surat_tugas'])) : '-' ?><br>
        <small style="color:#64748b"><?= esc($r['no_surat_tugas'] ?: '-') ?></small>
      </td>
      <td><?= esc($r['maksud']) ?></td>
      <td>
        <?= esc($r['kode_mak'] ?: '-') ?>
        <?php if (!empty($r['no_spm'])): ?><br><small style="color:#64748b">SPM: <?= esc($r['no_spm']) ?></small><?php endif; ?>
      </td>
      <td class="font-bold"><?= esc($r['nama_peserta']) ?></td>
      <td class="text-right"><?= number_format((float)($r['uang_harian'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['meeting_fullboard'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['meeting_fullday'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['uang_representasi'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['transport_lokal'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['bbm'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['total_tiket'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['total_hotel'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right font-bold"><?= number_format((float)($r['total_spj'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-right"><?= number_format((float)($r['dana_taktis'] ?? 0), 0, ',', '.') ?></td>
      <td class="text-center">
        <?= ($r['status_lunas'] ?? '') === 'lunas' ? ('Lunas' . (!empty($r['tanggal_lunas']) ? '<br><span style="font-size:8px;color:#64748b;">' . date('d/m/Y', strtotime($r['tanggal_lunas'])) . '</span>' : '')) : 'Belum' ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($perjadinRows)): ?>
  <tfoot>
    <tr>
      <td colspan="13" class="text-right font-bold">TOTAL SPJ</td>
      <td class="text-right font-bold"><?= number_format($perjadinTotalSpj, 0, ',', '.') ?></td>
      <td class="text-right font-bold"><?= number_format(array_sum(array_column($perjadinRows, 'dana_taktis')), 0, ',', '.') ?></td>
      <td></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>
<?php endif; ?>

<?php if (!empty($sertakanDanaTaktis)): ?>
<div class="section-head">Rincian Dana Taktis</div>
<table class="data-table">
  <thead>
    <tr>
      <th class="col-w12">Tanggal</th>
      <th class="col-w20">Nama Peserta</th>
      <th>Maksud Perjalanan</th>
      <th class="col-w15">Dana Taktis</th>
      <th class="col-w15">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($danaTaktisRows)): ?>
    <tr><td colspan="5" class="text-center">Tidak ada data pada periode ini</td></tr>
    <?php else: foreach ($danaTaktisRows as $row): ?>
    <tr>
      <td><?= date('d/m/Y', strtotime($row['tanggal_surat_tugas'])) ?></td>
      <td><?= esc($row['nama_peserta']) ?></td>
      <td><?= esc($row['maksud']) ?></td>
      <td class="text-right"><?= number_format($row['dana_taktis'], 0, ',', '.') ?></td>
      <td class="<?= $row['status_lunas'] === 'lunas' ? 'text-success' : 'text-danger' ?>">
        <?= $row['status_lunas'] === 'lunas' ? ('Lunas' . (!empty($row['tanggal_lunas']) ? '<br><span style="font-size:8px;color:#64748b;">' . date('d/m/Y', strtotime($row['tanggal_lunas'])) . '</span>' : '')) : 'Belum Lunas' ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($danaTaktisRows)): ?>
  <tfoot>
    <tr>
      <td colspan="3" class="text-right font-bold">TOTAL LUNAS / BELUM LUNAS</td>
      <td colspan="2" class="text-right font-bold"><?= number_format($danaTaktisTotalLunas, 0, ',', '.') ?> / <?= number_format($danaTaktisTotalBelum, 0, ',', '.') ?></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>
<?php endif; ?>

</body>
</html>
