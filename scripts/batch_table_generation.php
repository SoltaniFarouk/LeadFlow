<?php
echo "Check before running  \n"; 
exit();  




// ---- AUTOLOAD ALL CONNECTION FILES ----
//$connectionPath = __DIR__ . '/app/Connection/*.php';
$connectionPath = '/var/www/html/phone_extractor/app/Connection/*.php';
foreach (glob($connectionPath) as $connectionFile) {
    require_once $connectionFile;
}

// Namespace for all DB connection classes
$namespace = 'App\\Connection\\';




/**
 * Dynamically get DB connection from class name (e.g., Dep01, ConfigLeadFr).
 *
 * @param string $dbCode Example: 'ConfigLeadFr', 'Dep01', 'Dep97'
 * @return PDO
 * @throws Exception
 */
function getDbConnection(string $dbCode): PDO {
    global $namespace;
    $className = $namespace . $dbCode . 'Database';
    if (!class_exists($className)) {
        throw new Exception("Database connection class not found: $className");
    }
    return (new $className())->connect();
}

// ---- FILENAME HELPERS ----
function extractDepNumber($filename) {
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    $parts = explode('-', $nameWithoutExt);
    return str_replace('-', '', $parts[1] . $parts[2] . $parts[3]);
}

function extractFirstPart($filename) {
    return explode('-', $filename)[0];
}

function extractDepPart($filename) {
    return explode('-', $filename)[1];
}

function extractLastNumber($filename) {
    $parts = explode('-', $filename);
    return (int)str_replace('.txt', '', end($parts));
}

function extractMiddleNumberRegex($filename) {
    return preg_match('/-(\d{3})-/', $filename, $matches) ? $matches[1] : null;
}

// ---- CONFIG ----
$directory = '/var/www/html/sans404_01-2025/';
$configLeadFrDB = getDbConnection('ConfigLeadFr');

// ---- MAIN ----
if (!is_dir($directory)) {
    die("❌ Invalid directory path: $directory\n");
}

if ($dh = opendir($directory)) {
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $subdirectory = $directory . $file . '/';
        if (!is_dir($subdirectory)) {
            continue;
        }

        $name_db = 'dbx_' . strtolower($file);
        echo "\n📂 Processing: $subdirectory\n";
        echo "Database: $name_db\n";

        $txtFiles = glob($subdirectory . "*.txt");

        if (empty($txtFiles)) {
            echo "⚠️ No TXT files found.\n";
            continue;
        }

        foreach ($txtFiles as $file_txt) {
            $current_file = basename($file_txt);
            echo "\n📄 File: $current_file\n";

            // Extract values
            $batch_name       = extractDepNumber($current_file);
            $batch_split_date = extractFirstPart($current_file);
            $department       = extractDepPart($current_file);
            $database_name    = $name_db;
            $table_name       = 'tb_batch_' . extractLastNumber($current_file);
            $phone_prefix     = extractMiddleNumberRegex($current_file);

            echo " - batch_name: $batch_name\n";
            echo " - batch_split_date: $batch_split_date\n";
            echo " - department: $department\n";
            echo " - database_name: $database_name\n";
            echo " - table_name: $table_name\n";
            echo " - phone_prefix: $phone_prefix\n";

            // 1️⃣ Insert into tb_batch_management in dbx_config_lead_fr
            $insertSQL = "INSERT INTO tb_batch_management 
                (batch_name, batch_split_date, department, database_name, table_name, phone_prefix) 
                VALUES (:batch_name, :batch_split_date, :department, :database_name, :table_name, :phone_prefix)";

            $stmt = $configLeadFrDB->prepare($insertSQL);
            $stmt->execute([
                ':batch_name'       => $batch_name,
                ':batch_split_date' => $batch_split_date,
                ':department'       => $department,
                ':database_name'    => $database_name,
                ':table_name'       => $table_name,
                ':phone_prefix'     => $phone_prefix
            ]);

            echo "✅ Inserted batch into tb_batch_management\n";

            // 2️⃣ Create table in target department database
            $depCode = ucfirst($department); // e.g., "Dep01"
            try {
                $pdoTarget = getDbConnection($depCode);
            } catch (Exception $e) {
                echo "❌ Could not connect to target DB: " . $e->getMessage() . "\n";
                continue;
            }

            $createTableSQL = "CREATE TABLE IF NOT EXISTS `$table_name` (
    tel1 VARCHAR(15) PRIMARY KEY NOT NULL,
    code_postal VARCHAR(20) NULL,
    ville VARCHAR(255) NULL,
    adresse VARCHAR(255) NULL,
    genre VARCHAR(10) NULL,
    nom VARCHAR(255) NULL,
    prenom VARCHAR(255) NULL,                
    tel2 VARCHAR(50) NULL,
    tel3 VARCHAR(50) NULL,
    mobile VARCHAR(50) NULL,
    fax VARCHAR(50) NULL,
    habitat VARCHAR(255) NULL,
    age_moyen VARCHAR(50) NULL,
    ethnie VARCHAR(100) NULL,
    tel1_prospection VARCHAR(50) NULL,
    tel2_prospection VARCHAR(50) NULL,
    tel3_prospection VARCHAR(50) NULL,
    mobile_prospection VARCHAR(50) NULL,
    fax_prospection VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


            $pdoTarget->exec($createTableSQL);
            echo "✅ Table `$table_name` created in `$database_name`\n";
        }
    }
    closedir($dh);
} else {
    echo "❌ Could not open directory.\n";
}
