<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /** Simpan file bukti nota/barang (opsional) yang diupload lewat field "bukti". */
    protected function simpanBukti(): ?string
    {
        $file = $this->request->getFile('bukti');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $uploadPath = FCPATH . 'uploads/bukti';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);

        return $newName;
    }

    /** Hapus file bukti lama dari disk (dipanggil saat update/hapus data). */
    protected function hapusFileBukti(?string $filename): void
    {
        if (!$filename) return;
        $path = FCPATH . 'uploads/bukti/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
