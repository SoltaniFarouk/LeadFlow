<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suivi Batch Management</title>
    <link rel="icon" type="image/png" href="../images/loglog.png">
    <link rel="stylesheet" href="../css/monitor_batch_management.css">
</head>
<body>
<h1>Add Batch To Vicidial</h1>

<?php
/** ---------- Runtime & Errors ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);                  // long import
ini_set('memory_limit', '1024M');   // adjust if needed
ob_implicit_flush(true);

/** ---------- Autoload ---------- */
require_once '/var/www/html/phone_extractor/config/autoload.php';

use App\Repository\BatchRepository;
use App\Controller\BatchController;

/** ---------- Vicidial connection ---------- */
function connBackupVicidialIse(): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=192.168.1.131;port=3306;dbname=asterisk;charset=UTF8',
                'admin_dev',
                'wC5UKG664eAwc2d',
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000); // 0.5s
            if ($retries === 0) {
                die('Erreur 131 - db : asterisk ' . htmlspecialchars($e->getMessage()));
            }
        }
    }
}

/** ---------- Helpers ---------- */
function getDepNumber(string $str): string {
    if (preg_match('/dbx_dep(\d+)/i', $str, $m)) {
        return str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    return '';
}
function getBatchNumber(string $str): string {
    if (preg_match('/_(\d+)$/', $str, $m)) {
        return str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    return '00';
}

/**
 * Insert one record in vicidial_list (prepared statement reused)
 * Returns true if inserted, false if skipped/exists.
 */
function add_new_phone(
    PDO $pdo,
    PDOStatement $stmtInsert,
    string $dateDateAndTime,
    string $listId,
    string $phone_number,
    string $customer_name,
    string $address,
    string $postal_code,
    string $city
): bool {
    // Insert; if your schema has a unique index on (list_id, phone_number),
    // switch to INSERT ... ON DUPLICATE KEY UPDATE last_local_call_time = last_local_call_time
    // to skip existence checks completely.
    $stmtInsert->execute([
        ':entry_date'              => $dateDateAndTime,
        ':modify_date'             => '0000-00-00 00:00:00',
        ':status'                  => 'NEW',
        ':user'                    => '',
        ':vendor_lead_code'        => '',
        ':source_id'               => '',
        ':list_id'                 => $listId,
        ':gmt_offset_now'          => '-5.00',
        ':called_since_last_reset' => 'N',
        ':phone_code'              => '1',
        ':phone_number'            => $phone_number,
        ':title'                   => '',
        ':first_name'              => $customer_name,
        ':middle_initial'          => '',
        ':last_name'               => '',
        ':address1'                => $address,
        ':address2'                => '',
        ':address3'                => '',
        ':city'                    => $city,
        ':state'                   => '',
        ':province'                => '',
        ':postal_code'             => $postal_code,
        ':country_code'            => '',
        ':gender'                  => 'U',
        ':date_of_birth'           => '0000-00-00',
        ':alt_phone'               => '',
        ':email'                   => '',
        ':security_phrase'         => '',
        ':comments'                => '',
        ':called_count'            => 0,
        ':last_local_call_time'    => '2008-01-01 00:00:00',
        ':rank'                    => '0',
        ':owner'                   => '',
        ':entry_list_id'           => 0
    ]);
    return true;
}

/** ---------- Params ---------- */
$dateDateAndTime = date('Y-m-d H:i:s');
$selectDataBase  = $_GET['var1'] ?? '';
$nameTable       = $_GET['var2'] ?? '';
$phoneCode       = $_GET['var3'] ?? '';
$numberUse       = $_GET['var4'] ?? ''; // not used below; keeping for compatibility
$idLot           = $_GET['var5'] ?? '';
$extra           = $_GET['var6'] ?? '';

echo "- Data Base: " . htmlspecialchars($selectDataBase) . "<br>";
echo "- Table: " . htmlspecialchars($nameTable) . "<br>";
echo "- Phone Code: " . htmlspecialchars($phoneCode) . "<br>";
echo "- Number Use: " . htmlspecialchars($numberUse) . "<br>";
echo "- ID Lot: " . htmlspecialchars($idLot) . "<br>";
echo "- Extra: " . htmlspecialchars($extra) . "<br>";

/** ---------- Vicidial stats (optional) ---------- */
$connVicidial = connBackupVicidialIse();
$campaignId = '20250101'; // TODO: pass via GET if needed

try {
    $stmtStats = $connVicidial->prepare(
        "SELECT dialable_leads,
                drops_today_pct,
                CASE WHEN agent_calls_today > 0
                     THEN ROUND(agent_wait_today / agent_calls_today, 2)
                     ELSE 0 END AS avg_wait_time_seconds
         FROM vicidial_campaign_stats
         WHERE campaign_id = :cid"
    );
    $stmtStats->execute([':cid' => $campaignId]);
    if ($row = $stmtStats->fetch(PDO::FETCH_ASSOC)) {
        echo " - dialable_leads: " . htmlspecialchars($row['dialable_leads']) . "<br>";
        echo " - drops_today_pct: " . htmlspecialchars($row['drops_today_pct']) . "<br>";
        echo " - avg_wait_time_seconds: " . htmlspecialchars($row['avg_wait_time_seconds']) . "<br>";
    }
} catch (Throwable $e) {
    echo "<p style='color:#c00'>Stats read failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

/** ---------- Dynamic source DB connection ---------- */
try {
    $namespace = 'App\\Connection\\';
    if (preg_match('/dbx_dep(\d+)/i', $selectDataBase, $m)) {
        $depNumber = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $className = $namespace . "Dep{$depNumber}Database";
        if (!class_exists($className)) {
            throw new Exception("Database connection class not found: $className");
        }
        $db  = new $className();
        $pdo = $db->connect();
    } else {
        throw new Exception("Invalid database parameter: $selectDataBase");
    }
} catch (Exception $e) {
    die("<p style='color:red;'>DB Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}

/** ---------- Repository ---------- */
$repo        = new BatchRepository($pdo, $selectDataBase, $nameTable);
$controller  = new BatchController($repo);

/** ---------- Load data ---------- */
try {
    $batches = $controller->getAll();
    echo "<p>Total records found: " . count($batches) . "</p>";
    echo "<h3>Sample Data (first 5):</h3><ul>";
    foreach (array_slice($batches, 0, 5) as $batch) {
        echo "<li>" . htmlspecialchars($batch->getTel1()) . " - "
            . htmlspecialchars($batch->getNom() . ' ' . $batch->getPrenom()) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    die("<p style='color:red;'>Error fetching batches: " . htmlspecialchars($e->getMessage()) . "</p>");
}

/** ---------- Build list meta ---------- */
$dateAddVicidial = date('Ymd');
$varPostalCode   = getDepNumber($selectDataBase);
$varNumberUse    = '01'; // keep simple, can be GET later
$varIdLot        = getBatchNumber($nameTable);

$listId          = $dateAddVicidial . $phoneCode . $varPostalCode . $varNumberUse . $varIdLot;
$listName        = $dateAddVicidial . '_' . $phoneCode . '_DEP' . $varPostalCode . '_' . $varNumberUse . '_' . $varIdLot;
$listDescription = $varPostalCode;

echo '- List Id : ' . htmlspecialchars($listId) . '<br>';
echo '- List Name : ' . htmlspecialchars($listName) . '<br>';
echo '- List Description : ' . htmlspecialchars($listDescription) . '<br>';

/** ---------- Ensure list exists ---------- */
$checkLists = 0;
try {
    $stmtCheckList = $connVicidial->prepare("SELECT COUNT(*) FROM vicidial_lists WHERE list_id = :list_id");
    $stmtCheckList->execute([':list_id' => $listId]);
    $checkLists = (int)$stmtCheckList->fetchColumn();
} catch (Throwable $e) {
    die("<p style='color:red;'>List check failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}

if ($checkLists === 0) {
    echo '<p style="color: green;">List does not exist in Vicidial, creating…</p>';
    try {
        $stmtCreateList = $connVicidial->prepare(
            'INSERT INTO vicidial_lists (
                list_id, list_name, campaign_id, active, list_description, list_changedate,
                list_lastcalldate, reset_time, agent_script_override, campaign_cid_override,
                am_message_exten_override, drop_inbound_group_override, xferconf_a_number,
                xferconf_b_number, xferconf_c_number, xferconf_d_number, xferconf_e_number,
                web_form_address, web_form_address_two, time_zone_setting, inventory_report,
                expiration_date, na_call_url, local_call_time, web_form_address_three, status_group_id
             ) VALUES (
                :list_id, :list_name, :campaign_id, :active, :list_description, :list_changedate,
                NULL, :reset_time, :agent_script_override, :campaign_cid_override,
                :am_message_exten_override, :drop_inbound_group_override, :xferconf_a_number,
                :xferconf_b_number, :xferconf_c_number, :xferconf_d_number, :xferconf_e_number,
                NULL, NULL, :time_zone_setting, :inventory_report,
                :expiration_date, NULL, :local_call_time, NULL, :status_group_id
             )'
        );
        $stmtCreateList->execute([
            ':list_id'                    => $listId,
            ':list_name'                  => $listName,
            ':campaign_id'                => $campaignId,
            ':active'                     => 'Y',
            ':list_description'           => $listDescription,
            ':list_changedate'            => $dateDateAndTime,
            ':reset_time'                 => '',
            ':agent_script_override'      => '',
            ':campaign_cid_override'      => '',
            ':am_message_exten_override'  => '',
            ':drop_inbound_group_override'=> '',
            ':xferconf_a_number'          => '',
            ':xferconf_b_number'          => '',
            ':xferconf_c_number'          => '',
            ':xferconf_d_number'          => '',
            ':xferconf_e_number'          => '',
            ':time_zone_setting'          => 'COUNTRY_AND_AREA_CODE',
            ':inventory_report'           => 'Y',
            ':expiration_date'            => '2099-12-31',
            ':local_call_time'            => 'campaign',
            ':status_group_id'            => ''
        ]);
        echo "<h3>List $listId created</h3>";
    } catch (Throwable $e) {
        die("<p style='color:red;'>Create list failed: " . htmlspecialchars($e->getMessage()) . "</p>");
    }
} else {
    echo '<p style="color: red;">List already exists in Vicidial.</p>';
    // continue to insert leads into the existing list
}

/** ---------- Insert leads (chunked) ---------- */
if (empty($batches)) {
    echo "<p>No records found.</p>";
    exit;
}

$insertSql = 'INSERT INTO vicidial_list (
    entry_date, modify_date, status, user, vendor_lead_code, source_id, list_id, gmt_offset_now,
    called_since_last_reset, phone_code, phone_number, title, first_name, middle_initial, last_name,
    address1, address2, address3, city, state, province, postal_code, country_code, gender,
    date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time, rank,
    owner, entry_list_id
) VALUES (
    :entry_date, :modify_date, :status, :user, :vendor_lead_code, :source_id, :list_id, :gmt_offset_now,
    :called_since_last_reset, :phone_code, :phone_number, :title, :first_name, :middle_initial, :last_name,
    :address1, :address2, :address3, :city, :state, :province, :postal_code, :country_code, :gender,
    :date_of_birth, :alt_phone, :email, :security_phrase, :comments, :called_count, :last_local_call_time, :rank,
    :owner, :entry_list_id
)';

$stmtInsert = $connVicidial->prepare($insertSql);

// (Optional) quick existence check prepared statement (faster than string concat)
$stmtExists = $connVicidial->prepare(
    "SELECT COUNT(*) FROM vicidial_list WHERE list_id = :list_id AND phone_number = :phone_number"
);

$inserted = 0;
$skipped  = 0;
$errors   = 0;
$chunk    = 1000; // process in chunks to keep transactions short
$total    = count($batches);
$idx      = 0;

echo "<h3>Importing…</h3>";

while ($idx < $total) {
    $connVicidial->beginTransaction();
    try {
        $upper = min($idx + $chunk, $total);
        for ($i = $idx; $i < $upper; $i++) {
            $batch = $batches[$i];

            $phone_number  = (string)$batch->getTel1();
            if ($phone_number === '') { $skipped++; continue; }

            // exists ?
            $stmtExists->execute([':list_id' => $listId, ':phone_number' => $phone_number]);
            if ((int)$stmtExists->fetchColumn() > 0) { $skipped++; continue; }

            $customer_name = trim($batch->getNom() . ' ' . $batch->getPrenom());
            $address       = (string)$batch->getAdresse();
            $postal_code   = (string)$batch->getCodePostal();
            $city          = (string)$batch->getVille();

            try {
                add_new_phone(
                    $connVicidial,
                    $stmtInsert,
                    $dateDateAndTime,
                    $listId,
                    $phone_number,
                    $customer_name,
                    $address,
                    $postal_code,
                    $city
                );
                $inserted++;
            } catch (Throwable $e) {
                $errors++;
                // Optionally log: error_log($e->getMessage());
            }
        }
        $connVicidial->commit();
    } catch (Throwable $e) {
        $connVicidial->rollBack();
        $errors += ($upper - $idx); // pessimistic
        // Optionally log: error_log("Chunk failed: " . $e->getMessage());
    }
    $idx = $upper;
    echo "<p>Progress: $idx / $total</p>";
    flush();
}

echo "<h3>Done.</h3>";
echo "<p>Inserted: " . (int)$inserted . " | Skipped (exists/empty): " . (int)$skipped . " | Errors: " . (int)$errors . "</p>";

/** ---------- Tiny preview table (first 20) to keep UI snappy ---------- */
echo "<h3>Preview (first 20 rows)</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th>#</th><th>Tel1</th><th>CP</th><th>Ville</th><th>Adresse</th>
        <th>Genre</th><th>Nom</th><th>Prenom</th>
      </tr>";
$preview = array_slice($batches, 0, 20);
$k = 0;
foreach ($preview as $batch) {
    $k++;
    echo "<tr>";
    echo "<td>" . $k . "</td>";
    echo "<td>" . htmlspecialchars($batch->getTel1()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getCodePostal()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getVille()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getAdresse()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getGenre()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getNom()) . "</td>";
    echo "<td>" . htmlspecialchars($batch->getPrenom()) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

</body>
</html>