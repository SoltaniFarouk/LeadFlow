<?php

echo "This script creates a new database for each subdirectory in the specified directory.\n";
echo "The database name is derived from the subdirectory name.\n";
echo "Check before running it as it may generate new databases.\n";
exit();

/** ---------- Bootstrap ---------- */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../config/autoload.php';

use App\Repository\DatabaseProvisionRepository;
use App\Service\DatabaseProvisionService;
use App\Controller\DatabaseProvisionController;

/** ---------- Helper ---------- */
function requireEnv(string $key): string {
    $value = $_ENV[$key] ?? null;
    if ($value === null) {
        throw new \RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

/** ---------- Connection (no dbname — creating databases) ---------- */
$pdo = new PDO(
    "mysql:host=" . requireEnv('DB_DEP_HOST') . ";port=" . requireEnv('DB_DEP_PORT') . ";charset=utf8mb4",
    requireEnv('DB_DEP_USER'),
    requireEnv('DB_DEP_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/** ---------- Wire up ---------- */
$repo       = new DatabaseProvisionRepository($pdo);
$service    = new DatabaseProvisionService($repo);
$controller = new DatabaseProvisionController($service);

/** ---------- Run ---------- */
$controller->processDirectory(requireEnv('BATCH_SOURCE_DIRECTORY'));