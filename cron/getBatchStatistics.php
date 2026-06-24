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
require_once __DIR__ . '/../../web_dev/api/model/Skriptbibliothek.php';

use App\Repository\BatchStatisticsRepository;
use App\Service\BatchStatisticsService;
use App\Controller\BatchStatisticsController;

/** ---------- Helper ---------- */
function requireEnv(string $key): string {
    $value = $_ENV[$key] ?? null;
    if ($value === null) {
        throw new \RuntimeException("Missing environment variable: {$key}");
    }
    return $value;
}

function logMessage(string $message): void {
    $logFile  = __DIR__ . '/../logs/getBatchStatistics.log';
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

function conBdReceptionIse(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=' . requireEnv('DB_RECEPTION_HOST') . ';port=' . requireEnv('DB_RECEPTION_PORT') . ';dbname=' . requireEnv('DB_RECEPTION_NAME') . ';charset=UTF8',
                requireEnv('DB_RECEPTION_USER'),
                requireEnv('DB_RECEPTION_PASS'),
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) die('Erreur Reception: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

/** ---------- Telegram helper ---------- */
function sendTelegram(string $message): void {
    sendHtmlMessageToTelegramGroup(
        $message,
        requireEnv('TELEGRAM_CHAT_ID'),
        requireEnv('TELEGRAM_BOT_TOKEN')
    );
}

function buildTelegramMessage(string $datetimeNow, string $event): string {
    return "🖥️<B>SERVER:</B> 130 \n"
        . "⚙️<B>CRON:</B> GET BATCH STATISTICS \n"
        . "⏰<B>DATE:</B> $datetimeNow \n"
        . "🐘<B>FILE:</B> getBatchStatistics.php \n"
        . "🗒️<B>NOTES: retrieve the statistics from the Vicidial list used today</B> \n"
        . "📨<B>MESSAGE:</B> $event \n";
}

/** ---------- Main ---------- */
$datetimeNow = date('Y-m-d H:i:s');

sendTelegram(buildTelegramMessage($datetimeNow, 'START SCRIPT'));
logMessage('---------------------- START SCRIPT --------------------------');

$pdoVicidial  = connBackupVicidialIse();
$pdoConfigLead = connConfigLeadFr();
$pdoReception  = conBdReceptionIse();

$repo       = new BatchStatisticsRepository($pdoVicidial, $pdoConfigLead, $pdoReception);
$service    = new BatchStatisticsService($repo);
$controller = new BatchStatisticsController($service);

$totalUpdated = $controller->run();

echo " Nombre de mise a jour : $totalUpdated \n";
logMessage(" >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Nombre de mise a jour : $totalUpdated");

$pdoVicidial   = null;
$pdoConfigLead = null;
$pdoReception  = null;

logMessage('---------------------- END SCRIPT -------------------------');
sendTelegram(buildTelegramMessage($datetimeNow, 'END SCRIPT'));