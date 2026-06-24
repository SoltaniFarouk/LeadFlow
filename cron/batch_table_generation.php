<?php

/** ---------- Runtime & Errors ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "Check before running \n";
exit();

/** ---------- Bootstrap ---------- */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../config/autoload.php';

use App\Repository\BatchTableRepository;
use App\Service\BatchTableService;
use App\Controller\BatchTableController;

/** ---------- Helper: require ENV var ---------- */
function requireEnv(string $key): string {
    $value = $_ENV[$key] ?? null;
    if ($value === null) {
        throw new \RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

/** ---------- Dynamic DB connection ---------- */
function getDbConnection(string $dbCode): \PDO {
    $className = 'App\\Connection\\' . $dbCode . 'Database';
    if (!class_exists($className)) {
        throw new \Exception("Database connection class not found: $className");
    }
    return (new $className())->connect();
}

/** ---------- Wire up ---------- */
$directory      = requireEnv('BATCH_SOURCE_DIRECTORY');
$configLeadFrDB = getDbConnection('ConfigLeadFr');

$repo       = new BatchTableRepository($configLeadFrDB);
$service    = new BatchTableService($repo);
$controller = new BatchTableController($service);

/** ---------- Main ---------- */
if (!is_dir($directory)) {
    die("❌ Invalid directory path: $directory\n");
}

$dh = opendir($directory);
if (!$dh) {
    die("❌ Could not open directory.\n");
}

while (($file = readdir($dh)) !== false) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $subdirectory = $directory . $file . '/';
    if (!is_dir($subdirectory)) {
        continue;
    }

    $databaseName = 'dbx_' . strtolower($file);

    echo "\n📂 Processing: $subdirectory\n";
    echo "Database: $databaseName\n";

    $controller->processDirectory(
        $subdirectory,
        $databaseName,
        fn(string $depCode) => getDbConnection($depCode)
    );
}

closedir($dh);