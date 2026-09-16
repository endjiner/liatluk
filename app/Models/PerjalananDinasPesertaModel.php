<?php

namespace App\Models;

use CodeIgniter\Model;

class PerjalananDinasPesertaModel extends Model
{
    protected $table      = 'perjalanan_dinas_peserta';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'perjalanan_dinas_id', 'pegawai_id', 'nama_peserta', 'uang_harian', 'meeting_fullboard',
        'meeting_fullday', 'uang_representasi', 'transport_lokal', 'bbm', 'total_spj',
        'dana_taktis', 'status_lunas', 'jumlah_disetor', 'tanggal_lunas', 'pemasukan_id'
    ];
    protected $useTimestamps = true;

    public function getByPerjalanan(int $perjalananDinasId): array
    {
        return $this->where('perjalanan_dinas_id', $perjalananDinasId)->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Hitung ulang Total SPJ (semua komponen biaya milik peserta ini, termasuk seluruh
     * tiket & hotelnya) dan Dana Taktis (10% dari Uang Harian saja — TIDAK dari total SPJ,
     * sesuai rumus di spreadsheet sumber). Dipanggil setiap kali peserta/tiket/hotel disimpan.
     */
    public function recalculate(int $pesertaId): void
    {
        $peserta = $this->find($pesertaId);
        if (!$peserta) return;

        $totalTiket = (new PerjalananDinasTiketModel())->getSumHargaByPeserta($pesertaId);
        $hotel      = (new PerjalananDinasHotelModel())->getByPeserta($pesertaId);
        $totalHotel = $hotel ? ((float)$hotel['total_bill'] + (float)$hotel['total_biaya_30persen']) : 0.0;

        $totalSpj = (float)$peserta['uang_harian']
            + (float)$peserta['meeting_fullboard']
            + (float)$peserta['meeting_fullday']
            + (float)$peserta['uang_representasi']
            + (float)$peserta['transport_lokal']
            + (float)$peserta['bbm']
            + $totalTiket
            + $totalHotel;

        $danaTaktis = round((float)$peserta['uang_harian'] * 0.10);

        $this->update($pesertaId, [
            'total_spj'   => $totalSpj,
            'dana_taktis' => $danaTaktis,
        ]);
    }

    /**
     * Tandai setoran Dana Taktis peserta ini lunas atau sebagian: otomatis membukukan
     * nilainya sebagai Pemasukan (kategori "Setoran Taktis Pegawai", status_dana mengikuti
     * lunas/sebagian persis seperti pemasukan biasa) supaya Dashboard/Laporan ikut akurat
     * tanpa input dobel. ID pemasukan yang dibuat disimpan di kolom pemasukan_id supaya bisa
     * di-update (setoran susulan) atau dibatalkan (lihat batalkanLunas()).
     *
     * $jumlahSetor hanya dipakai untuk status 'sebagian' — kalau kosong atau >= dana_taktis,
     * otomatis dianggap lunas penuh (menghindari status "sebagian" yang nilainya 100%).
     */
    public function tandaiLunas(int $id, string $tanggalLunas, string $status = 'lunas', ?float $jumlahSetor = null): bool
    {
        $peserta = $this->find($id);
        if (!$peserta || !in_array($status, ['sebagian', 'lunas'], true)) return false;

        $danaTaktis = (float)$peserta['dana_taktis'];
        if ($status === 'sebagian' && $jumlahSetor !== null && $jumlahSetor > 0 && $jumlahSetor < $danaTaktis) {
            $jumlahDisetor = $jumlahSetor;
        } else {
            $status        = 'lunas';
            $jumlahDisetor = $danaTaktis;
        }

        if ($peserta['status_lunas'] === $status && (float)$peserta['jumlah_disetor'] === $jumlahDisetor) {
            return false; // sudah sesuai, tidak ada perubahan
        }

        $pemasukanId = $peserta['pemasukan_id'];
        if ($danaTaktis > 0) {
            $trip = (new PerjalananDinasModel())->find($peserta['perjalanan_dinas_id']);
            $dataPemasukan = [
                'tanggal'           => $tanggalLunas,
                'kategori'          => 'Setoran Taktis Pegawai',
                'jumlah'            => $danaTaktis,
                'jumlah_diterima'   => $jumlahDisetor,
                'status_dana'       => $status === 'lunas' ? 'diterima' : 'sebagian',
                'sumber'            => $peserta['nama_peserta'],
                'keterangan'        => 'Setoran Dana Taktis 10% Uang Harian — ' . ($trip['maksud'] ?? ('Perjalanan Dinas #' . $peserta['perjalanan_dinas_id'])),
                'dari_tandai_lunas' => 1,
            ];
            if ($pemasukanId) {
                (new PemasukanModel())->update($pemasukanId, $dataPemasukan);
            } else {
                $pemasukanId = (new PemasukanModel())->insert($dataPemasukan);
            }
        }

        return $this->update($id, [
            'status_lunas'   => $status,
            'jumlah_disetor' => $jumlahDisetor,
            'tanggal_lunas'  => $status === 'lunas' ? $tanggalLunas : null,
            'pemasukan_id'   => $pemasukanId,
        ]);
    }

    /**
     * Batalkan status lunas. Pemasukan yang tadi ditautkan HANYA ikut dihapus kalau memang
     * dibuat otomatis oleh tandaiLunas() (dari_tandai_lunas=1) DAN tidak juga masih dipakai
     * peserta lain (setoran gabungan satu pegawai untuk beberapa trip sekaligus). Pemasukan
     * yang sudah ada sebelumnya dan cuma ditautkan (mis. lewat app:reconcile-setoran-taktis,
     * atau data lama) TIDAK PERNAH ikut terhapus di sini — itu data keuangan asli, bukan
     * sesuatu yang dibuat oleh aksi ini, jadi membatalkan tautannya tidak boleh menghilangkan
     * catatan pemasukannya dari pembukuan.
     *
     * @return array{ok: bool, pemasukan_dihapus: bool}
     */
    public function batalkanLunas(int $id): array
    {
        $peserta = $this->find($id);
        if (!$peserta || $peserta['status_lunas'] === 'belum') {
            return ['ok' => false, 'pemasukan_dihapus' => false];
        }

        $pemasukanDihapus = false;
        if (!empty($peserta['pemasukan_id'])) {
            $pemasukan = (new PemasukanModel())->find($peserta['pemasukan_id']);
            $masihDipakai = $this->where('pemasukan_id', $peserta['pemasukan_id'])->where('id !=', $id)->countAllResults();
            if ($pemasukan && (int) $pemasukan['dari_tandai_lunas'] === 1 && $masihDipakai === 0) {
                (new PemasukanModel())->delete($peserta['pemasukan_id']);
                $pemasukanDihapus = true;
            }
        }

        $ok = $this->update($id, [
            'status_lunas'   => 'belum',
            'jumlah_disetor' => 0,
            'tanggal_lunas'  => null,
            'pemasukan_id'   => null,
        ]);

        return ['ok' => $ok, 'pemasukan_dihapus' => $pemasukanDihapus];
    }

    /** Rekap Dana Taktis milik satu pegawai — dipakai di halaman "Dana Taktis". */
    public function getRekapDanaTaktisByPegawai(int $pegawaiId): array
    {
        $rows = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas_peserta.pegawai_id', $pegawaiId)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'DESC')
            ->findAll();

        $totalUangHarian = 0.0;
        $totalSpj        = 0.0;
        $totalDanaTaktis = 0.0;
        $belumDibayar    = 0.0;

        foreach ($rows as $r) {
            $totalUangHarian += (float)$r['uang_harian'];
            $totalSpj        += (float)$r['total_spj'];
            $totalDanaTaktis += (float)$r['dana_taktis'];
            if ($r['status_lunas'] === 'belum') $belumDibayar += (float)$r['dana_taktis'];
            elseif ($r['status_lunas'] === 'sebagian') $belumDibayar += (float)$r['dana_taktis'] - (float)$r['jumlah_disetor'];
        }

        return [
            'rows'              => $rows,
            'total_uang_harian' => $totalUangHarian,
            'total_spj'         => $totalSpj,
            'total_dana_taktis' => $totalDanaTaktis,
            'belum_dibayar'     => $belumDibayar,
        ];
    }

    /** Ringkasan (total + jumlah baris) setoran Dana Taktis yang belum lunas — dipakai di
     *  Rencana Keuangan untuk menampilkan setoran ini sebagai bagian dari rencana pemasukan,
     *  tanpa menulis baris baru ke tabel rencana_pemasukan (selalu diturunkan langsung dari
     *  perjalanan_dinas_peserta supaya tidak bisa jadi tidak sinkron). */
    public function getBelumDibayarSummary(): array
    {
        $row = $this->select("COALESCE(SUM(CASE WHEN status_lunas = 'belum' THEN dana_taktis ELSE dana_taktis - jumlah_disetor END), 0) as total, COUNT(*) as jumlah")
            ->where('status_lunas !=', 'lunas')
            ->where('dana_taktis >', 0)
            ->get()->getRowArray();

        return ['total' => (float)$row['total'], 'jumlah' => (int)$row['jumlah']];
    }

    /**
     * Query dasar (join + filter) yang dipakai bersama oleh getFiltered() & countFiltered() —
     * satu baris per PESERTA per PERJALANAN DINAS (bukan digabung per pegawai), supaya satu
     * pegawai yang ikut 3 perjalanan dinas dengan 1 yang sudah lunas tetap terlihat sebagai
     * 3 baris terpisah dengan status masing-masing.
     */
    private function applyFilterDanaTaktis($filters)
    {
        $builder = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas_peserta.dana_taktis >', 0);

        if (!empty($filters['status'])) {
            $builder->where('perjalanan_dinas_peserta.status_lunas', $filters['status']);
        }
        if (!empty($filters['tahun'])) {
            $builder->where('YEAR(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['tahun']);
        }
        if (!empty($filters['bulan'])) {
            $builder->where('MONTH(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['bulan']);
        }
        $this->applyPencarian($builder, $filters['search'] ?? null, ['perjalanan_dinas.maksud', 'perjalanan_dinas.no_surat_tugas']);
        return $builder;
    }

    /** Pencarian nama/maksud/dll — untuk query PENDEK (<3 huruf) sengaja DIBATASI ke
     *  nama_peserta saja, tidak ikut mencari di field lain. Kalau semua field ikut dicari
     *  untuk query sependek "Al", hasilnya jadi cocok ke HAMPIR SEMUA baris (bukan cuma
     *  yang namanya diawali "Al") karena kata umum seperti "Perjalanan"/"Lokal" yang
     *  muncul di hampir setiap maksud trip SAMA-SAMA mengandung substring "al" — jadi
     *  pencarian nama pendek terasa "tidak menyaring apa-apa" padahal sebenarnya cocok
     *  ke field yang salah. Query 3+ huruf jauh lebih jarang kebetulan begitu, jadi tetap
     *  dicari di semua field seperti biasa. */
    private function applyPencarian($builder, ?string $search, array $fieldLain): void
    {
        if (empty($search)) return;
        if (mb_strlen($search) < 3) {
            $builder->like('perjalanan_dinas_peserta.nama_peserta', $search);
            return;
        }
        $builder->groupStart()->like('perjalanan_dinas_peserta.nama_peserta', $search);
        foreach ($fieldLain as $f) $builder->orLike($f, $search);
        $builder->groupEnd();
    }

    /** Daftar Dana Taktis per peserta-per-trip, dipaginasi & difilter — bukan rumus baru,
     *  dana_taktis sudah dihitung 10% uang_harian saat peserta disimpan (lihat recalculate()). */
    public function getFilteredDanaTaktis(array $filters, int $limit, int $offset): array
    {
        return $this->applyFilterDanaTaktis($filters)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'DESC')
            ->orderBy('perjalanan_dinas_peserta.id', 'DESC')
            ->findAll($limit, $offset);
    }

    public function countFilteredDanaTaktis(array $filters): int
    {
        return $this->applyFilterDanaTaktis($filters)->countAllResults();
    }

    /**
     * Query dasar untuk daftar Perjalanan Dinas versi tabel datar (satu baris per peserta
     * per trip) — dipakai bersama oleh admin & publik. Beda dengan applyFilterDanaTaktis():
     * di sini SEMUA peserta ditampilkan (termasuk yang dana_taktis-nya 0, mis. trip
     * "Transport Lokal" tanpa uang harian), bukan cuma yang punya setoran.
     */
    private function applyFilterPerjalananDinas($filters)
    {
        $builder = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak, perjalanan_dinas.no_spm')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id');

        if (!empty($filters['status'])) {
            $builder->where('perjalanan_dinas_peserta.status_lunas', $filters['status']);
        }
        if (!empty($filters['tahun'])) {
            $builder->where('YEAR(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['tahun']);
        }
        if (!empty($filters['bulan'])) {
            $builder->where('MONTH(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['bulan']);
        }
        $this->applyPencarian($builder, $filters['search'] ?? null, ['perjalanan_dinas.maksud', 'perjalanan_dinas.no_surat_tugas', 'perjalanan_dinas.kode_mak']);
        return $builder;
    }

    /** Daftar Perjalanan Dinas datar (1 baris = 1 peserta), dipaginasi & difilter, lengkap
     *  dengan tiket & hotel per baris — pengganti tampilan kartu bertingkat lama. Diurutkan
     *  (tanggal, trip id, peserta id) supaya peserta dari trip yang sama selalu bersebelahan,
     *  jadi baris "Perjalanan Dinas" yang berulang bisa disembunyikan sisi klien. */
    public function getFilteredPerjalananDinas(array $filters, int $limit, int $offset): array
    {
        $rows = $this->applyFilterPerjalananDinas($filters)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.perjalanan_dinas_id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll($limit, $offset);

        $tiketModel = new PerjalananDinasTiketModel();
        $hotelModel = new PerjalananDinasHotelModel();
        foreach ($rows as &$r) {
            $r['tiket'] = $tiketModel->getByPeserta($r['id']);
            $r['hotel'] = $hotelModel->getByPeserta($r['id']);
        }
        unset($r);

        return $rows;
    }

    public function countFilteredPerjalananDinas(array $filters): int
    {
        return $this->applyFilterPerjalananDinas($filters)->countAllResults();
    }

    /**
     * Semua peserta (dengan data trip) dalam rentang tanggal_surat_tugas [tglMulai, tglAkhir] —
     * dipakai khusus oleh Laporan untuk rincian opsional Perjalanan Dinas / Dana Taktis. Tidak
     * dipaginasi, sama seperti pemasukan/pengeluaran di Laporan yang juga ditampilkan utuh untuk
     * satu periode (bukan per halaman).
     */
    public function getForLaporan(string $tglMulai, string $tglAkhir): array
    {
        return $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas.tanggal_surat_tugas >=', $tglMulai)
            ->where('perjalanan_dinas.tanggal_surat_tugas <=', $tglAkhir)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.perjalanan_dinas_id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll();
    }
}
