<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> BM : ORDER INTERFACE </title>
    <link rel="icon" type="image/png" href="../images/loglog.png">
    <link rel="stylesheet" href="../css/monitor_batch_management.css">
</head>
<body>
<h1>Add Batch To Vicidial</h1>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '7024M');

/** ---------- Database connection ---------- */
function connConfigLeadFr(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;port=3306;dbname=dbx_config_lead_fr;charset=UTF8',
                'root',
                '123456fF$$',
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000);
            if ($retries === 0) {
                die('Erreur ConfigLeadFrDatabase - db :  ' . htmlspecialchars($e->getMessage()));
            }
        }
    }
}

/** ---------- Function to add new bash ordre ---------- */
/*
function addNewBashOrdre(
    string $databaseName,
    string $tableName,
    string $phoneCode,
    int $numberUse,
    int $idLot,
    int $extra,
    int $idUserConnecte,
    int $status = 0
): bool {
    try {
        $conn = connConfigLeadFr();
        $sql = "INSERT INTO tb_bash_ordre 
                    (database_name, table_name, phone_code, number_use, id_lot, extra, id_user_connecte, status) 
                VALUES 
                    (:database_name, :table_name, :phone_code, :number_use, :id_lot, :extra, :id_user_connecte, :status)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':database_name'   => $databaseName,
            ':table_name'      => $tableName,
            ':phone_code'      => $phoneCode,
            ':number_use'      => $numberUse,
            ':id_lot'          => $idLot,
            ':extra'           => $extra,
            ':id_user_connecte'=> $idUserConnecte,
            ':status'          => $status
        ]);
    } catch (PDOException $e) {
        error_log("Insert tb_bash_ordre failed: " . $e->getMessage());
        return false;
    }
}
*/

function addNewBashOrdre(
    string $databaseName,
    string $tableName,
    string $phoneCode,
    int $numberUse,
    int $idLot,
    int $extra,
    int $idUserConnecte,
    int $status = 0
): bool {

    try {
        $conn = connConfigLeadFr();

        // Check if an entry exists older than 35 days
        $checkSql = "SELECT created_at 
                     FROM tb_bash_ordre 
                     WHERE database_name = :database_name 
                       AND table_name = :table_name 
                       AND created_at < (NOW() - INTERVAL 35 DAY)
                     LIMIT 1";

        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([
            ':database_name' => $databaseName,
            ':table_name'    => $tableName
        ]);

        // If a record exists older than 35 days, do something (optional)
        if ($checkStmt->fetch()) {
            error_log("Record found older than 35 days for {$databaseName} / {$tableName}");
            // You can decide to delete it, update it, or skip inserting a new one
            // Example: return false;
        }

        // Insert new record
        $sql = "INSERT INTO tb_bash_ordre 
                    (database_name, table_name, phone_code, number_use, id_lot, extra, id_user_connecte, status) 
                VALUES 
                    (:database_name, :table_name, :phone_code, :number_use, :id_lot, :extra, :id_user_connecte, :status)";
        $stmt = $conn->prepare($sql);

        return $stmt->execute([
            ':database_name'    => $databaseName,
            ':table_name'       => $tableName,
            ':phone_code'       => $phoneCode,
            ':number_use'       => $numberUse,
            ':id_lot'           => $idLot,
            ':extra'            => $extra,
            ':id_user_connecte' => $idUserConnecte,
            ':status'           => $status
        ]);

    } catch (PDOException $e) {
        error_log("Insert tb_bash_ordre failed: " . $e->getMessage());
        return false;
    }
}

/** ---------- Get & sanitize GET params ---------- */
$selectDataBase  = $_GET['var1'] ?? '';
$nameTable       = $_GET['var2'] ?? '';
$phoneCode       = $_GET['var3'] ?? '';
$numberUse       = intval($_GET['var4'] ?? 0);
$idLot           = intval($_GET['var5'] ?? 0);
$extra           = intval($_GET['var6'] ?? 0);
$idUserConnecte  = intval($_GET['var7'] ?? 0);

// Check required fields
if (!$selectDataBase || !$nameTable || !$phoneCode || !$numberUse || !$idLot || !$idUserConnecte) {
    die("❌ Missing required parameters.");
}

// Display parameters
echo "- Data Base: " . htmlspecialchars($selectDataBase) . "<br>";
echo "- Table: " . htmlspecialchars($nameTable) . "<br>";
echo "- Phone Code: " . htmlspecialchars($phoneCode) . "<br>";
echo "- Number Use: " . $numberUse . "<br>";
echo "- ID Lot: " . $idLot . "<br>";
echo "- Extra: " . $extra . "<br>";
echo "- idUserConnecte: " . $idUserConnecte . "<br>";

// Insert new bash ordre
$result = addNewBashOrdre(
    $selectDataBase,
    $nameTable,
    $phoneCode,
    $numberUse,
    $idLot,
    $extra,
    $idUserConnecte,
    0
);

if ($result) {
    echo "✅ New bash ordre inserted successfully.";
    echo '
        <script>
            setTimeout(function() {
                window.close();
            }, 10000); // wait 10 seconds (60000 milliseconds)
        </script>';
} else {
    echo "❌ Failed to insert bash ordre.";
}

exit(); 

?>
</body>
</html>
