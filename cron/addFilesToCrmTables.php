<?php

/** ---------- Runtime & Errors ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '4024M');
ob_implicit_flush(true);

$curdateTime     = date("Y-m-d H:i:s");
$str_msg         = "<B>" . $curdateTime . "</B> \n";
$dateDateAndTime = date('Y-m-d H:i:s');

/** ---------- Bootstrap ---------- */
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../../web_dev/api/model/Skriptbibliothek.php';

use App\Repository\BatchRepository;
use App\Repository\VicidialRepository;
use App\Repository\BashOrdreRepository;
use App\Service\VicidialService;
use App\Controller\BatchController;
use App\Controller\VicidialController;

/** ---------- Helper ---------- */
function requireEnv(string $key): string {
    $value = $_ENV[$key] ?? null;
    if ($value === null) {
        throw new \RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

function logMessage(string $message): void {
    $logFile  = __DIR__ . '/../logs/add_phone_crm.log';
    $dateTime = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$dateTime] $message\n", FILE_APPEND);
}

/** ---------- Connections ---------- */
function connBackupVicidialIse(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=' . requireEnv('DB_VICIDIAL_HOST') . ';port=' . requireEnv('DB_VICIDIAL_PORT') . ';dbname=' . requireEnv('DB_VICIDIAL_NAME') . ';charset=UTF8',
                requireEnv('DB_VICIDIAL_USER'),
                requireEnv('DB_VICIDIAL_PASS'),
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
                'mysql:host=' . requireEnv('DB_CONFIG_LEAD_FR_HOST') . ';port=' . requireEnv('DB_CONFIG_LEAD_FR_PORT') . ';dbname=' . requireEnv('DB_CONFIG_LEAD_FR_NAME') . ';charset=UTF8',
                requireEnv('DB_CONFIG_LEAD_FR_USER'),
                requireEnv('DB_CONFIG_LEAD_FR_PASS'),
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) die('Erreur ConfigLeadFr: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

function connDepAuto(string $name_db): ?PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                "mysql:host=" . requireEnv('DB_DEP_HOST') . ";port=" . requireEnv('DB_DEP_PORT') . ";dbname={$name_db};charset=utf8mb4",
                requireEnv('DB_DEP_USER'),
                requireEnv('DB_DEP_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
            );
            $pdo->query("SET SQL_BIG_SELECTS=1");
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) { error_log("DB error [{$name_db}]: " . $e->getMessage()); return null; }
        }
    }
    return null;
}

/** ---------- Start ---------- */
logMessage('---------------------- START SCRIPT -------------------------');

$pdoConfigLead = connConfigLeadFr();
$pdoVicidial   = connBackupVicidialIse();

$bashOrdreRepo = new BashOrdreRepository($pdoConfigLead);
$lastOrdre     = $bashOrdreRepo->getLastPending();

if (!$lastOrdre) {
    echo "No pending bash ordre found. \n";
    logMessage(' No pending bash ordre found. ');
    exit();
}

$idOrdre        = $lastOrdre['idOrdre'];
$selectDataBase = $lastOrdre['selectDataBase'];
$nameTable      = $lastOrdre['nameTable'];
$phoneCode      = $lastOrdre['phone_code'];
$idLot          = $lastOrdre['id_lot'];
$extra          = $lastOrdre['extra'];
$idUserConnecte = $lastOrdre['id_user_connecte'];

echo "ID Ordre: $idOrdre <br> \n";
echo "Database: $selectDataBase <br> \n";
echo "Table: $nameTable <br> \n";
echo "Phone Code: $phoneCode <br> \n";
echo "ID Lot: $idLot <br> \n";
echo "Extra: $extra <br> \n";
echo "User Connected ID: $idUserConnecte <br> \n";

/** ---------- Wire up ---------- */
$pdoDep          = connDepAuto($selectDataBase);
$batchRepo       = new BatchRepository($pdoDep, $selectDataBase, $nameTable);
$batchController = new BatchController($batchRepo);
$vicidialRepo    = new VicidialRepository($pdoVicidial);
$vicidialService = new VicidialService($vicidialRepo, $bashOrdreRepo);
$controller      = new VicidialController($vicidialService, $batchController);

/** ---------- Execute ---------- */
$result = $controller->handle($lastOrdre, $dateDateAndTime, $str_msg);

$meta  = $result['meta'];
$stats = $result['stats'];

echo "- List Id : "          . htmlspecialchars($meta['listId'])          . " <br> \n"; logMessage(" List Id : "          . $meta['listId']);
echo "- List Name : "        . htmlspecialchars($meta['listName'])        . " <br> \n"; logMessage(" List Name : "        . $meta['listName']);
echo "- List Description : " . htmlspecialchars($meta['listDescription']) . " <br> \n"; logMessage(" List Description : " . $meta['listDescription']);

if ($result['created']) {
    echo "<h3>List {$meta['listId']} created</h3> \n";
    logMessage(" List created: " . $meta['listId']);
} else {
    echo "<p style='color:red;'>List already exists in Vicidial.</p> \n";
    logMessage(" List already exists.");
}

echo "<h3>Done.</h3> \n";
logMessage(" Done. ");
echo "<p>Inserted: {$stats['inserted']} | Skipped: {$stats['skipped']} | Errors: {$stats['errors']}</p> \n";
logMessage(" Inserted: {$stats['inserted']} | Skipped: {$stats['skipped']} | Errors: {$stats['errors']}");

/** ---------- Finalize ---------- */
$updated = $vicidialService->finalizeOrdre(
    $idOrdre,
    $idLot,
    (int)$idUserConnecte,
    $meta['listId'],
    $dateDateAndTime,
    (int)$extra
);

if ($updated) {
    echo "✅ last_id_crm updated successfully for batch ID $idLot. \n";
    logMessage(" last_id_crm updated for batch ID $idLot.");

    $result_sendToTelegramGroup = sendHtmlMessageToTelegramGroup(
        $str_msg,
        requireEnv('TELEGRAM_CHAT_ID'),
        requireEnv('TELEGRAM_BOT_TOKEN')
    );
} else {
    echo "❌ Failed to update last_id_crm for batch ID $idLot. \n";
    logMessage(" Failed to update last_id_crm for batch ID $idLot.");
}

$pdoVicidial = null;
$pdoDep      = null;

logMessage('---------------------- END SCRIPT -------------------------');