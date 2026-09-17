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
     * Hitung ulang Total SPJ dan Dana Taktis (10% Uang Harian).
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
     * Format teks keterangan & data relasional MariaDB untuk pencatatan di tabel Pemasukan.
     * Mengisi kolom relasional native MariaDB: no_surat_tugas, kode_mak, no_spm, perjalanan_dinas_id,
     * serta identitas peserta sehingga terintegrasi langsung di database MariaDB tanpa memerlukan JSON.
     */
    public function formatMetadataPemasukan(array $peserta, ?array $trip): array
    {
        $maksud      = $trip['maksud'] ?? ('Perjalanan Dinas #' . ($peserta['perjalanan_dinas_id'] ?? '-'));
        $noST        = !empty($trip['no_surat_tugas']) ? $trip['no_surat_tugas'] : null;
        $kodeMak     = !empty($trip['kode_mak']) ? $trip['kode_mak'] : null;
        $noSpm       = !empty($trip['no_spm']) ? $trip['no_spm'] : null;

        $parts = ["Setoran Dana Taktis 10% Uang Harian — {$maksud}"];
        if ($noST) $parts[] = "No. ST: {$noST}";
        if ($kodeMak) $parts[] = "MAK: {$kodeMak}";
        if ($noSpm) $parts[] = "SPM: {$noSpm}";
        $keterangan = implode(' | ', $parts);

        return [
            'keterangan'                  => $keterangan,
            'perjalanan_dinas_id'         => (int)($peserta['perjalanan_dinas_id'] ?? 0),
            'perjalanan_dinas_peserta_id' => (int)($peserta['id'] ?? 0),
            'no_surat_tugas'              => $noST,
            'kode_mak'                    => $kodeMak,
            'no_spm'                      => $noSpm,
        ];
    }

    /**
     * Sinkronisasi status setoran taktis peserta ke tabel Pemasukan.
     * Jika status lunas/sebagian: membukukan ke Pemasukan dengan kategori "Setoran Taktis Pegawai"
     * dan sumber sesuai nama peserta yang membayar, disimpan langsung ke kolom-kolom MariaDB.
     * Jika status belum: membatalkan dan menghapus baris pemasukan otomatis terkait.
     */
    public function sinkronPemasukan(int $id, ?string $tanggalSetoran = null, ?string $forceStatus = null, ?float $forceJumlahDisetor = null): bool
    {
        $peserta = $this->find($id);
        if (!$peserta) return false;

        $status = $forceStatus ?? $peserta['status_lunas'];
        if (!in_array($status, ['belum', 'sebagian', 'lunas'], true)) return false;

        // Jika status belum, batalkan pelunasan
        if ($status === 'belum') {
            $this->batalkanLunas($id);
            return true;
        }

        $danaTaktis = (float)$peserta['dana_taktis'];
        if ($danaTaktis <= 0) {
            // Jika dana taktis 0, tidak ada setoran yang masuk kas
            $this->update($id, [
                'status_lunas'   => $status,
                'jumlah_disetor' => 0,
                'tanggal_lunas'  => ($tanggalSetoran ?: date('Y-m-d')),
                'pemasukan_id'   => null,
            ]);
            return true;
        }

        if ($status === 'sebagian') {
            $jumlahDisetor = ($forceJumlahDisetor !== null) ? $forceJumlahDisetor : (float)$peserta['jumlah_disetor'];
            if ($jumlahDisetor >= $danaTaktis) {
                $status        = 'lunas';
                $jumlahDisetor = $danaTaktis;
            } elseif ($jumlahDisetor <= 0) {
                // Sebagian tapi nilainya 0 -> batalkan
                $this->batalkanLunas($id);
                return true;
            }
        } else {
            $jumlahDisetor = $danaTaktis;
        }

        $tanggal = $tanggalSetoran ?: ($peserta['tanggal_lunas'] ?: date('Y-m-d'));
        $trip    = (new PerjalananDinasModel())->find($peserta['perjalanan_dinas_id']);

        $meta = $this->formatMetadataPemasukan(array_merge($peserta, [
            'dana_taktis'    => $danaTaktis,
            'jumlah_disetor' => $jumlahDisetor,
            'status_lunas'   => $status,
            'tanggal_lunas'  => $tanggal,
        ]), $trip);

        $dataPemasukan = [
            'tanggal'                     => $tanggal,
            'kategori'                    => 'Setoran Taktis Pegawai',
            'jumlah'                      => $status === 'sebagian' ? $jumlahDisetor : $danaTaktis,
            'jumlah_diterima'             => $jumlahDisetor,
            'status_dana'                 => $status === 'lunas' ? 'diterima' : 'sebagian',
            'sumber'                      => $peserta['nama_peserta'],
            'keterangan'                  => $meta['keterangan'],
            'catatan_internal'            => null,
            'dari_tandai_lunas'           => 1,
            'perjalanan_dinas_id'         => $meta['perjalanan_dinas_id'],
            'perjalanan_dinas_peserta_id' => $meta['perjalanan_dinas_peserta_id'],
            'no_surat_tugas'              => $meta['no_surat_tugas'],
            'kode_mak'                    => $meta['kode_mak'],
            'no_spm'                      => $meta['no_spm'],
        ];

        $pemasukanModel = new PemasukanModel();
        $pemasukanId    = $peserta['pemasukan_id'];

        if ($pemasukanId && $pemasukanModel->find($pemasukanId)) {
            $pemasukanModel->update($pemasukanId, $dataPemasukan);
        } else {
            $pemasukanId = $pemasukanModel->insert($dataPemasukan);
        }

        return (bool) $this->update($id, [
            'status_lunas'   => $status,
            'jumlah_disetor' => $jumlahDisetor,
            'tanggal_lunas'  => $tanggal,
            'pemasukan_id'   => $pemasukanId,
        ]);
    }

    /**
     * Tandai setoran Dana Taktis peserta ini lunas atau sebagian (delegasi ke sinkronPemasukan).
     */
    public function tandaiLunas(int $id, string $tanggalLunas, string $status = 'lunas', ?float $jumlahSetor = null): bool
    {
        return $this->sinkronPemasukan($id, $tanggalLunas, $status, $jumlahSetor);
    }

    /**
     * Batalkan status lunas peserta dan hapus pemasukan otomatis terkait.
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
            if ($pemasukan && (int)$pemasukan['dari_tandai_lunas'] === 1 && $masihDipakai === 0) {
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

        return ['ok' => (bool)$ok, 'pemasukan_dihapus' => $pemasukanDihapus];
    }

    /** Rekap Dana Taktis milik satu pegawai. */
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

    /** Ringkasan setoran Dana Taktis yang belum lunas. */
    public function getBelumDibayarSummary(): array
    {
        $row = $this->select("COALESCE(SUM(CASE WHEN status_lunas = 'belum' THEN dana_taktis ELSE dana_taktis - jumlah_disetor END), 0) as total, COUNT(*) as jumlah")
            ->where('status_lunas !=', 'lunas')
            ->where('dana_taktis >', 0)
            ->get()->getRowArray();

        return ['total' => (float)$row['total'], 'jumlah' => (int)$row['jumlah']];
    }

    /**
     * Query dasar (join + filter) terpadu untuk daftar peserta perjalanan dinas / dana taktis.
     */
    private function applyFilterPesertaTrip(array $filters, bool $danaTaktisOnly = false)
    {
        $builder = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.no_pd, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak, perjalanan_dinas.no_spm')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id');

        if ($danaTaktisOnly) {
            $builder->where('perjalanan_dinas_peserta.dana_taktis >', 0);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'belum') {
                $builder->whereIn('perjalanan_dinas_peserta.status_lunas', ['belum', 'sebagian'])
                        ->where('perjalanan_dinas_peserta.dana_taktis >', 0);
            } else {
                $builder->groupStart()
                        ->where('perjalanan_dinas_peserta.status_lunas', $filters['status'])
                        ->orWhere('perjalanan_dinas_peserta.dana_taktis <=', 0)
                        ->groupEnd();
            }
        }
        if (!empty($filters['tahun'])) {
            $builder->where('YEAR(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['tahun']);
        }
        if (!empty($filters['bulan'])) {
            $builder->where('MONTH(perjalanan_dinas.tanggal_surat_tugas)', (int)$filters['bulan']);
        }
        if ($danaTaktisOnly) {
            // Pada Dana Taktis, fokus pencarian adalah nama pegawai dan data administrasi (No. PD, ST, MAK, SPM), bukan teks maksud perjadin
            $searchFields = ['perjalanan_dinas.no_pd', 'perjalanan_dinas.no_surat_tugas', 'perjalanan_dinas.kode_mak', 'perjalanan_dinas.no_spm'];
        } else {
            // Pada Perjalanan Dinas, sertakan maksud hanya jika pencarian cukup spesifik (>= 4 huruf)
            $search = trim((string)($filters['search'] ?? ''));
            $searchFields = ['perjalanan_dinas.no_pd', 'perjalanan_dinas.no_surat_tugas', 'perjalanan_dinas.kode_mak', 'perjalanan_dinas.no_spm'];
            if (mb_strlen($search) >= 4) {
                $searchFields[] = 'perjalanan_dinas.maksud';
            }
        }
        $this->applyPencarian($builder, $filters['search'] ?? null, $searchFields);
        return $builder;
    }

    private function applyPencarian($builder, ?string $search, array $fieldLain): void
    {
        $search = trim((string)$search);
        if ($search === '') return;
        
        // Query pendek (< 3 huruf): izinkan pencarian nama pegawai dan No. PD (misal: "UP", "52", "1")
        if (mb_strlen($search) < 3) {
            $builder->groupStart()
                ->like('perjalanan_dinas_peserta.nama_peserta', $search)
                ->orLike('perjalanan_dinas.no_pd', $search)
                ->groupEnd();
            return;
        }

        $builder->groupStart()->like('perjalanan_dinas_peserta.nama_peserta', $search);
        foreach ($fieldLain as $f) {
            $builder->orLike($f, $search);
        }
        $builder->groupEnd();
    }

    /**
     * Mengambil data perjalanan dinas terpaginasi lengkap dengan tiket & hotel.
     */
    public function getPaginatedPerjalananDinas(array $filters): array
    {
        $perPage = (int)($filters['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 10;
        $filters['per_page'] = $perPage;

        $total      = $this->applyFilterPesertaTrip($filters, false)->countAllResults();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = !empty($filters['page']) ? min(max(1, (int)$filters['page']), $totalPages) : $totalPages;
        $offset     = ($page - 1) * $perPage;

        $rows = $this->applyFilterPesertaTrip($filters, false)
            ->orderBy('perjalanan_dinas.id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll($perPage, $offset);

        $tiketModel = new PerjalananDinasTiketModel();
        $hotelModel = new PerjalananDinasHotelModel();
        foreach ($rows as &$r) {
            $r['tiket'] = $tiketModel->getByPeserta($r['id']);
            $r['hotel'] = $hotelModel->getByPeserta($r['id']);
        }
        unset($r);

        return [
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'offset'      => $offset,
        ];
    }

    /**
     * Mengambil data dana taktis terpaginasi.
     */
    public function getPaginatedDanaTaktis(array $filters): array
    {
        $perPage = (int)($filters['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 10;
        $filters['per_page'] = $perPage;

        $total      = $this->applyFilterPesertaTrip($filters, true)->countAllResults();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = !empty($filters['page']) ? min(max(1, (int)$filters['page']), $totalPages) : $totalPages;
        $offset     = ($page - 1) * $perPage;

        $rows = $this->applyFilterPesertaTrip($filters, true)
            ->orderBy('perjalanan_dinas.id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll($perPage, $offset);

        return [
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'offset'      => $offset,
        ];
    }

    public function getFilteredDanaTaktis(array $filters, int $limit, int $offset): array
    {
        return $this->applyFilterPesertaTrip($filters, true)
            ->orderBy('perjalanan_dinas.id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll($limit, $offset);
    }

    public function countFilteredDanaTaktis(array $filters): int
    {
        return $this->applyFilterPesertaTrip($filters, true)->countAllResults();
    }

    public function getFilteredPerjalananDinas(array $filters, int $limit, int $offset): array
    {
        $rows = $this->applyFilterPesertaTrip($filters, false)
            ->orderBy('perjalanan_dinas.id', 'ASC')
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
        return $this->applyFilterPesertaTrip($filters, false)->countAllResults();
    }

    public function getForLaporan(string $tglMulai, string $tglAkhir): array
    {
        $rows = $this->select('perjalanan_dinas_peserta.*, perjalanan_dinas.no_pd, perjalanan_dinas.maksud, perjalanan_dinas.no_surat_tugas, perjalanan_dinas.tanggal_surat_tugas, perjalanan_dinas.kode_mak, perjalanan_dinas.no_spm')
            ->join('perjalanan_dinas', 'perjalanan_dinas.id = perjalanan_dinas_peserta.perjalanan_dinas_id')
            ->where('perjalanan_dinas.tanggal_surat_tugas >=', $tglMulai)
            ->where('perjalanan_dinas.tanggal_surat_tugas <=', $tglAkhir)
            ->orderBy('perjalanan_dinas.tanggal_surat_tugas', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.perjalanan_dinas_id', 'ASC')
            ->orderBy('perjalanan_dinas_peserta.id', 'ASC')
            ->findAll();

        $tiketModel = new PerjalananDinasTiketModel();
        $hotelModel = new PerjalananDinasHotelModel();
        foreach ($rows as &$r) {
            $r['tiket'] = $tiketModel->getByPeserta($r['id']);
            $r['hotel'] = $hotelModel->getByPeserta($r['id']);
        }
        unset($r);

        return $rows;
    }

    /**
     * Reset status peserta yang bertaut dengan baris pemasukan yang dihapus di modul keuangan.
     * Menjamin integritas dua arah sehingga tidak ada peserta yang menggantung dengan ID pemasukan fiktif.
     */
    public function resetStatusDariPemasukan(array $pemasukanIds): int
    {
        $pemasukanIds = array_values(array_filter(array_map('intval', $pemasukanIds), fn($v) => $v > 0));
        if (empty($pemasukanIds)) return 0;

        $pesertaList = $this->whereIn('pemasukan_id', $pemasukanIds)->findAll();
        foreach ($pesertaList as $p) {
            $this->update($p['id'], [
                'status_lunas'   => 'belum',
                'jumlah_disetor' => 0,
                'tanggal_lunas'  => null,
                'pemasukan_id'   => null,
            ]);
        }

        return count($pesertaList);
    }

    /**
     * Sinkronkan pembaruan nama master pegawai ke seluruh riwayat perjalanan dinas dan pemasukan setoran.
     */
    public function perbaruiNamaPegawai(int $pegawaiId, string $namaBaru): int
    {
        $namaBaru = trim($namaBaru);
        if ($namaBaru === '' || $pegawaiId <= 0) return 0;

        // 1. Update nama_peserta di seluruh riwayat perjalanan_dinas_peserta
        $this->where('pegawai_id', $pegawaiId)->set(['nama_peserta' => $namaBaru])->update();

        // 2. Update sumber di tabel pemasukan yang bertaut dengan peserta milik pegawai ini
        $pesertaList = $this->where('pegawai_id', $pegawaiId)->where('pemasukan_id IS NOT NULL')->findAll();
        $pemasukanModel = new PemasukanModel();
        foreach ($pesertaList as $p) {
            if (!empty($p['pemasukan_id'])) {
                $pemasukanModel->update($p['pemasukan_id'], ['sumber' => $namaBaru]);
            }
        }

        return count($pesertaList);
    }
}
