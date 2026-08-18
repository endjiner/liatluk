<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotifikasiModel;

class Notifikasi extends BaseController
{
    protected $notifikasiModel;

    public function __construct()
    {
        $this->notifikasiModel = new NotifikasiModel();
    }

    /** Dropdown notifikasi di topbar (AJAX, hanya yang belum dibaca) */
    public function getUnread()
    {
        return $this->response->setJSON([
            'data'  => $this->notifikasiModel->getUnread(),
            'count' => $this->notifikasiModel->countUnread(),
        ]);
    }

    /** Halaman penuh notifikasi dengan filter jangka waktu & jenis */
    public function index(): string
    {
        $periode  = $this->request->getGet('periode') ?? 'tahun';
        $tahun    = $this->request->getGet('tahun') ?? date('Y');
        $bulan    = $this->request->getGet('bulan') ?? date('n');
        $jenis    = $this->request->getGet('jenis') ?? 'semua';
        $page     = (int) ($this->request->getGet('page') ?? 1);
        $limit    = 20;
        $offset   = ($page - 1) * $limit;

        $filters = compact('periode', 'tahun', 'bulan', 'jenis');

        $notifikasi = $this->notifikasiModel->getFiltered($filters, $limit, $offset);
        $total      = $this->notifikasiModel->countFilteredAll($filters);
        $totalPage  = (int) ceil($total / $limit);

        return view('admin/notifikasi', [
            'notifikasi'  => $notifikasi,
            'total'       => $total,
            'totalPage'   => $totalPage,
            'currentPage' => $page,
            'periode'     => $periode,
            'tahun'       => $tahun,
            'bulan'       => (int) $bulan,
            'jenis'       => $jenis,
            'notifCount'  => $this->notifikasiModel->countUnread(),
        ]);
    }

    public function markRead($id)
    {
        $this->notifikasiModel->markRead($id);
        return $this->response->setJSON(['success' => true]);
    }

    public function markAllRead()
    {
        $this->notifikasiModel->markAllRead();
        return $this->response->setJSON(['success' => true]);
    }
}
