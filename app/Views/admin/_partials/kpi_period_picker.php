<?php
$tahunNow = (int)date('Y');
$bulanNow = (int)date('m');
$tahunOpsi = range($tahunNow, 2016);
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>
<div class="mt-2 space-y-1.5">
  <div class="segment w-full">
    <button type="button" class="segment-btn kpi-tab active flex-1 px-1 sm:px-3 text-[11px] sm:text-xs" data-scope="semua">Semua</button>
    <button type="button" class="segment-btn kpi-tab flex-1 px-1 sm:px-3 text-[11px] sm:text-xs" data-scope="tahun">Tahun</button>
    <button type="button" class="segment-btn kpi-tab flex-1 px-1 sm:px-3 text-[11px] sm:text-xs" data-scope="bulan">Bulan</button>
  </div>
  <div class="flex gap-2">
    <div class="hidden flex-1">
      <select class="kpi-year form-control form-control-sm" aria-label="Pilih tahun untuk <?= esc($id ?? 'KPI') ?>">
        <?php foreach ($tahunOpsi as $t): ?>
        <option value="<?= $t ?>" <?= $t === $tahunNow ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="hidden flex-1">
      <select class="kpi-month form-control form-control-sm" aria-label="Pilih bulan untuk <?= esc($id ?? 'KPI') ?>">
        <?php foreach ($namaBulan as $n => $nm): ?>
        <option value="<?= $n ?>" <?= $n === $bulanNow ? 'selected' : '' ?>><?= $nm ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>
