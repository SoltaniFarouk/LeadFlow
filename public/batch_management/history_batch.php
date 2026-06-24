<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BM : HISTORY BATCH</title>
    <link rel="icon" type="image/png" href="../images/loglog.png">
    <link rel="stylesheet" href="../css/monitor_batch_management.css">
</head>
<body>
    <h1>HISTORY BATCH</h1>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '7024M');

/** ---------- Database connection helper ---------- */
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
                error_log("DB connection error to {$db}@{$host}: " . $e->getMessage());
                return null;
            }
        }
    }
    return null;
}

/** ---------- Specific DB connection ---------- */
function connConfigLeadFr(): ?PDO {
    return createPdoConnection("localhost", "dbx_config_lead_fr", "root", "123456fF$$");
}

/** ---------- Get & sanitize GET params ---------- */
$id_bash = isset($_GET['var1']) ? intval($_GET['var1']) : 0;
$idUserConnecte = isset($_GET['var2']) ? intval($_GET['var2']) : 0;

echo "Lot ID: $id_bash <br>";
echo "User ID Connecté: $idUserConnecte <br>";

// Connect to DB
$pdo = connConfigLeadFr();
if ($pdo === null) {
    die("Database connection failed.");
}

// Fetch batch history
$stmt = $pdo->prepare("SELECT * FROM `tb_bash_history` WHERE id_bash = :id_bash ORDER BY id DESC");
$stmt->bindParam(':id_bash', $id_bash, PDO::PARAM_INT);
$stmt->execute();
$historyEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($historyEntries)) {
    echo "<p>No history found for batch ID: $id_bash</p>";
} else {
    echo "<h2>History for Batch ID: $id_bash</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>ID</th>
            <th>Batch</th>
            <th>User </th>
            <th>CRM</th>
            <th>Added to CRM</th>
            <th>Promises</th>
            <th>SDA</th>
            <th>OK</th> 
            <th>Answered</th>
            <th>404</th>
            <th>LAST CALL</th>
            <th>Last Call Date</th>
            <th>Created At</th>
          </tr>";

    foreach ($historyEntries as $entry) {
        echo "<tr>";
            echo "<td>" . htmlspecialchars($entry['id']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['id_bash']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['id_user']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['id_crm']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_added_crm']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_promises']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_sda']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_ok']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_answer']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['number_404']) . "</td>";  
            echo "<td>" . htmlspecialchars($entry['last_call_date']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['updated_at']) . "</td>";

        echo "</tr>";
    }
    echo "</table>";



}
?>

</body>
</html>
