<?php

namespace App\Config;

/**
 * Daftar kategori tetap (dropdown) untuk pemasukan & pengeluaran.
 * Sengaja disentralkan di satu tempat agar formulir input, template
 * import, dan hasil migrasi data lama semuanya konsisten.
 */
class Kategori
{
    public static array $pemasukan = [
        'Setoran Taktis Pegawai',
        'Pengembalian Pinjaman',
        'Saldo Awal',
        'Dana APBN',
        'Hibah',
        'PNBP',
        'Pemasukan Lainnya',
    ];

    public static array $pengeluaran = [
        'Bantuan Duka & Sakit',
        'Sumbangan Kelahiran & Pernikahan',
        'Konsumsi & Jamuan Tamu',
        'Perjalanan Dinas & Kunjungan Kerja',
        'Pinjaman Diberikan',
        'Kegiatan & Perlombaan',
        'Donasi & Sumbangan Sosial',
        'Honor & Jasa Kegiatan',
        'Kenang-kenangan & Perpisahan',
        'Operasional & Perlengkapan',
        'ATK',
        'Pemeliharaan',
        'Pengeluaran Lainnya',
    ];
}
