<?php
echo "Check before running  \n"; 
exit(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '4024M');
ob_implicit_flush(true);

// ---------- Function: PDO Connection ----------
function createPdoConnection(
    string $host,
    string $db,
    string $user,
    string $pass,
    int $port = 3306,
    int $retries = 3
): ?PDO {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            $pdo->query("SET SQL_BIG_SELECTS=1");
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000); // wait 0.5s
            if ($retries === 0) {
                error_log("❌ DB connection error to {$db}@{$host}: " . $e->getMessage());
                return null;
            }
        }
    }
    return null;
}

// ---------- Shortcut Connections ----------
function connConfigLead(): ?PDO {
    return createPdoConnection("localhost", "dbx_config_lead_fr", "root", "123456fF$$");
}
function connDepAuto(string $dbName): ?PDO {
    return createPdoConnection("localhost", $dbName, "root", "123456fF$$");
}

// ---------- Function: Add Missing Columns ----------
function addMissingColumns(PDO $pdo, string $tableName): void {
    $columnsToAdd = [
        "crm_status_1"   => "VARCHAR(50) NULL",
        "crm_date_call_1"=> "DATETIME NULL",
        "crm_status_2"   => "VARCHAR(50) NULL",
        "crm_date_call_2"=> "DATETIME NULL",
        "crm_status_3"   => "VARCHAR(50) NULL",
        "crm_date_call_3"=> "DATETIME NULL",
        "update_number"  => "INT DEFAULT 0",
        "crm_status"     => "VARCHAR(50) NULL",
        "crm_date_call"  => "DATETIME NULL"
    ];

    foreach ($columnsToAdd as $col => $type) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE :col");
        $stmt->execute([':col' => $col]);
        if (!$stmt->fetch()) {
            try {
                $pdo->exec("ALTER TABLE `$tableName` ADD COLUMN `$col` $type");
                echo "✅ Added column `$col` to `$tableName`\n";
            } catch (PDOException $e) {
                echo "❌ Error adding column `$col` to `$tableName`: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ Column `$col` already exists in `$tableName`\n";
        }
    }
}

// ================================================================================================================
// Main Script
$connConfigLead = connConfigLead();
if ($connConfigLead === null) {
    die("❌ Cannot connect to config database.\n");
}

$sql = "SELECT * FROM tb_batch_management";
$stmt = $connConfigLead->prepare($sql);
$stmt->execute();
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$batches) {
    die("⚠️ No batch found in tb_batch_management.\n");
}

foreach ($batches as $batch) {
    $tableId       = $batch['id'];
    $batch_name    = $batch['batch_name'];
    $batch_file    = $batch['batch_file'];
    $batch_split   = $batch['batch_split_date'];
    $department    = $batch['department'];
    $database_name = $batch['database_name'];
    $table_name    = $batch['table_name'];

    echo "\n------------------------------------------------------\n";
    echo "📌 TABLE.ID : $tableId\n";
    echo "📂 batch_name : $batch_name\n";
    echo "📄 batch_file : $batch_file\n";
    echo "📅 batch_split_date : $batch_split\n";
    echo "🏢 department : $department\n";
    echo "🗄️ database_name : $database_name\n";
    echo "📊 table_name : $table_name\n";

    $pdo_auto = connDepAuto($database_name);
    if ($pdo_auto === null) {
        echo "❌ Failed to connect to database: $database_name\n";
        continue;
    }

    // Ensure required CRM columns exist
    addMissingColumns($pdo_auto, $table_name);

    // close connection
    $pdo_auto = null;
}

// close connection
$connConfigLead = null;
echo "\n✅ Script finished.\n";
