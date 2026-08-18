<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RencanaPemasukanModel;
use App\Models\RencanaPengeluaranModel;
use App\Models\NotifikasiModel;

class RencanaKeuangan extends BaseController
{
    protected $rencanaPemasukanModel;
    protected $rencanaPengeluaranModel;
    protected $notifikasiModel;

    public function __construct()
    {
        $this->rencanaPemasukanModel   = new RencanaPemasukanModel();
        $this->rencanaPengeluaranModel = new RencanaPengeluaranModel();
        $this->notifikasiModel         = new NotifikasiModel();
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

    public function index(): string
    {
        // Dibatasi 200 baris terbaru per tabel — tanpa batas ini, riwayat rencana yang sudah
        // terealisasi (tidak pernah dihapus/diarsipkan) akan terus menumpuk dan me-render
        // tabel HTML yang makin berat setiap tahun. Rencana AKTIF nyaris selalu masuk 200
        // ini karena baru dibuat/belum lama, jadi tidak memengaruhi alur kerja sehari-hari.
        $limitRencana = 200;
        $notifCount = $this->notifikasiModel->countUnread();
        $totalPemasukan   = $this->rencanaPemasukanModel->countAllResults();
        $totalPengeluaran = $this->rencanaPengeluaranModel->countAllResults();

        return view('admin/rencana_keuangan', [
            'rencanaPemasukan'   => $this->rencanaPemasukanModel->orderBy('id', 'DESC')->findAll($limitRencana),
            'rencanaPengeluaran' => $this->rencanaPengeluaranModel->orderBy('id', 'DESC')->findAll($limitRencana),
            'totalPemasukanAll'   => $totalPemasukan,
            'totalPengeluaranAll' => $totalPengeluaran,
            'limitRencana'        => $limitRencana,
            'notifCount'         => $notifCount,
        ]);
    }

    /**
     * Dua form berbeda mengirim field ini dengan nama berbeda: drawer "Input Cepat"
     * (layouts/admin.php) mengirim 'tanggal'/'jumlah', form halaman ini sendiri
     * (rencana_keuangan.php) mengirim 'tanggal_rencana'/'jumlah_rencana'. Terima keduanya.
     */
    private function getPostEither(string $a, string $b)
    {
        $val = $this->request->getPost($a);
        return ($val !== null && $val !== '') ? $val : $this->request->getPost($b);
    }

    // ── Rencana Pemasukan ────────────────────────────────────────────────────────

    public function storeRencanaPemasukan()
    {
        $rules = [
            'bukti' => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $data = [
            'tanggal_rencana' => $this->getPostEither('tanggal_rencana', 'tanggal'),
            'kategori'        => $this->request->getPost('kategori'),
            'jumlah_rencana'  => $this->sanitizeNominal($this->getPostEither('jumlah_rencana', 'jumlah')),
            'keterangan'      => $this->request->getPost('keterangan'),
            'status'          => 'aktif',
            'file_bukti'      => $this->simpanBukti(),
        ];
        $id = $this->rencanaPemasukanModel->insert($data);
        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Rencana pemasukan ditambahkan']);
    }

    public function updateRencanaPemasukan($id)
    {
        $data = $this->request->getPost();
        if (isset($data['jumlah_rencana'])) $data['jumlah_rencana'] = $this->sanitizeNominal($data['jumlah_rencana']);
        $this->rencanaPemasukanModel->update($id, $data);
        return $this->response->setJSON(['success' => true, 'message' => 'Rencana pemasukan diupdate']);
    }

    public function deleteRencanaPemasukan($id)
    {
        $row = $this->rencanaPemasukanModel->find($id);
        $this->hapusFileBukti($row['file_bukti'] ?? null);
        $this->rencanaPemasukanModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Rencana pemasukan dihapus']);
    }

    public function realisasiPemasukan($id)
    {
        $jumlahAktual = $this->sanitizeNominal($this->request->getPost('jumlah_diterima'));
        if ($jumlahAktual <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nominal yang diterima harus lebih dari 0']);
        }
        $scope  = $this->request->getPost('scope') === 'sebagian' ? 'sebagian' : 'semua';
        $result = $this->rencanaPemasukanModel->realisasi($id, $jumlahAktual, $scope);

        if ($result) {
            $this->notifikasiModel->tambahNotifikasi('Rencana pemasukan #' . $id . ' telah direalisasikan.', 'rencana', 'rencana');
            return $this->response->setJSON(['success' => true, 'message' => 'Rencana berhasil direalisasikan']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Rencana tidak ditemukan']);
    }

    // ── Rencana Pengeluaran ──────────────────────────────────────────────────────

    public function storeRencanaPengeluaran()
    {
        $rules = [
            'bukti' => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $data = [
            'tanggal_rencana' => $this->getPostEither('tanggal_rencana', 'tanggal'),
            'kategori'        => $this->request->getPost('kategori'),
            'jumlah_rencana'  => $this->sanitizeNominal($this->getPostEither('jumlah_rencana', 'jumlah')),
            'keterangan'      => $this->request->getPost('keterangan'),
            'status'          => 'aktif',
            'file_bukti'      => $this->simpanBukti(),
        ];
        $id = $this->rencanaPengeluaranModel->insert($data);
        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Rencana pengeluaran ditambahkan']);
    }

    public function updateRencanaPengeluaran($id)
    {
        $data = $this->request->getPost();
        if (isset($data['jumlah_rencana'])) $data['jumlah_rencana'] = $this->sanitizeNominal($data['jumlah_rencana']);
        $this->rencanaPengeluaranModel->update($id, $data);
        return $this->response->setJSON(['success' => true, 'message' => 'Rencana pengeluaran diupdate']);
    }

    public function deleteRencanaPengeluaran($id)
    {
        $row = $this->rencanaPengeluaranModel->find($id);
        $this->hapusFileBukti($row['file_bukti'] ?? null);
        $this->rencanaPengeluaranModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Rencana pengeluaran dihapus']);
    }

    public function realisasiPengeluaran($id)
    {
        $jumlahAktual = $this->sanitizeNominal($this->request->getPost('jumlah'));
        if ($jumlahAktual <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nominal yang dikeluarkan harus lebih dari 0']);
        }
        $scope  = $this->request->getPost('scope') === 'sebagian' ? 'sebagian' : 'semua';
        $result = $this->rencanaPengeluaranModel->realisasi($id, $jumlahAktual, $scope);

        if ($result) {
            $this->notifikasiModel->tambahNotifikasi('Rencana pengeluaran #' . $id . ' telah direalisasikan.', 'rencana', 'rencana');
            return $this->response->setJSON(['success' => true, 'message' => 'Rencana berhasil direalisasikan']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Rencana tidak ditemukan']);
    }
}
