<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\NotifikasiModel;
use App\Models\PengaturanModel;

class DataKeuangan extends BaseController
{
    protected $pemasukanModel;
    protected $pengeluaranModel;
    protected $notifikasiModel;
    protected $pengaturanModel;

    public function __construct()
    {
        $this->pemasukanModel  = new PemasukanModel();
        $this->pengeluaranModel = new PengeluaranModel();
        $this->notifikasiModel = new NotifikasiModel();
        $this->pengaturanModel = new PengaturanModel();
    }

    /**
     * Normalisasi input nominal (server-side defense-in-depth).
     * Menerima "1.000.000" (format id-ID), "1000000", "1000000.50", "1,000,000",
     * mengembalikan angka murni sebagai integer/float untuk disimpan ke DB.
     * BUG PENYEBAB SEBELUMNYA: value dari DB "1000000.00" bila di-strip semua non-digit
     * secara membabi buta menjadi "100000000" (×100). Fungsi ini menangani decimal
     * dengan benar sehingga aman dipanggil di semua jalur input.
     */
    private function sanitizeNominal($raw): float
    {
        if ($raw === null || $raw === '') return 0.0;
        if (is_numeric($raw)) return (float)$raw;
        $s = (string)$raw;
        // Buang decimal trailing yang formatnya id-ID pakai "," (mis "1000000,50")
        if (preg_match('/^([\d.]+),(\d{1,2})$/', $s, $m)) {
            return (float)(str_replace('.', '', $m[1]) . '.' . $m[2]);
        }
        // Format id-ID "1.000.000" — titik = thousand separator (buang semua)
        return (float)str_replace(['.', ','], ['', ''], $s);
    }

    /**
     * Data Keuangan sekarang jadi tab "Transaksi" di admin/dashboard, bukan halaman
     * tersendiri — redirect supaya tautan/bookmark lama tetap jalan.
     */
    public function index()
    {
        return redirect()->to(base_url('admin/dashboard'));
    }

    /**
     * AJAX endpoint: daftar transaksi gabungan (admin) dengan filter & pagination.
     * GET params: show_pemasukan, show_pengeluaran, search, bulan, tahun, per_page, page
     *
     * Penomoran baris SELALU ascending dari transaksi paling awal (nomor 1 = tertua). Kalau
     * $page tidak dikirim, default-nya HALAMAN TERAKHIR (transaksi terbaru) supaya tetap
     * yang pertama terlihat, walau nomor barisnya tetap urut dari awal. Query tetap diambil
     * DESC (termurah untuk data terbaru) lalu dibalik — jadi halaman default tetap murah,
     * yang mahal cuma saat sengaja menjelajah ke histori paling awal.
     */
    public function ajaxList()
    {
        $filters = [
            'search'   => $this->request->getGet('search'),
            'bulan'    => $this->request->getGet('bulan'),
            'tahun'    => $this->request->getGet('tahun'),
            'kategori' => [],
        ];

        $showP = $this->request->getGet('show_pemasukan');
        $showE = $this->request->getGet('show_pengeluaran');
        $showPemasukan   = ($showP === null || $showP === '1');
        $showPengeluaran = ($showE === null || $showE === '1');

        $pageParam = $this->request->getGet('page');
        $page = ($pageParam !== null && $pageParam !== '') ? max(1, (int)$pageParam) : null;
        $perPage = (int)($this->request->getGet('per_page') ?? 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) $perPage = 15;

        $total = ($showPemasukan ? $this->pemasukanModel->countFiltered($filters) : 0)
               + ($showPengeluaran ? $this->pengeluaranModel->countFiltered($filters) : 0);

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = $page ?? $totalPages;
        $page = max(1, min($page, $totalPages));

        $startNum = ($page - 1) * $perPage + 1;
        $endNum   = min($total, $page * $perPage);
        $take     = max(0, $endNum - $startNum + 1);

        $descOffset = max(0, $total - $endNum);
        $fetchLimit = $descOffset + $take;

        $data = [];
        if ($showPemasukan) {
            foreach ($this->pemasukanModel->getFiltered($filters, $fetchLimit, 0) as $r) {
                $data[] = array_merge($r, ['tipe' => 'pemasukan']);
            }
        }
        if ($showPengeluaran) {
            foreach ($this->pengeluaranModel->getFiltered($filters, $fetchLimit, 0) as $r) {
                $data[] = array_merge($r, ['tipe' => 'pengeluaran']);
            }
        }

        // Urutkan berdasarkan tanggal terbaru, secondary id terbaru (DESC)
        usort($data, function ($a, $b) {
            $t = strtotime($b['tanggal']) - strtotime($a['tanggal']);
            if ($t !== 0) return $t;
            return ((int)($b['id'] ?? 0)) - ((int)($a['id'] ?? 0));
        });

        $slice = array_reverse(array_slice($data, $descOffset, $take));
        foreach ($slice as $i => &$row) {
            $row['nomor'] = $startNum + $i;
        }
        unset($row);

        return $this->response->setJSON([
            'data'        => $slice,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
    }

    // ── Pemasukan CRUD ──────────────────────────────────────────────────────────

    public function storePemasukan()
    {
        // Normalisasi nominal SEBELUM validasi supaya numeric rule tidak menolak "1.000.000"
        $jumlahBersih = $this->sanitizeNominal($this->request->getPost('jumlah'));
        $diterimaBersih = $this->request->getPost('jumlah_diterima')
            ? $this->sanitizeNominal($this->request->getPost('jumlah_diterima'))
            : $jumlahBersih;
        $_POST['jumlah'] = $jumlahBersih;
        $_POST['jumlah_diterima'] = $diterimaBersih;

        $rules = [
            'tanggal'  => 'required|valid_date',
            'kategori' => 'required|min_length[2]',
            'jumlah'   => 'required|numeric|greater_than[0]',
            'bukti'    => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $data = [
            'tanggal'         => $this->request->getPost('tanggal'),
            'kategori'        => $this->request->getPost('kategori'),
            'jumlah'          => $jumlahBersih,
            'jumlah_diterima' => $diterimaBersih,
            'status_dana'     => $this->request->getPost('status_dana') ?? 'belum_diterima',
            'sumber'          => $this->request->getPost('sumber'),
            'keterangan'      => $this->request->getPost('keterangan'),
            'file_bukti'      => $this->simpanBukti(),
            'catatan_internal' => $this->request->getPost('catatan_internal'),
        ];

        $id = $this->pemasukanModel->insert($data);

        // Notif transaksi besar
        $this->cekNotifTransaksiBesar($jumlahBersih, 'pemasukan');

        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Pemasukan berhasil ditambahkan']);
    }

    public function updatePemasukan($id)
    {
        $data = $this->request->getPost();
        unset($data['_method']);

        // Normalisasi nominal SEBELUM validasi (sama seperti storePemasukan) supaya rule
        // 'numeric' membaca nilai yang sudah bersih, bukan format id-ID mentah "1.000.000".
        if (isset($data['jumlah'])) $data['jumlah'] = $this->sanitizeNominal($data['jumlah']);
        if (isset($data['jumlah_diterima'])) $data['jumlah_diterima'] = $this->sanitizeNominal($data['jumlah_diterima']);
        $_POST['jumlah'] = $data['jumlah'] ?? null;

        $rules = [
            'tanggal'  => 'required|valid_date',
            'kategori' => 'required|min_length[2]',
            'jumlah'   => 'required|numeric|greater_than[0]',
            'bukti'    => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $bukti = $this->simpanBukti();
        if ($bukti) {
            $lama = $this->pemasukanModel->find($id);
            $this->hapusFileBukti($lama['file_bukti'] ?? null);
            $data['file_bukti'] = $bukti;
        }

        $this->pemasukanModel->update($id, $data);
        return $this->response->setJSON(['success' => true, 'message' => 'Pemasukan berhasil diupdate']);
    }

    public function deletePemasukan($id)
    {
        $row = $this->pemasukanModel->find($id);
        $this->hapusFileBukti($row['file_bukti'] ?? null);
        $this->pemasukanModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Pemasukan berhasil dihapus']);
    }

    public function updateStatusDana($id)
    {
        $status          = $this->request->getPost('status_dana');
        $jumlahDiterima  = $this->sanitizeNominal($this->request->getPost('jumlah_diterima'));
        $this->pemasukanModel->update($id, [
            'status_dana'     => $status,
            'jumlah_diterima' => $jumlahDiterima,
        ]);
        return $this->response->setJSON(['success' => true, 'message' => 'Status dana berhasil diupdate']);
    }

    // ── Pengeluaran CRUD ────────────────────────────────────────────────────────

    public function storePengeluaran()
    {
        // Normalisasi nominal terlebih dulu
        $jumlah = $this->sanitizeNominal($this->request->getPost('jumlah'));
        $_POST['jumlah'] = $jumlah;

        $totalP = $this->pemasukanModel->getTotalDiterima();
        $totalE = $this->pengeluaranModel->getTotalPengeluaran();
        $saldo  = $totalP - $totalE;

        if ($jumlah > $saldo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Saldo tidak mencukupi. Saldo saat ini: Rp ' . number_format($saldo, 0, ',', '.'),
            ]);
        }

        $rules = [
            'tanggal'  => 'required|valid_date',
            'kategori' => 'required|min_length[2]',
            'jumlah'   => 'required|numeric|greater_than[0]',
            'bukti'    => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $data = [
            'tanggal'    => $this->request->getPost('tanggal'),
            'kategori'   => $this->request->getPost('kategori'),
            'jumlah'     => $jumlah,
            'tujuan'     => $this->request->getPost('tujuan'),
            'keterangan' => $this->request->getPost('keterangan'),
            'file_bukti' => $this->simpanBukti(),
            'catatan_internal' => $this->request->getPost('catatan_internal'),
        ];

        $id = $this->pengeluaranModel->insert($data);

        // Notif transaksi besar
        $this->cekNotifTransaksiBesar($jumlah, 'pengeluaran');

        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => 'Pengeluaran berhasil ditambahkan']);
    }

    public function updatePengeluaran($id)
    {
        $data = $this->request->getPost();
        unset($data['_method']);

        // Normalisasi nominal SEBELUM validasi (sama seperti storePengeluaran)
        if (isset($data['jumlah'])) $data['jumlah'] = $this->sanitizeNominal($data['jumlah']);
        $_POST['jumlah'] = $data['jumlah'] ?? null;

        $rules = [
            'tanggal'  => 'required|valid_date',
            'kategori' => 'required|min_length[2]',
            'jumlah'   => 'required|numeric|greater_than[0]',
            'bukti'    => 'max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpg,image/jpeg,image/png,application/pdf]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $bukti = $this->simpanBukti();
        if ($bukti) {
            $lama = $this->pengeluaranModel->find($id);
            $this->hapusFileBukti($lama['file_bukti'] ?? null);
            $data['file_bukti'] = $bukti;
        }

        $this->pengeluaranModel->update($id, $data);
        return $this->response->setJSON(['success' => true, 'message' => 'Pengeluaran berhasil diupdate']);
    }

    public function deletePengeluaran($id)
    {
        $row = $this->pengeluaranModel->find($id);
        $this->hapusFileBukti($row['file_bukti'] ?? null);
        $this->pengeluaranModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Pengeluaran berhasil dihapus']);
    }

    /**
     * Hapus banyak baris sekaligus, bisa lintas tabel (pemasukan + pengeluaran).
     * Menerima POST 'pemasukan_ids[]' dan 'pengeluaran_ids[]'.
     */
    public function bulkDelete()
    {
        $pemasukanIds   = $this->request->getPost('pemasukan_ids') ?? [];
        $pengeluaranIds = $this->request->getPost('pengeluaran_ids') ?? [];

        if (!is_array($pemasukanIds))   $pemasukanIds   = [];
        if (!is_array($pengeluaranIds)) $pengeluaranIds = [];

        // Sanitasi: hanya integer
        $pemasukanIds   = array_values(array_filter(array_map('intval', $pemasukanIds), fn($v) => $v > 0));
        $pengeluaranIds = array_values(array_filter(array_map('intval', $pengeluaranIds), fn($v) => $v > 0));

        if (empty($pemasukanIds) && empty($pengeluaranIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
        }

        $deletedP = 0;
        $deletedE = 0;

        // Hapus pemasukan (termasuk file bukti)
        if (!empty($pemasukanIds)) {
            $rows = $this->pemasukanModel->whereIn('id', $pemasukanIds)->findAll();
            foreach ($rows as $row) {
                $this->hapusFileBukti($row['file_bukti'] ?? null);
            }
            $this->pemasukanModel->whereIn('id', $pemasukanIds)->delete();
            $deletedP = count($pemasukanIds);
        }

        // Hapus pengeluaran (termasuk file bukti)
        if (!empty($pengeluaranIds)) {
            $rows = $this->pengeluaranModel->whereIn('id', $pengeluaranIds)->findAll();
            foreach ($rows as $row) {
                $this->hapusFileBukti($row['file_bukti'] ?? null);
            }
            $this->pengeluaranModel->whereIn('id', $pengeluaranIds)->delete();
            $deletedE = count($pengeluaranIds);
        }

        $total = $deletedP + $deletedE;
        $detail = [];
        if ($deletedP > 0) $detail[] = $deletedP . ' pemasukan';
        if ($deletedE > 0) $detail[] = $deletedE . ' pengeluaran';

        return $this->response->setJSON([
            'success' => true,
            'message' => "Berhasil menghapus {$total} data (" . implode(', ', $detail) . ')',
            'deleted_pemasukan'   => $deletedP,
            'deleted_pengeluaran' => $deletedE,
        ]);
    }

    // ── Bulk Insert ─────────────────────────────────────────────────────────────

    public function bulkStore()
    {
        $items = $this->request->getPost('items');
        $tipe  = $this->request->getPost('tipe');

        if (!is_array($items)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak valid']);
        }

        $inserted = 0;
        foreach ($items as $item) {
            if ($tipe === 'pemasukan') {
                $this->pemasukanModel->insert($item);
            } else {
                $this->pengeluaranModel->insert($item);
            }
            $inserted++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "$inserted data berhasil ditambahkan",
        ]);
    }

    // ── Import Data (CSV & Excel) ──────────────────────────────────────────────

    /**
     * Endpoint tunggal untuk import data. Mendeteksi ekstensi file (.csv / .xlsx / .xls)
     * lalu mengubahnya menjadi baris asosiatif yang seragam sebelum divalidasi & disimpan.
     */
    public function import()
    {
        $file = $this->request->getFile('import_file');
        $tipe = $this->request->getPost('tipe') ?? 'pemasukan';

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'File tidak valid atau tidak ditemukan']);
        }

        $ext = strtolower($file->getClientExtension() ?: $file->getExtension());
        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Format file harus .csv, .xlsx, atau .xls']);
        }
        if ($file->getSize() > 5 * 1024 * 1024) { // 5MB max
            return $this->response->setJSON(['success' => false, 'message' => 'Ukuran file maksimal 5 MB']);
        }

        try {
            $rows = $ext === 'csv'
                ? $this->readCsvRows($file->getTempName())
                : $this->readSpreadsheetRows($file->getTempName());
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        if ($rows === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'File kosong atau format tidak valid']);
        }

        [$header, $dataRows] = $rows;

        // Template gabungan pakai kolom pemasukan/pengeluaran, bukan jumlah tunggal —
        // jangan syaratkan 'jumlah' kalau file memang format gabungan.
        $modeDualKolom = in_array('pemasukan', $header) && in_array('pengeluaran', $header);
        $requiredCols  = $modeDualKolom ? ['tanggal', 'kategori'] : ['tanggal', 'kategori', 'jumlah'];
        foreach ($requiredCols as $col) {
            if (!in_array($col, $header)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Kolom wajib '{$col}' tidak ditemukan di header. Header yang ditemukan: " . implode(', ', $header),
                ]);
            }
        }

        return $this->response->setJSON($this->processImportRows($header, $dataRows, $tipe));
    }

    /** Backward-compatible alias (form lama / integrasi lain yang masih memanggil import-csv). */
    public function importCsv()
    {
        return $this->import();
    }

    private function readCsvRows(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return null;

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return null;
        }
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $rows = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
            $rows[] = $row;
        }
        fclose($handle);

        return [$header, $rows];
    }

    private function readSpreadsheetRows(string $path): ?array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $table       = $sheet->toArray(null, true, true, false);

        if (empty($table)) return null;

        // Template yang di-generate sistem punya judul & instruksi di atas header (lihat
        // downloadTemplateUniversalExcel) — jangan asumsikan baris pertama selalu header,
        // cari baris pertama yang benar-benar memuat 'tanggal' DAN 'kategori'.
        $headerIdx = null;
        foreach ($table as $i => $row) {
            $normalized = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            if (in_array('tanggal', $normalized, true) && in_array('kategori', $normalized, true)) {
                $headerIdx = $i;
                break;
            }
        }
        if ($headerIdx === null) return null;

        $table  = array_slice($table, $headerIdx);
        $header = array_map(fn($h) => strtolower(trim((string)$h)), array_shift($table));

        $rows = [];
        foreach ($table as $row) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
            // Excel menyimpan tanggal sebagai serial number bila kolom diformat Date
            $rows[] = array_map(function ($v) {
                if (is_numeric($v) && $v > 25000 && $v < 60000) {
                    // kemungkinan besar ini serial tanggal Excel, tapi hanya dikonversi
                    // di kolom 'tanggal' saat pemrosesan baris (lihat processImportRows)
                    return $v;
                }
                return is_string($v) ? trim($v) : $v;
            }, $row);
        }

        return [$header, $rows];
    }

    /**
     * Validasi & insert baris hasil parsing CSV/Excel. Dipakai bersama oleh kedua format
     * agar aturan validasinya konsisten dan tidak ada logika ganda.
     */
    private function processImportRows(array $header, array $dataRows, string $tipe): array
    {
        $inserted = 0;
        $skipped  = 0;
        $errors   = [];
        $lineNum  = 1; // baris 1 = header
        $modeDualKolom = in_array('pemasukan', $header) && in_array('pengeluaran', $header);
        $adaKolomJenis = in_array('jenis', $header);

        // Dibungkus transaksi supaya import ribuan baris tetap konsisten kalau gagal di
        // tengah jalan (koneksi DB putus, dsb) — baris yang di-skip karena validasi TIDAK
        // membatalkan transaksi (itu memang disengaja: "1500 masuk, 3 dilewati" tetap valid).
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($dataRows as $row) {
            $lineNum++;

            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = isset($row[$i]) ? (is_string($row[$i]) ? trim($row[$i]) : $row[$i]) : '';
            }

            $rowErrors = [];
            $tipeBaris = $tipe;
            $jumlah    = 0;

            if ($modeDualKolom) {
                // Format 2-kolom: isi salah satu kolom "pemasukan" ATAU "pengeluaran" saja
                $nilaiMasuk  = is_numeric($data['pemasukan'] ?? null) ? (float)$data['pemasukan'] : 0;
                $nilaiKeluar = is_numeric($data['pengeluaran'] ?? null) ? (float)$data['pengeluaran'] : 0;

                if ($nilaiMasuk > 0 && $nilaiKeluar > 0) {
                    $rowErrors[] = 'isi hanya salah satu kolom, pemasukan ATAU pengeluaran (tidak boleh dua-duanya)';
                } elseif ($nilaiMasuk > 0) {
                    $tipeBaris = 'pemasukan';
                    $jumlah    = $nilaiMasuk;
                } elseif ($nilaiKeluar > 0) {
                    $tipeBaris = 'pengeluaran';
                    $jumlah    = $nilaiKeluar;
                } else {
                    $rowErrors[] = 'isi salah satu kolom pemasukan atau pengeluaran dengan nominal';
                }
            } else {
                // Format lama: kolom "jumlah" tunggal + kolom "jenis" (opsional) atau tipe default dari form
                if ($adaKolomJenis && !empty($data['jenis'])) {
                    $jenisNormalisasi = strtolower(trim((string)$data['jenis']));
                    if (in_array($jenisNormalisasi, ['pemasukan', 'masuk', 'in', 'income'])) {
                        $tipeBaris = 'pemasukan';
                    } elseif (in_array($jenisNormalisasi, ['pengeluaran', 'keluar', 'out', 'expense'])) {
                        $tipeBaris = 'pengeluaran';
                    } else {
                        $rowErrors[] = "kolom jenis tidak dikenali: '{$data['jenis']}' (isi 'pemasukan' atau 'pengeluaran')";
                    }
                }
                $jumlahStr = (string)($data['jumlah'] ?? '');
                $jumlah    = is_numeric($data['jumlah'] ?? null)
                    ? (float)$data['jumlah']
                    : (float) str_replace(['.', ','], ['', '.'], $jumlahStr);
                if ($jumlah <= 0) {
                    $rowErrors[] = "jumlah tidak valid: '{$jumlahStr}'";
                }
            }

            // Tanggal: dukung YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, dan serial tanggal Excel
            if (empty($data['tanggal']) && $data['tanggal'] !== 0) {
                $rowErrors[] = 'tanggal kosong';
            } else {
                $tglRaw = $data['tanggal'];
                if (is_numeric($tglRaw)) {
                    $d = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$tglRaw);
                } else {
                    $tglRaw = (string)$tglRaw;
                    $d = \DateTime::createFromFormat('Y-m-d', $tglRaw)
                        ?: \DateTime::createFromFormat('d/m/Y', $tglRaw)
                        ?: \DateTime::createFromFormat('d-m-Y', $tglRaw);
                }
                if (!$d) {
                    $rowErrors[] = "format tanggal tidak valid: '{$tglRaw}' (gunakan YYYY-MM-DD atau DD/MM/YYYY)";
                } else {
                    $data['tanggal'] = $d->format('Y-m-d');
                }
            }

            if (empty($data['kategori'])) {
                $rowErrors[] = 'kategori kosong';
            }

            if (!empty($rowErrors)) {
                $errors[] = "Baris {$lineNum}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            // Kolom universal "sumber_tujuan" dipetakan ke field sesuai jenis baris
            $sumberTujuan = $data['sumber_tujuan'] ?? null;

            if ($tipeBaris === 'pemasukan') {
                $this->pemasukanModel->insert([
                    'tanggal'         => $data['tanggal'],
                    'kategori'        => $data['kategori'],
                    'jumlah'          => $jumlah,
                    'jumlah_diterima' => isset($data['jumlah_diterima']) && (float)$data['jumlah_diterima'] > 0
                        ? (float)$data['jumlah_diterima'] : $jumlah,
                    'status_dana'     => isset($data['status_dana']) && in_array($data['status_dana'], ['diterima', 'sebagian', 'belum_diterima'])
                        ? $data['status_dana'] : 'diterima',
                    'sumber'          => $data['sumber'] ?? $sumberTujuan,
                    'keterangan'      => $data['keterangan'] ?? null,
                ]);
            } else {
                $this->pengeluaranModel->insert([
                    'tanggal'    => $data['tanggal'],
                    'kategori'   => $data['kategori'],
                    'jumlah'     => $jumlah,
                    'tujuan'     => $data['tujuan'] ?? $sumberTujuan,
                    'keterangan' => $data['keterangan'] ?? null,
                ]);
            }
            $inserted++;
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return [
                'success'  => false,
                'message'  => 'Import gagal di tengah proses (kesalahan database), semua perubahan dibatalkan. Tidak ada data yang tersimpan sebagian.',
                'inserted' => 0,
                'skipped'  => count($dataRows),
                'errors'   => $errors,
            ];
        }

        $message = "{$inserted} data berhasil diimpor";
        if ($skipped > 0) $message .= ", {$skipped} baris dilewati karena error";

        return [
            'success'  => $inserted > 0 || $skipped === 0,
            'message'  => $message,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    private function templateUniversalDefinition(): array
    {
        return [
            'header' => ['tanggal', 'kategori', 'pemasukan', 'pengeluaran', 'jumlah_diterima', 'status_dana', 'sumber_tujuan', 'keterangan'],
            'contoh' => [
                ['2026-01-15', 'Dana APBN', 5000000, '', 5000000, 'diterima', 'DIPA 2026', 'Penerimaan triwulan 1'],
                ['2026-01-20', 'ATK', '', 500000, '', '', 'Toko Sumber Makmur', 'Pembelian kertas & tinta'],
                ['2026-02-01', 'Hibah', 2000000, '', 0, 'belum_diterima', 'Kemkes', 'Menunggu transfer'],
                ['2026-02-05', 'Perjalanan Dinas & Kunjungan Kerja', '', 1500000, '', '', 'Pegawai Budi', 'Perdin ke Jakarta'],
            ],
        ];
    }

    /** Template CSV universal — satu file untuk pemasukan & pengeluaran sekaligus (kolom "jenis" menentukan tujuannya). */
    public function downloadTemplateUniversalCsv()
    {
        $def     = $this->templateUniversalDefinition();
        $filename = 'template_import_keuangan.csv';

        $buffer = fopen('php://temp', 'r+');
        fputs($buffer, "\xEF\xBB\xBF");
        fputcsv($buffer, $def['header']);
        foreach ($def['contoh'] as $row) fputcsv($buffer, $row);
        rewind($buffer);
        $csvContent = stream_get_contents($buffer);
        fclose($buffer);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($csvContent);
    }

    /** Template Excel universal — kolom identik dengan versi CSV, plus judul, petunjuk,
     *  border rapi, dan dropdown validasi jenis & status supaya jelas cara mengisinya. */
    public function downloadTemplateUniversalExcel()
    {
        $def      = $this->templateUniversalDefinition();
        $filename = 'template_import_keuangan.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $lastCol = chr(ord('A') + count($def['header']) - 1);
        $fill    = fn($rgb) => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]];
        $thinBorder = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]];

        // Judul
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA KEUANGAN — BBPOM DI PANGKAL PINANG');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        // Petunjuk pengisian
        $sheet->setCellValue('A2', 'Petunjuk: isi HANYA salah satu kolom Pemasukan ATAU Pengeluaran per baris (jangan dua-duanya). '
            . 'Format tanggal: YYYY-MM-DD atau DD/MM/YYYY. Kolom status_dana hanya berlaku untuk baris Pemasukan '
            . '(diterima / sebagian / belum_diterima). Kolom tanggal, kategori, dan salah satu nominal wajib diisi.');
        $sheet->mergeCells("A2:{$lastCol}3");
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(45);

        $headerRow = 5;
        $col = 'A';
        foreach ($def['header'] as $h) {
            $sheet->setCellValue($col . $headerRow, $h);
            $col++;
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray(array_merge([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => $fill('0066B2'),
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ], $thinBorder));
        // Tandai kolom "pemasukan" (C) hijau dan "pengeluaran" (D) merah agar jelas dipisah
        $sheet->getStyle("C{$headerRow}")->applyFromArray($fill('059669'));
        $sheet->getStyle("D{$headerRow}")->applyFromArray($fill('DC2626'));

        $r = $headerRow + 1;
        foreach ($def['contoh'] as $row) {
            $col = 'A';
            foreach ($row as $v) {
                $sheet->setCellValue($col . $r, $v);
                $col++;
            }
            $rowStyle = $thinBorder;
            if ($r % 2 === 0) $rowStyle['fill'] = $fill('F8FAFC');
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray($rowStyle);
            $r++;
        }

        // Border kosong untuk baris-baris berikutnya supaya tetap terlihat seperti form/tabel
        for ($i = $r; $i < $r + 195; $i++) {
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray($thinBorder);
        }

        // Dropdown validasi kolom "status_dana" (F)
        for ($i = $headerRow + 1; $i <= $headerRow + 200; $i++) {
            $vStatus = $sheet->getCell("F{$i}")->getDataValidation();
            $vStatus->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $vStatus->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $vStatus->setAllowBlank(true);
            $vStatus->setShowDropDown(true);
            $vStatus->setFormula1('"diterima,sebagian,belum_diterima"');
        }

        // Dropdown validasi kolom "kategori" (B) — gabungan kategori pemasukan & pengeluaran
        // ditaruh di sheet tersembunyi karena daftarnya terlalu panjang untuk formula list
        // langsung (batas ~255 karakter pada data validation Excel).
        $daftarKategori = array_merge(\App\Config\Kategori::$pemasukan, \App\Config\Kategori::$pengeluaran);
        $sheetKategori  = $spreadsheet->createSheet();
        $sheetKategori->setTitle('DaftarKategori');
        foreach ($daftarKategori as $i => $kat) {
            $sheetKategori->setCellValue('A' . ($i + 1), $kat);
        }
        $sheetKategori->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        for ($i = $headerRow + 1; $i <= $headerRow + 200; $i++) {
            $vKategori = $sheet->getCell("B{$i}")->getDataValidation();
            $vKategori->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $vKategori->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $vKategori->setAllowBlank(true);
            $vKategori->setShowDropDown(true);
            $vKategori->setFormula1('DaftarKategori!$A$1:$A$' . count($daftarKategori));
        }
        $spreadsheet->setActiveSheetIndex(0);

        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setWidth(22);
        }
        $sheet->freezePane('A' . ($headerRow + 1));

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return $this->streamXlsx($writer, $filename);
    }

    private function streamXlsx(\PhpOffice\PhpSpreadsheet\Writer\Xlsx $writer, string $filename)
    {
        $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setHeader('Cache-Control', 'max-age=0');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response->setBody($content);
    }

    // ── Helper ──────────────────────────────────────────────────────────────────

    private function cekNotifTransaksiBesar($jumlah, $tipe)
    {
        $setting = $this->pengaturanModel->getSetting();
        if ($setting && $setting['notif_transaksi_besar'] && $jumlah >= (float)$setting['threshold_notif']) {
            $this->notifikasiModel->tambahNotifikasi(
                'Transaksi ' . $tipe . ' besar tercatat: Rp ' . number_format($jumlah, 0, ',', '.'),
                'transaksi_besar',
                $tipe // kategori: 'pemasukan' | 'pengeluaran'
            );
        }
    }

}
