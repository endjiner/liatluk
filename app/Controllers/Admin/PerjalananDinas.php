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

        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;

        return [
            'bulan'    => $this->request->getGet('bulan'),
            'tahun'    => $this->request->getGet('tahun'),
            'status'   => $this->request->getGet('status'),
            'search'   => $this->request->getGet('search'),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Perjalanan Dinas sekarang jadi tab di admin/dashboard, bukan halaman tersendiri —
     * redirect supaya tautan/bookmark lama tetap jalan.
     */
    public function index()
    {
        return redirect()->to(base_url('admin/dashboard#perjadin'));
    }

    /** Dipanggil via fetch() dari filter/search/pagination di halaman index — tabel datar
     *  (1 baris = 1 peserta per trip), bukan lagi kartu bertingkat. */
    public function ajaxList()
    {
        $filters = $this->ambilFilterGet();
        $result  = $this->pesertaModel->getPaginatedPerjalananDinas($filters);

        return $this->response->setJSON(['success' => true] + $result);
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
            'no_pd'               => trim((string)$this->request->getPost('no_pd')) ?: null,
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
            'no_pd'               => trim((string)$this->request->getPost('no_pd')) ?: null,
            'maksud'              => $this->request->getPost('maksud'),
            'no_surat_tugas'      => $this->request->getPost('no_surat_tugas'),
            'tanggal_surat_tugas' => $this->request->getPost('tanggal_surat_tugas'),
            'kode_mak'            => $this->request->getPost('kode_mak'),
            'no_spm'              => $this->request->getPost('no_spm'),
        ]);

        // Sinkronisasi otomatis ke seluruh pemasukan peserta yang sudah lunas/sebagian di trip ini
        // agar keterangan & metadata JSON Asikkekku selalu sinkron
        $pesertaList = $this->pesertaModel->getByPerjalanan((int)$id);
        foreach ($pesertaList as $p) {
            if (!empty($p['pemasukan_id']) || in_array($p['status_lunas'], ['lunas', 'sebagian'], true)) {
                $this->pesertaModel->sinkronPemasukan((int)$p['id']);
            }
        }

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
        $this->terapkanStatusLunasDariInput($pesertaId);

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
        $this->terapkanStatusLunasDariInput((int)$id);

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

    /** Terapkan status setoran Dana Taktis yang dipilih langsung di form Tambah/Edit Peserta
     *  (bukan cuma lewat aksi "Tandai Lunas" terpisah di tabel) — supaya admin bisa catat
     *  rekap sekaligus saat input data pertama kali, tanpa harus mencari barisnya lagi
     *  setelah tersimpan. Field status_lunas_input yang tidak dikirim/tidak valid berarti
     *  tidak ada perubahan status. */
    private function terapkanStatusLunasDariInput(int $pesertaId): void
    {
        $status = $this->request->getPost('status_lunas_input');
        if (!in_array($status, ['belum', 'sebagian', 'lunas'], true)) {
            // Jika status_lunas_input tidak dikirim di form, namun peserta sudah punya pemasukan_id
            // (misal edit data peserta), kita tetap sinkronkan agar nama sumber/nominal terbaru terupdate!
            $peserta = $this->pesertaModel->find($pesertaId);
            if ($peserta && !empty($peserta['pemasukan_id'])) {
                $this->pesertaModel->sinkronPemasukan($pesertaId);
            }
            return;
        }

        $peserta = $this->pesertaModel->find($pesertaId);
        if (!$peserta) return;

        if ($status === 'belum') {
            if ($peserta['status_lunas'] !== 'belum') {
                $this->pesertaModel->batalkanLunas($pesertaId);
            }
            return;
        }

        $tanggal = $this->request->getPost('tanggal_setoran') ?: date('Y-m-d');
        $jumlah  = $status === 'sebagian' ? $this->sanitizeNominal($this->request->getPost('jumlah_disetor')) : null;
        $this->pesertaModel->tandaiLunas($pesertaId, $tanggal, $status, $jumlah);
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
        if ($peserta['status_lunas'] !== 'belum') {
            $this->pesertaModel->batalkanLunas($pesertaId);
        }
        $this->tiketModel->where('peserta_id', $pesertaId)->delete();
        $this->hotelModel->where('peserta_id', $pesertaId)->delete();
        $this->pesertaModel->delete($pesertaId);
    }

    // ── Status Lunas (sinkron ke Pemasukan) ───────────────────────────────────────

    public function toggleLunas($pesertaId)
    {
        $aksi = $this->request->getPost('aksi'); // 'lunas' | 'sebagian' | 'batal'
        $peserta = $this->pesertaModel->find((int)$pesertaId);
        if (!$peserta) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data peserta tidak ditemukan']);
        }

        if ($aksi === 'batal') {
            $hasil = $this->pesertaModel->batalkanLunas((int)$pesertaId);
            $ok    = $hasil['ok'];
            $pesan = $hasil['pemasukan_dihapus']
                ? 'Status setoran dibatalkan, pemasukan otomatis ikut dihapus'
                : 'Status setoran dibatalkan (pemasukan yang tertaut tetap ada di pembukuan)';
        } elseif ($aksi === 'sebagian') {
            $tanggal = $this->request->getPost('tanggal_lunas') ?: date('Y-m-d');
            $jumlah  = $this->sanitizeNominal($this->request->getPost('jumlah_disetor'));
            $ok = $this->pesertaModel->tandaiLunas((int)$pesertaId, $tanggal, 'sebagian', $jumlah);
            $pesan = 'Setoran sebagian dicatat sebagai Pemasukan';
        } else {
            $tanggal = $this->request->getPost('tanggal_lunas') ?: date('Y-m-d');
            $ok = $this->pesertaModel->tandaiLunas((int)$pesertaId, $tanggal, 'lunas');
            $pesan = 'Setoran Dana Taktis ditandai lunas & tercatat sebagai Pemasukan';
        }

        if (!$ok) {
            return $this->response->setJSON(['success' => false, 'message' => 'Status sudah sesuai, tidak ada perubahan']);
        }
        return $this->response->setJSON(['success' => true, 'message' => $pesan]);
    }

    // ── Rekap Dana Taktis per pegawai ──────────────────────────────────────────────

    /**
     * Dana Taktis sekarang jadi tab di admin/dashboard, bukan halaman tersendiri —
     * redirect supaya tautan/bookmark lama tetap jalan.
     */
    public function danaTaktis()
    {
        return redirect()->to(base_url('admin/dashboard#dana-taktis'));
    }

    /** Daftar Dana Taktis per peserta-per-trip, dipaginasi & difilter — dipakai di kartu
     *  "Dana Taktis" pada halaman ini dan di dashboard admin (lihat dashboardDanaTaktisList()
     *  di Admin\Dashboard). Bukan agregat per pegawai supaya status per-trip tetap kelihatan
     *  (mis. satu pegawai 3 perjalanan dinas, cuma 1 yang sudah lunas). */
    public function danaTaktisList()
    {
        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;

        $filters = [
            'search'   => $this->request->getGet('search'),
            'status'   => $this->request->getGet('status'),
            'tahun'    => $this->request->getGet('tahun'),
            'bulan'    => $this->request->getGet('bulan'),
            'page'     => $page,
            'per_page' => (int)($this->request->getGet('per_page') ?? 10),
        ];

        $result = $this->pesertaModel->getPaginatedDanaTaktis($filters);

        return $this->response->setJSON(['success' => true] + $result);
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
        $namaBaru = trim((string)$this->request->getPost('nama'));
        $this->pegawaiModel->update($id, [
            'nama'  => $namaBaru,
            'nip'   => $this->request->getPost('nip'),
            'aktif' => $this->request->getPost('aktif') !== null ? (int)$this->request->getPost('aktif') : 1,
        ]);
        // Sinkronkan nama baru ke seluruh riwayat peserta & sumber pemasukan setoran terkait
        $this->pesertaModel->perbaruiNamaPegawai((int)$id, $namaBaru);

        return $this->response->setJSON(['success' => true, 'message' => 'Pegawai berhasil diupdate dan riwayat disinkronkan']);
    }

    public function pegawaiDelete($id)
    {
        // Hapus permanen HANYA kalau pegawai ini tidak pernah tercatat sebagai peserta
        // perjalanan dinas manapun — kalau masih ada, dihapus akan bikin baris peserta
        // lama itu yatim piatu (pegawai_id nunjuk ke baris yang sudah tidak ada, lihat
        // app:sinkron-sumber-setoran yang justru dibuat untuk mendeteksi masalah ini).
        // Untuk pegawai yang punya riwayat, sarankan nonaktifkan saja lewat pegawaiNonaktifkan().
        $terpakai = $this->pesertaModel->where('pegawai_id', $id)->countAllResults();
        if ($terpakai > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Tidak bisa dihapus — pegawai ini masih tercatat di {$terpakai} riwayat perjalanan dinas. Nonaktifkan saja supaya riwayatnya tetap utuh.",
            ]);
        }
        $this->pegawaiModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Pegawai berhasil dihapus']);
    }

    public function pegawaiNonaktifkan($id)
    {
        $this->pegawaiModel->update($id, ['aktif' => 0]);
        return $this->response->setJSON(['success' => true, 'message' => 'Pegawai dinonaktifkan']);
    }
}
