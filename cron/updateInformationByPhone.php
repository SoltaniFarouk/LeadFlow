<?php

/** ---------- Runtime & Errors ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '4024M');
ob_implicit_flush(true);

/** ---------- Bootstrap ---------- */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../config/autoload.php';

use App\Repository\PhoneUpdateRepository;
use App\Service\PhoneUpdateService;
use App\Controller\PhoneUpdateController;

/** ---------- Helper ---------- */
function requireEnv(string $key): string {
    $value = $_ENV[$key] ?? null;
    if ($value === null) {
        throw new \RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

function logMessage(string $message): void {
    $logFile  = __DIR__ . '/../logs/updateInformationByPhone.log';
    $dateTime = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$dateTime] $message\n", FILE_APPEND);
}

/** ---------- Connections ---------- */
function connBackupVicidialIse(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=' . requireEnv('DB_VICIDIAL_HOST') . ';port=' . requireEnv('DB_VICIDIAL_PORT') . ';dbname=' . requireEnv('DB_VICIDIAL_NAME') . ';charset=utf8mb4',
                requireEnv('DB_VICIDIAL_USER'),
                requireEnv('DB_VICIDIAL_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
            );
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) die('Erreur Vicidial: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

function connConfigLeadFr(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=' . requireEnv('DB_CONFIG_LEAD_FR_HOST') . ';port=' . requireEnv('DB_CONFIG_LEAD_FR_PORT') . ';dbname=' . requireEnv('DB_CONFIG_LEAD_FR_NAME') . ';charset=utf8mb4',
                requireEnv('DB_CONFIG_LEAD_FR_USER'),
                requireEnv('DB_CONFIG_LEAD_FR_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
            );
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) die('Erreur ConfigLeadFr: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

function connDepAuto(string $dbName): ?PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                "mysql:host=" . requireEnv('DB_DEP_HOST') . ";port=" . requireEnv('DB_DEP_PORT') . ";dbname={$dbName};charset=utf8mb4",
                requireEnv('DB_DEP_USER'),
                requireEnv('DB_DEP_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
            );
            $pdo->query("SET SQL_BIG_SELECTS=1");
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) { error_log("DB error [{$dbName}]: " . $e->getMessage()); return null; }
        }
    }
    return null;
}

/** ---------- Main ---------- */
$threeDaysAgo = date('Y-m-d H:i:s', strtotime('-3 days'));
echo " Three Days Ago : $threeDaysAgo \n";

logMessage('---------------------- START SCRIPT -------------------------');

$pdoVicidial   = connBackupVicidialIse();
$pdoConfigLead = connConfigLeadFr();

$repo       = new PhoneUpdateRepository($pdoConfigLead, $pdoVicidial);
$service    = new PhoneUpdateService($repo);
$controller = new PhoneUpdateController(
    $service,
    fn(string $dbName) => connDepAuto($dbName)
);

$totalUpdated = $controller->run($threeDaysAgo);

echo " Nombre de mise a jour : $totalUpdated \n";
logMessage(" >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Nombre de mise a jour : $totalUpdated");

$pdoVicidial   = null;
$pdoConfigLead = null;

logMessage('---------------------- END SCRIPT -------------------------');