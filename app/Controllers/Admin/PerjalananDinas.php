<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PerjalananDinasModel;
use App\Models\PerjalananDinasPesertaModel;
use App\Models\PerjalananDinasTiketModel;
use App\Models\PerjalananDinasHotelModel;
use App\Models\PegawaiModel;
use App\Models\NotifikasiModel;

class PerjalananDinas extends BaseController
{
    protected $tripModel;
    protected $pesertaModel;
    protected $tiketModel;
    protected $hotelModel;
    protected $pegawaiModel;
    protected $notifikasiModel;

    private const NOMINAL_PESERTA = ['uang_harian', 'meeting_fullboard', 'meeting_fullday', 'uang_representasi', 'transport_lokal', 'bbm'];
    private const NOMINAL_TIKET   = ['harga_tiket'];
    private const NOMINAL_HOTEL   = ['total_bill', 'total_biaya_30persen'];

    public function __construct()
    {
        $this->tripModel       = new PerjalananDinasModel();
        $this->pesertaModel    = new PerjalananDinasPesertaModel();
        $this->tiketModel      = new PerjalananDinasTiketModel();
        $this->hotelModel      = new PerjalananDinasHotelModel();
        $this->pegawaiModel    = new PegawaiModel();
        $this->notifikasiModel = new NotifikasiModel();
    }

    /** Normalisasi nominal dari format "1.000.000" (id-ID) atau string decimal DB. */
    private function sanitizeNominal($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        if (is_numeric($raw)) return (float)$raw;
        $s = (string)$raw;
        if (preg_match('/^([\d.]+),(\d{1,2})$/', $s, $m)) {
            return (float)(str_replace('.', '', $m[1]) . '.' . $m[2]);
        }
        return (float)str_replace(['.', ','], ['', ''], $s);
    }

    /** Filter dari query string. 'tahun' sengaja TIDAK default ke tahun berjalan — kalau
     *  dipaksa default, data di tahun lain jadi hilang tanpa keterangan jelas ("tidak ada
     *  data" padahal cuma tersaring). Kosong berarti semua tahun. */
    private function ambilFilterGet(): array
    {
        $perPage = (int)($this->request->getGet('per_page') ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;

        return [
            'bulan'    => $this->request->getGet('bulan'),
            'tahun'    => $this->request->getGet('tahun'),
            'status'   => $this->request->getGet('status'),
            'search'   => $this->request->getGet('search'),
            'page'     => max(1, (int)($this->request->getGet('page') ?? 1)),
            'per_page' => $perPage,
        ];
    }

    public function index(): string
    {
        $notifCount = $this->notifikasiModel->countUnread();

        return view('admin/perjalanan_dinas', [
            'notifCount'  => $notifCount,
            'pegawaiList' => $this->pegawaiModel->getAktifList(),
            'tahunList'   => $this->tripModel->getAvailableYears(),
        ]);
    }

    /** Dipanggil via fetch() dari filter/search/pagination di halaman index — tabel datar
     *  (1 baris = 1 peserta per trip), bukan lagi kartu bertingkat. */
    public function ajaxList()
    {
        $filters    = $this->ambilFilterGet();
        $total      = $this->pesertaModel->countFilteredPerjalananDinas($filters);
        $totalPages = max(1, (int)ceil($total / $filters['per_page']));
        $page       = min(max(1, $filters['page']), $totalPages);
        $offset     = ($page - 1) * $filters['per_page'];

        $rows = $this->pesertaModel->getFilteredPerjalananDinas($filters, $filters['per_page'], $offset);

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $filters['per_page'],
            'total_pages' => $totalPages,
            'offset'      => $offset,
        ]);
    }

    // ── CRUD Header Perjalanan Dinas ──────────────────────────────────────────────

    public function store()
    {
        $rules = [
            'maksud'              => 'required|min_length[3]',
            'tanggal_surat_tugas' => 'required|valid_date',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $id = $this->tripModel->insert([
            'maksud'              => $this->request->getPost('maksud'),
            'no_surat_tugas'      => $this->request->getPost('no_surat_tugas'),
            'tanggal_surat_tugas' => $this->request->getPost('tanggal_surat_tugas'),
            'kode_mak'            => $this->request->getPost('kode_mak'),
            'no_spm'              => $this->request->getPost('no_spm'),
        ]);

        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Perjalanan dinas berhasil ditambahkan. Sekarang tambahkan peserta.']);
    }

    public function update($id)
    {
        $rules = [
            'maksud'              => 'required|min_length[3]',
            'tanggal_surat_tugas' => 'required|valid_date',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $this->tripModel->update($id, [
            'maksud'              => $this->request->getPost('maksud'),
            'no_surat_tugas'      => $this->request->getPost('no_surat_tugas'),
            'tanggal_surat_tugas' => $this->request->getPost('tanggal_surat_tugas'),
            'kode_mak'            => $this->request->getPost('kode_mak'),
            'no_spm'              => $this->request->getPost('no_spm'),
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Perjalanan dinas berhasil diupdate']);
    }

    public function delete($id)
    {
        foreach ($this->pesertaModel->getByPerjalanan((int)$id) as $p) {
            $this->hapusPesertaBersih((int)$p['id']);
        }
        $this->tripModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Perjalanan dinas & seluruh datanya berhasil dihapus']);
    }

    // ── Peserta (per orang, termasuk tiket & hotelnya) ────────────────────────────

    public function storePeserta()
    {
        $tripId = (int)$this->request->getPost('perjalanan_dinas_id');
        if (!$tripId || !$this->tripModel->find($tripId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Perjalanan dinas tidak ditemukan']);
        }

        $data = $this->pesertaDataFromPost($tripId);
        if ($data === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pilih pegawai dari daftar (tambahkan dulu lewat "Kelola Pegawai" kalau belum ada)']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $pesertaId = $this->pesertaModel->insert($data);
        $this->simpanTiketHotel($pesertaId);
        $this->pesertaModel->recalculate($pesertaId);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyimpan peserta (kesalahan database)']);
        }

        return $this->response->setJSON(['success' => true, 'id' => $pesertaId, 'message' => 'Peserta berhasil ditambahkan']);
    }

    public function updatePeserta($id)
    {
        $peserta = $this->pesertaModel->find((int)$id);
        if (!$peserta) {
            return $this->response->setJSON(['success' => false, 'message' => 'Peserta tidak ditemukan']);
        }

        $data = $this->pesertaDataFromPost((int)$peserta['perjalanan_dinas_id']);
        if ($data === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pilih pegawai dari daftar (tambahkan dulu lewat "Kelola Pegawai" kalau belum ada)']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->pesertaModel->update($id, $data);
        $this->simpanTiketHotel((int)$id);
        $this->pesertaModel->recalculate((int)$id);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyimpan peserta (kesalahan database)']);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Peserta berhasil diupdate']);
    }

    public function deletePeserta($id)
    {
        $this->hapusPesertaBersih((int)$id);
        return $this->response->setJSON(['success' => true, 'message' => 'Peserta berhasil dihapus']);
    }

    /** Nama peserta SELALU snapshot dari data master pegawai (bukan input bebas) — supaya
     *  penautan ke rekap Dana Taktis per pegawai selalu akurat, mirror dropdown "NAMA" di
     *  spreadsheet sumber. Return null kalau pegawai_id tidak valid. */
    private function pesertaDataFromPost(int $tripId): ?array
    {
        $pegawaiId = (int)$this->request->getPost('pegawai_id');
        $pegawai   = $pegawaiId ? $this->pegawaiModel->find($pegawaiId) : null;
        if (!$pegawai) return null;

        $data = [
            'perjalanan_dinas_id' => $tripId,
            'pegawai_id'          => $pegawaiId,
            'nama_peserta'        => $pegawai['nama'],
        ];
        foreach (self::NOMINAL_PESERTA as $key) {
            $data[$key] = $this->sanitizeNominal($this->request->getPost($key));
        }
        return $data;
    }

    /** Ganti seluruh baris tiket & blok hotel milik satu peserta dengan yang baru dikirim dari form. */
    private function simpanTiketHotel(int $pesertaId): void
    {
        $tikets = $this->request->getPost('tiket') ?? [];
        $tikets = array_values(array_filter($tikets, fn($t) => !empty(array_filter((array)$t, fn($v) => $v !== null && $v !== ''))));
        foreach ($tikets as &$t) {
            foreach (self::NOMINAL_TIKET as $key) {
                $t[$key] = $this->sanitizeNominal($t[$key] ?? 0);
            }
            $t = array_intersect_key($t, array_flip(['maskapai', 'arah', 'no_tiket', 'kode_booking', 'no_penerbangan', 'tempat_asal', 'tempat_tujuan', 'tanggal_terbang', 'harga_tiket']));
        }
        unset($t);
        $this->tiketModel->replaceForPeserta($pesertaId, $tikets);

        $hotel = $this->request->getPost('hotel');
        if ($hotel) {
            foreach (self::NOMINAL_HOTEL as $key) {
                $hotel[$key] = $this->sanitizeNominal($hotel[$key] ?? 0);
            }
            $hotel = array_intersect_key($hotel, array_flip(['nama_hotel', 'alamat_hotel', 'telp_hotel', 'checkin', 'checkout', 'total_bill', 'no_kamar', 'no_invoice', 'total_biaya_30persen']));
        }
        $this->hotelModel->replaceForPeserta($pesertaId, $hotel);
    }

    /** Hapus 1 peserta beserta tiket/hotelnya; batalkan dulu status lunas kalau ada supaya pemasukan otomatisnya ikut terhapus. */
    private function hapusPesertaBersih(int $pesertaId): void
    {
        $peserta = $this->pesertaModel->find($pesertaId);
        if (!$peserta) return;
        if ($peserta['status_lunas'] === 'lunas') {
            $this->pesertaModel->batalkanLunas($pesertaId);
        }
        $this->tiketModel->where('peserta_id', $pesertaId)->delete();
        $this->hotelModel->where('peserta_id', $pesertaId)->delete();
        $this->pesertaModel->delete($pesertaId);
    }

    // ── Status Lunas (sinkron ke Pemasukan) ───────────────────────────────────────

    public function toggleLunas($pesertaId)
    {
        $aksi = $this->request->getPost('aksi'); // 'lunas' | 'batal'
        $peserta = $this->pesertaModel->find((int)$pesertaId);
        if (!$peserta) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data peserta tidak ditemukan']);
        }

        if ($aksi === 'batal') {
            $ok = $this->pesertaModel->batalkanLunas((int)$pesertaId);
            $pesan = 'Status lunas dibatalkan, pemasukan otomatis ikut dihapus';
        } else {
            $tanggal = $this->request->getPost('tanggal_lunas') ?: date('Y-m-d');
            $ok = $this->pesertaModel->tandaiLunas((int)$pesertaId, $tanggal);
            $pesan = 'Setoran Dana Taktis ditandai lunas & tercatat sebagai Pemasukan';
        }

        if (!$ok) {
            return $this->response->setJSON(['success' => false, 'message' => 'Status sudah sesuai, tidak ada perubahan']);
        }
        return $this->response->setJSON(['success' => true, 'message' => $pesan]);
    }

    // ── Rekap Dana Taktis per pegawai ──────────────────────────────────────────────

    public function danaTaktis(): string
    {
        return view('admin/dana_taktis', [
            'notifCount'  => $this->notifikasiModel->countUnread(),
            'pegawaiList' => $this->pegawaiModel->getAktifList(),
        ]);
    }

    /** Daftar Dana Taktis per peserta-per-trip, dipaginasi & difilter — dipakai di kartu
     *  "Dana Taktis" pada halaman ini dan di dashboard admin (lihat dashboardDanaTaktisList()
     *  di Admin\Dashboard). Bukan agregat per pegawai supaya status per-trip tetap kelihatan
     *  (mis. satu pegawai 3 perjalanan dinas, cuma 1 yang sudah lunas). */
    public function danaTaktisList()
    {
        $filters = [
            'search' => $this->request->getGet('search'),
            'status' => $this->request->getGet('status'),
            'tahun'  => $this->request->getGet('tahun'),
            'bulan'  => $this->request->getGet('bulan'),
        ];
        $page    = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = (int)($this->request->getGet('per_page') ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;

        $total      = $this->pesertaModel->countFilteredDanaTaktis($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = min($page, $totalPages);

        $rows = $this->pesertaModel->getFilteredDanaTaktis($filters, $perPage, ($page - 1) * $perPage);

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
    }

    public function danaTaktisData($pegawaiId)
    {
        $pegawai = $this->pegawaiModel->find((int)$pegawaiId);
        if (!$pegawai) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pegawai tidak ditemukan']);
        }
        $rekap = $this->pesertaModel->getRekapDanaTaktisByPegawai((int)$pegawaiId);
        return $this->response->setJSON(['success' => true, 'pegawai' => $pegawai] + $rekap);
    }

    // ── Master Data Pegawai (dipakai sebagai modal kecil di halaman ini) ──────────

    public function pegawaiList()
    {
        return $this->response->setJSON(['success' => true, 'data' => $this->pegawaiModel->orderBy('nama', 'ASC')->findAll()]);
    }

    public function pegawaiStore()
    {
        $rules = ['nama' => 'required|min_length[2]'];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }
        $id = $this->pegawaiModel->insert([
            'nama'  => $this->request->getPost('nama'),
            'nip'   => $this->request->getPost('nip'),
            'aktif' => 1,
        ]);
        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Pegawai berhasil ditambahkan']);
    }

    public function pegawaiUpdate($id)
    {
        $rules = ['nama' => 'required|min_length[2]'];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }
        $this->pegawaiModel->update($id, [
            'nama'  => $this->request->getPost('nama'),
            'nip'   => $this->request->getPost('nip'),
            'aktif' => $this->request->getPost('aktif') !== null ? (int)$this->request->getPost('aktif') : 1,
        ]);
        return $this->response->setJSON(['success' => true, 'message' => 'Pegawai berhasil diupdate']);
    }

    public function pegawaiDelete($id)
    {
        // Nonaktifkan saja, bukan dihapus permanen — supaya riwayat peserta perjalanan
        // dinas lama (yang menyimpan pegawai_id ini) tidak kehilangan rujukan.
        $this->pegawaiModel->update($id, ['aktif' => 0]);
        return $this->response->setJSON(['success' => true, 'message' => 'Pegawai dinonaktifkan']);
    }
}
