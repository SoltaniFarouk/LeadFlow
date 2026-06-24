<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suivi Batch Management</title>
    <link rel="icon"  type="image/png" href="../images/loglog.png">
    <link rel="stylesheet" href="../css/monitor_batch_management.css">
</head>
<body>
    <h1> Add Batch To Vicidial </h1>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '4024M');   // adjust if needed
ob_implicit_flush(true);

require_once '/var/www/html/phone_extractor/config/autoload.php';

use App\Repository\BatchRepository;
use App\Controller\BatchController;

    function connBackupVicidialIse(){
        $retries = 3;
        while ($retries > 0){
            try{
                $pdo = new PDO('mysql:host=192.168.1.131;port=3306;dbname=asterisk;charset=UTF8', 'admin_dev', 'wC5UKG664eAwc2d', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->query('SET SQL_BIG_SELECTS=1');
                return $pdo;
            }
            catch (PDOException $e)
            {
                $retries--;
                usleep(500); 
            }
  			if ($retries == 1 ) { die('Erreur 131 - db : asterisk '.$e->getMessage());   }
        }
    } 

    function getDepNumber(string $str): string {
        if (preg_match('/dbx_dep(\d+)/i', $str, $matches)) {
            // Ensure 2 digits (01, 02, ...)
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }
        return ''; // return empty string if no match
    }
    
    function getBatchNumber(string $str): string {
        if (preg_match('/_(\d+)$/', $str, $matches)) {
        return str_pad($matches[1], 2, '0', STR_PAD_LEFT); // pad with leading zero
        }
        return '00'; // default if no match
    }

    function add_new_phone($dateDateAndTime,$listId,$phone_number,$customer_name,$address,$postal_code,$city,$conn_database) {
            $connVicidial = $conn_database;
						    try{
						        $req = $connVicidial->prepare('INSERT INTO vicidial_list ( `entry_date`, `modify_date`, `status`, `user`, `vendor_lead_code`, `source_id`, `list_id`, `gmt_offset_now`, `called_since_last_reset`, `phone_code`, `phone_number`, `title`, `first_name`, `middle_initial`, `last_name`, `address1`, `address2`, `address3`, `city`, `state`, `province`, `postal_code`, `country_code`, `gender`, `date_of_birth`, `alt_phone`, `email`, `security_phrase`, `comments`, `called_count`, `last_local_call_time`, `rank`, `owner`, `entry_list_id`) VALUES ( :entry_date,:modify_date,:status,:user,:vendor_lead_code,:source_id,:list_id,:gmt_offset_now,:called_since_last_reset,:phone_code,:phone_number,:title,:first_name,:middle_initial,:last_name,:address1,:address2,:address3,:city,:state,:province,:postal_code,:country_code,:gender,:date_of_birth,:alt_phone,:email,:security_phrase,:comments,:called_count,:last_local_call_time,:rank,:owner,:entry_list_id )')
						        or exit(print_r($conn->errorInfo()));
						        $req->execute(array(
						            'entry_date' => $dateDateAndTime,
						            'modify_date' => '0000-00-00 00:00:00',
						            'status' => 'NEW',
						            'user' => '',
						            'vendor_lead_code' => '',
						            'source_id' => '',
						            'list_id' => $listId,
						            'gmt_offset_now' => '-5.00',
						            'called_since_last_reset' => 'N',
						            'phone_code' => '1',
						            'phone_number' => $phone_number,
						            'title' => '',
						            'first_name' => $customer_name,
						            'middle_initial' => '',
						            'last_name' => '',
						            'address1' => $address,
						            'address2' => '',
						            'address3' => '',
						            'city' => $city,
						            'state' => '',
						            'province' => '',
						            'postal_code' => $postal_code,
						            'country_code' => '',
						            'gender' => 'U',
						            'date_of_birth' => '0000-00-00',
						            'alt_phone' => '',
						            'email' => '',
						            'security_phrase' => '',
						            'comments' => '',
						            'called_count' =>  0,
						            'last_local_call_time' => '2008-01-01 00:00:00',
						            'rank' => '0',
						            'owner' => '',
						            'entry_list_id' => 0
						        ));

						        $checkAddphone = $connVicidial->query("SELECT count(*) FROM vicidial_list where entry_date = '$dateDateAndTime' and phone_number ='$phone_number' ")->fetchColumn();
						    }catch(PDOException $e){
						        echo "The user could not be added.<br> ".$e->getMessage();
						        $checkAddphone = $e;
						    }
						    
						    $connVicidial = null;
						    return $checkAddphone;
						}

// 1. Get params
$dateDateAndTime = date('Y-m-d H:i:s');
$nbr = 0;
$selectDataBase = $_GET['var1'] ?? '';
$nameTable      = $_GET['var2'] ?? '';
$phoneCode      = $_GET['var3'] ?? '';
$numberUse      = $_GET['var4'] ?? '';
$idLot          = $_GET['var5'] ?? '';
$extra          = $_GET['var6'] ?? '';

echo "- Data Base: $selectDataBase<br>";
echo "- Table: $nameTable<br>";
echo "- Phone Code: $phoneCode<br>";
echo "- Number Use: $numberUse<br>";
echo "- ID Lot: $idLot<br>";
echo "- Extra: $extra<br>";
$connVicidial = connBackupVicidialIse();
        $reponse_infocrm = $connVicidial->query("SELECT dialable_leads, drops_today_pct, ROUND(agent_wait_today / agent_calls_today, 2) AS avg_wait_time_seconds  FROM vicidial_campaign_stats  WHERE campaign_id = '20250101'");
			    $row_infocrm = $reponse_infocrm->fetch(PDO::FETCH_ASSOC);				                    
			        $dialable_leads = $row_infocrm['dialable_leads'];       
			        $drops_today_pct = $row_infocrm['drops_today_pct']; 
                    $avg_wait_time_seconds = $row_infocrm['avg_wait_time_seconds']; 

                    echo " - dialable_leads: $dialable_leads <br>";
                    echo " - drops_today_pct: $drops_today_pct <br>";
                    echo " - avg_wait_time_seconds: $avg_wait_time_seconds <br>";




// 2. Dynamically choose DB connection class
try {
    $namespace = 'App\\Connection\\';

    // Example: var1=dbx_dep01 → Dep01Database
    if (preg_match('/dbx_dep(\d+)/i', $selectDataBase, $m)) {
        $depNumber = str_pad($m[1], 2, '0', STR_PAD_LEFT); // 01, 02, etc.
        $className = $namespace . "Dep{$depNumber}Database";

        if (!class_exists($className)) {
            throw new Exception("Database connection class not found: $className");
        }

        $db = new $className();
        $pdo = $db->connect(); // ✅ use connect() instead of getConnection()
    } else {
        throw new Exception("Invalid database parameter: $selectDataBase");
    }

} catch (Exception $e) {
    die("<p style='color:red;'>DB Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// 3. Load Repository + Controller
$repo = new BatchRepository($pdo, $selectDataBase, $nameTable);
$controller = new BatchController($repo);

// 4. Get data
try {
    $batches = $controller->getAll();

    echo "<p>Total records found: " . count($batches) . "</p>";
    echo "<h3>Sample Data:</h3><ul>";
    foreach (array_slice($batches, 0, 5) as $batch) {
        echo "<li>" . htmlspecialchars($batch->getTel1()) . " - " . 
             htmlspecialchars($batch->getNom()) . " " . 
             htmlspecialchars($batch->getPrenom()) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error fetching batches: " . htmlspecialchars($e->getMessage()) . "</p>";
}

        $dateAddVicidial = date('Ymd');    //   echo '- date Add Vicidial : ';   echo $dateAddVicidial;
        $varPostalCode = getDepNumber($selectDataBase);
        $varNumberUse = '01'; // Example: 01, 02, etc.
        $varIdLot = getBatchNumber($nameTable); // Example: 01, 02, etc.

        // List ID:     20230124 334 83 10 06   20230124 334 83 10 06 // 20230304 333 02 00 05
		$listId = $dateAddVicidial . $phoneCode . $varPostalCode . $varNumberUse . $varIdLot;  
        echo '- List Id : ';   echo $listId;   echo '<br>';

		// List Name:	20230124_334_DEP83_10_06
		$listName = $dateAddVicidial .'_'. $phoneCode .'_DEP'. $varPostalCode .'_'. $varNumberUse .'_'. $varIdLot;  
        echo '- List Name : ';   echo $listName;   echo '<br>';						
		// List Description:	83
		$listDescription = $varPostalCode;   
        echo '- List Description : ';   echo $listDescription;   echo '<br>'; 

       $checkLists = $connVicidial->query(" select count(*) from vicidial_lists where list_id='$listId' ")->fetchColumn();
		echo '- checkLists ';   echo $checkLists;   echo '<br>';
						
		if(empty($checkLists)){
            echo '<p style="color: green;">List does not exist in Vicidial, proceeding to add...</p>';
            // Add list to Vicidial
            try{						     
						        $req = $connVicidial->prepare('INSERT INTO vicidial_lists ( list_id, list_name, campaign_id, active, list_description, list_changedate, list_lastcalldate, reset_time, agent_script_override, campaign_cid_override, am_message_exten_override, drop_inbound_group_override, xferconf_a_number, xferconf_b_number, xferconf_c_number, xferconf_d_number, xferconf_e_number, web_form_address, web_form_address_two, time_zone_setting, inventory_report, expiration_date, na_call_url, local_call_time, web_form_address_three, status_group_id ) VALUES (:list_id,:list_name,:campaign_id,:active,:list_description,:list_changedate,:list_lastcalldate,:reset_time,:agent_script_override,:campaign_cid_override,:am_message_exten_override,:drop_inbound_group_override,:xferconf_a_number,:xferconf_b_number,:xferconf_c_number,:xferconf_d_number,:xferconf_e_number,:web_form_address,:web_form_address_two,:time_zone_setting,:inventory_report,:expiration_date,:na_call_url,:local_call_time,:web_form_address_three,:status_group_id)')
						        or exit(print_r($conn->errorInfo()));
						            $req->execute(array(
						                'list_id' => $listId,
						                'list_name' => $listName,
						                'campaign_id' => '20250101',
						                'active' => 'Y',
						                'list_description' => $listDescription,
						                'list_changedate' => $dateDateAndTime, //NULL,
						                'list_lastcalldate' => NULL,
						                'reset_time' => '',
						                'agent_script_override' => '',
						                'campaign_cid_override' => '',
						                'am_message_exten_override' => '',
						                'drop_inbound_group_override' => '',
						                'xferconf_a_number' => '',
						                'xferconf_b_number' => '',
						                'xferconf_c_number' => '',
						                'xferconf_d_number' => '',
						                'xferconf_e_number' => '',
						                'web_form_address' => NULL,
						                'web_form_address_two' => NULL,
						                'time_zone_setting' => 'COUNTRY_AND_AREA_CODE',
						                'inventory_report' => 'Y',
						                'expiration_date' => '2099-12-31',
						                'na_call_url' => NULL,
						                'local_call_time' => 'campaign',
						                'web_form_address_three' => NULL,
						                'status_group_id' => ''
						            ));
						     
						        echo "<H3> Creation list $listId Dane </H3>";						     
						    }catch(PDOException $e){
						        echo "The user could not be added.<br> ".$e->getMessage();
						    }




        }else{
            echo '<p style="color: red;">List already exists in Vicidial.</p>';
            exit;
        }

// 5. Display all rows line by line

echo "<h3>All Records:</h3>";
if (!empty($batches)) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>N</th>
            <th>Tel1</th>
            <th> C.P </th>
            <th>Ville</th>
            <th>Adresse</th>
            <th>Genre</th>
            <th>Nom</th>
            <th>Prenom</th>
            <th>Tel2</th>
            <th>Tel3</th>
            <th>Mobile</th>
            <th>Fax</th>
            <th>Habitat</th>
            <th>Age Moyen</th>
            <th>Ethnie</th>
            <th>Tel1 Prospection</th>
            <th>Tel2 Prospection</th>
            <th>Tel3 Prospection</th>
            <th>Mobile Prospection</th>
            <th>Fax Prospection</th>
            <th>Fax LOG </th>
          </tr>";

    foreach ($batches as $batch) {
        $nbr++;
        $phone_number = $batch->getTel1();
        $customer_name = $batch->getNom() . ' ' . $batch->getPrenom();
        $address = $batch->getAdresse();    
        $postal_code = $batch->getCodePostal();
        $city = $batch->getVille();         
        //----------------------------------
        echo "<tr>";
        echo "<td>" . htmlspecialchars($nbr) . "</td>";
        echo "<td>" . htmlspecialchars($phone_number) . "</td>";
        echo "<td>" . htmlspecialchars($postal_code) . "</td>";
        echo "<td>" . htmlspecialchars($city) . "</td>";
        echo "<td>" . htmlspecialchars($address) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getGenre()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getNom()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getPrenom()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getTel2()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getTel3()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getMobile()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getFax()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getHabitat()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getAgeMoyen()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getEthnie()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getTel1Prospection()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getTel2Prospection()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getTel3Prospection()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getMobileProspection()) . "</td>";
        echo "<td>" . htmlspecialchars($batch->getFaxProspection()) . "</td>";
        

        $checkAddphone = $connVicidial->query("SELECT count(*) FROM vicidial_list where list_id = '$listId' and phone_number ='$phone_number' ")->fetchColumn();
						        
		if(empty($checkAddphone)){
			$msgAddNewPhone = add_new_phone($dateDateAndTime,$listId,$phone_number,$customer_name,$address,$postal_code,$city, $connVicidial);		 				            
			echo "<td>"; echo '- phone_number : ';   echo $phone_number;   echo '- msg Add Phone -> '; echo $msgAddNewPhone; echo "</td>";
		}else{
			echo "<td>"; echo '- phone_number : ';   echo $phone_number;   echo '- Existe D&eacute;j&agrave; '; echo "</td>";
		}

        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No records found.</p>";
}
  


?>
</body>
</html>