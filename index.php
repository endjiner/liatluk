<?php
/**
 * Root Front Controller Fallback
 * Memungkinkan hosting seperti InfinityFree / cPanel menjalankan aplikasi
 * secara langsung dari root / htdocs tanpa harus mengetik /public/ di URL.
 */

require __DIR__ . '/public/index.php';
