<?php

use CodeIgniter\Boot;
use Config\Paths;

echo "STEP 1: PHP OK<br>";

require __DIR__ . '/vendor/autoload.php';
echo "STEP 2: AUTOLOAD OK<br>";

require __DIR__ . '/app/Config/Paths.php';
echo "STEP 3: PATHS FILE OK<br>";

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
echo "STEP 4: FCPATH OK<br>";

$paths = new Paths();
echo "STEP 5: PATHS OBJECT OK<br>";

require $paths->systemDirectory . '/Boot.php';
echo "STEP 6: BOOT FILE OK<br>";

Boot::bootWeb($paths);

echo "STEP 7: BOOT WEB FINISHED<br>";