<?php

/** ---------- Runtime & Errors ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);                  // long import
ini_set('memory_limit', '4024M');   // adjust if needed
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

/** ---------- ConfigLeadFrDatabase connection ---------- */
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
            usleep(500000); // 0.5s
            if ($retries === 0) {
                die('Erreur ConfigLeadFrDatabase - db :  ' . htmlspecialchars($e->getMessage()));
            }
        }
    }
    
}

/** ---------- ConfigLeadFrDatabase connection ---------- */
function connDepAuto($name_db): PDO {
    $retries = 3;
    while ($retries > 0) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;port=3306;dbname='.$name_db.';charset=UTF8',
                'root',
                '123456fF$$',
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query('SET SQL_BIG_SELECTS=1');
            return $pdo;
        } catch (PDOException $e) {
            $retries--;
            usleep(500000); // 0.5s
            if ($retries === 0) {
                die('Erreur ConfigLeadFrDatabase - db :  ' . htmlspecialchars($e->getMessage()));
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

function updateBashOrdreStatus(int $idOrdre, int $status = 1): bool {
    try {
        $connConfigLead = connConfigLeadFr(); // your DB connection function

        $sql = "UPDATE tb_bash_ordre 
                SET status = :status, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :idOrdre";

        $stmt = $connConfigLead->prepare($sql);
        return $stmt->execute([
            ':status'   => $status,
            ':idOrdre'  => $idOrdre
        ]);

    } catch (PDOException $e) {
        error_log("Failed to update bash ordre status: " . $e->getMessage());
        return false;
    }
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
        ':phone_code'              => '33',
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

function getLastPendingBashOrdre(): ?array {
    try {
        $conn = connConfigLeadFr();

        $sql = "SELECT id AS idOrdre, database_name AS selectDataBase, table_name AS nameTable,
                       phone_code, number_use, id_lot, extra, id_user_connecte
                FROM tb_bash_ordre
                WHERE status = 0
                ORDER BY id DESC
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $ordre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ordre) {
            return $ordre; // associative array with all fields
        } else {
            return null; // no pending ordre found
        }
    } catch (PDOException $e) {
        error_log("Fetch last pending bash ordre failed: " . $e->getMessage());
        return null;
    }
}


/** ---------- Vicidial stats (optional) ---------- */
$connVicidial = connBackupVicidialIse();
$connConfigLead = connConfigLeadFr();
$campaignId = '20250101'; // TODO: pass via GET if needed
$dateDateAndTime = date('Y-m-d H:i:s');


// ?var1=dbx_dep50&var2=tb_batch_1&var3=332&var4=25000&var5=198&var6=0
/*

$selectDataBase  = 'dbx_dep50'; //= $_GET['var1'] ?? '';
$nameTable       = 'tb_batch_1'; //= $_GET['var2'] ?? '';
$phoneCode       = '332'; //= $_GET['var3'] ?? '';
$numberUse       = '25000'; //= $_GET['var4'] ?? ''; // not used below; keeping for compatibility
$idLot           = '198'; //= $_GET['var5'] ?? '';
$extra           = '0'; //= $_GET['var6'] ?? '';
$idUserConnecte  = '50'; //= $_GET['var7'] ?? '';
*/

// Usage example
$lastOrdre = getLastPendingBashOrdre();
if ($lastOrdre) {
    $idOrdre        = $lastOrdre['idOrdre'];
    $selectDataBase = $lastOrdre['selectDataBase'];
    $nameTable      = $lastOrdre['nameTable'];
    $phoneCode      = $lastOrdre['phone_code'];
    $numberUse      = $lastOrdre['number_use'];
    $idLot          = $lastOrdre['id_lot'];
    $extra          = $lastOrdre['extra'];
    $idUserConnecte = $lastOrdre['id_user_connecte'];

    // Example: print values
    echo "ID Ordre: $idOrdre <br> \n ";
    echo "Database: $selectDataBase <br> \n";
    echo "Table: $nameTable <br> \n";
    echo "Phone Code: $phoneCode <br> \n";
    echo "Number Use: $numberUse <br> \n";
    echo "ID Lot: $idLot <br> \n";
    echo "Extra: $extra <br> \n";
    echo "User Connected ID: $idUserConnecte <br> \n";
} else {
    echo "No pending bash ordre found.  \n";
    exit(); // Exit if no pending ordre found
}


$pdo = connDepAuto($selectDataBase);
/** ---------- Repository ---------- */
$repo        = new BatchRepository($pdo, $selectDataBase, $nameTable);
$controller  = new BatchController($repo);

/** ---------- Load data ---------- */
try {
    $batches = $controller->getAll();
    echo "<p>Total records found: " . count($batches) . "</p> \n";
    echo "<h3>Sample Data (first 5):</h3><ul> \n";
    foreach (array_slice($batches, 0, 5) as $batch) {
        echo "<li>" . htmlspecialchars($batch->getTel1()) . " - "
            . htmlspecialchars($batch->getNom() . ' ' . $batch->getPrenom()) . "</li> \n";
    }
    echo "</ul> \n";
} catch (Exception $e) {
    die("<p style='color:red;'>Error fetching batches: " . htmlspecialchars($e->getMessage()) . "</p> \n");
}

/** ---------- Build list meta ---------- */
$dateAddVicidial = date('Ymd');
$varPostalCode   = getDepNumber($selectDataBase);
if(empty($extra)){
    $varNumberUse    = '01';
}elseif($extra<10){
    $varNumberUse    = '0'.$extra;
}else{
    $varNumberUse    = $extra;
}

$varIdLot        = getBatchNumber($nameTable);

$listId          = $dateAddVicidial . $phoneCode . $varPostalCode . $varNumberUse . $varIdLot;
$listName        = $dateAddVicidial . '_' . $phoneCode . '_DEP' . $varPostalCode . '_' . $varNumberUse . '_' . $varIdLot;
$listDescription = $varPostalCode;

echo "- List Id : " . htmlspecialchars($listId) ." <br> \n";
echo "- List Name : " . htmlspecialchars($listName) . " <br> \n";
echo "- List Description : " . htmlspecialchars($listDescription) . " <br> \n";

/** ---------- Ensure list exists ---------- */
$checkLists = 0;

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
        echo "<h3>List $listId created</h3> \n";
    } catch (Throwable $e) {
        die("<p style='color:red;'>Create list failed: " . htmlspecialchars($e->getMessage()) . "</p>");
    }
} else {
    echo "<p style='color: red;'>List already exists in Vicidial.</p> \n";
    // continue to insert leads into the existing list
}

/** ---------- Insert leads (chunked) ---------- */
if (empty($batches)) {
    echo "<p>No records found.</p> \n";
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

echo "<h3>Importing…</h3> \n";

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
    echo "<p>Progress: $idx / $total</p> \n";
    flush();
}

echo "<h3>Done.</h3>    \n";
echo "<p>Inserted: " . (int)$inserted . " | Skipped (exists/empty): " . (int)$skipped . " | Errors: " . (int)$errors . "</p> \n";

// ---------- UPDATE tb_batch_management ----------
        $new_number_uses = $extra + 1;
        $sql = "UPDATE tb_batch_management 
                   SET number_uses = '$new_number_uses', 
                       date_last_use = '$dateDateAndTime' 
                   WHERE id = '$idLot'";

$reponse = $connConfigLead->query($sql);

if ($reponse) {
    echo "Update tb_batch_management successful for ID $idLot. \n";
} else {
    echo "Error tb_batch_management: " . $connConfigLead->error . "\n";
}
     
// ---------- ADD batch history ----------
$sqlInsertHistory = "INSERT INTO tb_bash_history
                        (id_bash, id_user, id_crm, number_added_CRM, number_promises, number_sda, number_ok, number_answer) 
                     VALUES 
                        (:id_bash, :id_user, :id_crm, 0, 0, 0, 0, 0)";

$stmtInsert = $connConfigLead->prepare($sqlInsertHistory);
$stmtInsert->execute([
    ':id_bash' => $idLot,             
    ':id_user' => $idUserConnecte,     
    ':id_crm'  => $listId       
]);

if (updateBashOrdreStatus($idOrdre, 1)) {
    echo "✅ Status updated successfully for ordre ID $idOrdre. \n";
} else {
    echo "❌ Failed to update status for ordre ID $idOrdre.     \n";
}

// update last_id_crm in tb_batch_management
$sqlUpdateBatch = "UPDATE tb_batch_management 
                   SET last_id_crm = :last_id_crm 
                   WHERE id = :id"; 
$stmtUpdate = $connConfigLead->prepare($sqlUpdateBatch);
$stmtUpdate->execute([
    ':last_id_crm' => $listId,
    ':id'          => $idLot
]);     
if ($stmtUpdate->rowCount() > 0) {
    echo "✅ last_id_crm updated successfully for batch ID $idLot. \n";
} else {
    echo "❌ Failed to update last_id_crm for batch ID $idLot. \n";
}

//  Close the connection ----------
$connVicidial = null;
$pdo = null;


?>