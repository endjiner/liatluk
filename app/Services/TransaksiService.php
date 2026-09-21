<?php

namespace App\Services;

use App\Config\Kategori;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;

class TransaksiService
{
    /**
     * Mengambil daftar transaksi gabungan (Pemasukan & Pengeluaran) dengan filter,
     * pengurutan urut tanggal terbaru, pagination bolak-balik (DESC ke ASC),
     * serta penomoran baris yang konsisten.
     *
     * @param array     $filters         Filter: search, bulan, tahun, kategori
     * @param bool      $showPemasukan   Tampilkan transaksi pemasukan
     * @param bool      $showPengeluaran Tampilkan transaksi pengeluaran
     * @param int       $perPage         Jumlah baris per halaman
     * @param int|null  $page            Halaman saat ini (null = otomatis halaman terakhir/terbaru)
     * @param bool      $isPublic        Jika true, sembunyikan catatan_internal dan validasi kategori publik
     * @return array{data: array, total: int, page: int, per_page: int, total_pages: int, offset: int}
     */
    public static function getTransaksiGabungan(
        array $filters,
        bool $showPemasukan = true,
        bool $showPengeluaran = true,
        int $perPage = 10,
        ?int $page = null,
        bool $isPublic = false
    ): array {
        $pemasukanModel   = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();

        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $kat = (array)($filters['kategori'] ?? []);
        if ($isPublic) {
            $katP = !empty($kat) ? array_values(array_intersect($kat, Kategori::$pemasukan)) : [];
            $katE = !empty($kat) ? array_values(array_intersect($kat, Kategori::$pengeluaran)) : [];
            $skipP = !$showPemasukan || (!empty($kat) && empty($katP));
            $skipE = !$showPengeluaran || (!empty($kat) && empty($katE));
            $filtersP = array_merge($filters, ['kategori' => $katP]);
            $filtersE = array_merge($filters, ['kategori' => $katE]);
        } else {
            $skipP = !$showPemasukan;
            $skipE = !$showPengeluaran;
            $filtersP = $filters;
            $filtersE = $filters;
        }

        $total = ($skipP ? 0 : $pemasukanModel->countFiltered($filtersP))
               + ($skipE ? 0 : $pengeluaranModel->countFiltered($filtersE));

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = ($page !== null && $page !== 0) ? max(1, min($page, $totalPages)) : $totalPages;

        $startNum = ($page - 1) * $perPage + 1;
        $endNum   = min($total, $page * $perPage);
        $take     = max(0, $endNum - $startNum + 1);

        $descOffset = max(0, $total - $endNum);
        $fetchLimit = $descOffset + $take;

        $data = [];
        if (!$skipP) {
            foreach ($pemasukanModel->getFiltered($filtersP, $fetchLimit, 0) as $r) {
                if ($isPublic) {
                    unset($r['catatan_internal']);
                }
                $data[] = array_merge($r, ['tipe' => 'pemasukan']);
            }
        }
        if (!$skipE) {
            foreach ($pengeluaranModel->getFiltered($filtersE, $fetchLimit, 0) as $r) {
                if ($isPublic) {
                    unset($r['catatan_internal']);
                }
                $data[] = array_merge($r, ['tipe' => 'pengeluaran']);
            }
        }

        // Urutkan tanggal DESC, sekunder ID DESC
        usort($data, function ($a, $b) {
            $t = strtotime($b['tanggal']) - strtotime($a['tanggal']);
            if ($t !== 0) return $t;
            return ((int)($b['id'] ?? 0)) - ((int)($a['id'] ?? 0));
        });

        // Potong rentang yang dibutuhkan (DESC) lalu balik ke ASC
        $slice = array_reverse(array_slice($data, $descOffset, $take));
        foreach ($slice as $i => &$row) {
            $row['nomor'] = $startNum + $i;
        }
        unset($row);

        return [
            'data'        => $slice,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'offset'      => ($page - 1) * $perPage,
        ];
    }
}
