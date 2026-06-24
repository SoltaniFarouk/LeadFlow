<?php
//echo "Check before running  \n"; 
//exit();  


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

            // 1️⃣ UPDATE in tb_batch_management the  batch_file = current_file and phone_nombre = number of lines found in the file - 1
            // 2️⃣ add the file contents into the table = $table_name

            // 1️⃣ UPDATE in tb_batch_management
$lines = file($file_txt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$phone_nombre = max(0, count($lines) - 1); // Header abziehen

$updateSQL = "UPDATE tb_batch_management 
              SET batch_file = :batch_file, phone_nombre = :phone_nombre 
              WHERE batch_name = :batch_name AND table_name = :table_name";

$stmtUpdate = $configLeadFrDB->prepare($updateSQL);
$stmtUpdate->execute([
    ':batch_file'   => $current_file,
    ':phone_nombre' => $phone_nombre,
    ':batch_name'   => $batch_name,
    ':table_name'   => $table_name
]);

echo "✅ Updated tb_batch_management with file + phone count\n";

// 2️⃣ Insert file contents into target table
$depCode = ucfirst($department); // e.g., "Dep01"
try {
    $pdoTarget = getDbConnection($depCode);
} catch (Exception $e) {
    echo "❌ Could not connect to target DB: " . $e->getMessage() . "\n";
    continue;
}

// Prepare INSERT for all columns
$insertSQL = "INSERT IGNORE INTO `$table_name`
    (tel1, code_postal, ville, adresse, genre, nom, prenom,
     tel2, tel3, mobile, fax, habitat, age_moyen, ethnie,
     tel1_prospection, tel2_prospection, tel3_prospection,
     mobile_prospection, fax_prospection)
VALUES
    (:tel1, :code_postal, :ville, :adresse, :genre, :nom, :prenom,
     :tel2, :tel3, :mobile, :fax, :habitat, :age_moyen, :ethnie,
     :tel1_prospection, :tel2_prospection, :tel3_prospection,
     :mobile_prospection, :fax_prospection)";

$stmtInsert = $pdoTarget->prepare($insertSQL);

// ab Zeile 2 (erste Zeile = Header)
for ($i = 1; $i < count($lines); $i++) {
    $cols = explode("\t", $lines[$i]);

    if (count($cols) < 8) { 
        continue; // keine tel1 → skip
    }

    $stmtInsert->execute([
        ':tel1'               => trim($cols[7]),
        ':code_postal'        => trim($cols[1] ?? ''),
        ':ville'              => trim($cols[2] ?? ''),
        ':adresse'            => trim($cols[3] ?? ''),
        ':genre'              => trim($cols[4] ?? ''),
        ':nom'                => trim($cols[5] ?? ''),
        ':prenom'             => trim($cols[6] ?? ''),
        ':tel2'               => trim($cols[8] ?? ''),
        ':tel3'               => trim($cols[9] ?? ''),
        ':mobile'             => trim($cols[10] ?? ''),
        ':fax'                => trim($cols[11] ?? ''),
        ':habitat'            => trim($cols[12] ?? ''),
        ':age_moyen'          => trim($cols[13] ?? ''),
        ':ethnie'             => trim($cols[14] ?? ''),
        ':tel1_prospection'   => trim($cols[15] ?? ''),
        ':tel2_prospection'   => trim($cols[16] ?? ''),
        ':tel3_prospection'   => trim($cols[17] ?? ''),
        ':mobile_prospection' => trim($cols[18] ?? ''),
        ':fax_prospection'    => trim($cols[19] ?? '')
    ]);
}

echo "✅ Inserted $phone_nombre contacts into `$table_name`\n";

        }
    }
    closedir($dh);
} else {
    echo "❌ Could not open directory.\n";
}
