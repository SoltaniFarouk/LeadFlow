    <link rel="icon" type="image/png" href="images/loglog.png">
<?php
    session_start(); //session verification    
    if( isset( $_SESSION['username'] ) ){ $token = $_SESSION['username']; } else { header("Location: index.php");  }  	
    if(isset($_POST['logout'])){ header('Location: logout.php'); exit; }
    //  echo 'TOKEN ="' . htmlspecialchars($token) . '" <br>';

    //require_once __DIR__ . '/app/Connection/ConfigLeadFrDatabase.php';
    require_once __DIR__ . '/../app/Connection/ConfigLeadFrDatabase.php';
    require_once __DIR__ . '/../app/Connection/ConBdIdashboardProd1.php';

    use App\Connection\ConfigLeadFrDatabase;
    use App\Connection\ConBdIdashboardProd1;

    // Connect
    $pdo = (new ConfigLeadFrDatabase())->connect();
    $pdo_prod1 = (new ConBdIdashboardProd1())->connect();

    // Optional search
    $search = $_GET['q'] ?? '';
    $sql = "SELECT * FROM tb_batch_management";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE batch_name LIKE :search OR batch_file LIKE :search OR table_name LIKE :search";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY id ASC LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function conn_auto_db($dataBese_name){
        $retries = 3;
        while ($retries > 0){
            try{
                $pdo = new PDO('mysql:host=localhost;port=3306;dbname='.$dataBese_name.';charset=utf8mb4', 'root', '123456fF$$',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->query('SET SQL_BIG_SELECTS=1');
                return $pdo;
            }catch (PDOException $e){
                $retries--;
                die('Erreur DBX : '.$e->getMessage());
                usleep(500); 
            }
        }
                
    }

    function vicidialCrm(){     
        $retries = 3;
        while ($retries > 0){
            try{
				$pdo = new PDO('mysql:host=192.168.1.131;port=3306;dbname=asterisk;charset=UTF8', 'admin_dev', 'wC5UKG664eAwc2d', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->query('SET SQL_BIG_SELECTS=1');
                return $pdo;
            }catch (PDOException $e){
                $retries--;
                usleep(500); // Wait 0.5s between retries.
            }
    	    if ($retries == 1 ) {die('Erreur 5.196.160.244 - db : asterisk '.$e->getMessage());   }
        }
    }
    
    function daysBetweenToday(?string $date): ?int {
        if (empty($date)) {  return null; }
        try {
            $givenDate = new DateTime($date);
            $givenDate->setTime(0, 0, 0);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $diff = $today->diff($givenDate)->days;

            // If the date is today, return 1
            if ($givenDate == $today) { return 1;}

            // If the date is in the future, return 0
            if ($givenDate > $today) { return 0;}

            // Past dates: return number of days ago + 1
            return $diff + 1;
        } catch (Exception $e) {
            return null;
        }
    }

    $reponseUserConnected = $pdo_prod1->query("select id,username,userlevel from tb_user where email='$token'");
		$rowUserConnected = $reponseUserConnected->fetch(PDO::FETCH_ASSOC);				                    
			$idUserConnecte = $rowUserConnected['id'];       
			$UserConnecte = $rowUserConnected['username'];
			$userLevel = $rowUserConnected['userlevel'];
    // Debugging output
    // echo "User ID: $idUserConnecte, Username: $UserConnecte, User Level: $userLevel <br>";
     echo "Username: $UserConnecte <br>";   
    $currentDateTime = date('Y-m-d H:i:s');   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> BM : LISTS BATCH </title>
    <link rel="icon" type="image/png" href="images/loglog.png">
    <link rel="stylesheet" href="css/monitor_batch_management.css"> 
</head>
<body>
    <h1> 📊 Suivi Batch Management </h1>

    <div class="search-box">
        <form method="get" action="">
            <input type="text" name="q" placeholder="Search batch, file, table..." value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
            <a href="monitor_batch_management.php" class="button-link">🔄 Refresh</a>          
        </form>
        <form method="post" action="">
            <input type="submit" value="Get Statistics" name="get_stats">
        </form>    
        <br>
    </div>

    <?php
        if (isset($_POST['get_stats']))
        {
            echo "<h2>📊 Launching Statistics Calculation...</h2>";

            // Run the heavy script in background
            $cmd = "php /var/www/html/phone_extractor/cron/getBatchStatistics.php > /dev/null 2>&1 &";
            exec($cmd);

            echo "<p>The statistics script has been launched on the server (background). You can check logs or results later.</p>";
        }

    ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>File</th>
            <!-- th>Split Date</th -->
            <th>DEP</th>
            <th>Database</th>
            <th>Table</th>
            <th>Prefix</th>
            <th>Count</th>
            <th>Count.BD</th>            
            <th>Insertion</th>
            <th>Modification</th>
            <th>USES</th>
            <th>L USE</th>
            <th>CRM ID </th>
            <th>CRM A </th>
            <th> NEW </th>
            <th> 404 </th>
            <th> PROMISE </th>
            <th> SDA </th>   
            <th> OK </th>
            <th>CRM LAST CALL </th>
            <th>Actions</th>
        </tr>

        <?php if (empty($rows)): ?>
            <tr><td colspan="22" style="text-align:center;">No data found</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php
                    $rowClass = 'dept-default';
                    switch (strtolower($row['department'])) {
                        case 'sales': $rowClass = 'dept-sales'; break;
                        case 'support': $rowClass = 'dept-support'; break;
                        case 'marketing': $rowClass = 'dept-marketing'; break;
                        case 'finance': $rowClass = 'dept-finance'; break;
                    }
                     
                    $table_id = htmlspecialchars($row['id']);
                    $database_name = htmlspecialchars($row['database_name']);
                    $batch_name = htmlspecialchars($row['batch_name']);                  
                    $selectNameTable = htmlspecialchars($row['table_name']);
                    $dateLastUse = htmlspecialchars($row['date_last_use']);
                    $lastIdCrm = htmlspecialchars($row['last_id_crm']);
                    $number_uses = $row['number_uses'];
                    $name_file = $row['batch_file'];
                    $connAuto = conn_auto_db($database_name);
                    $phone_number_found = $connAuto->query("SELECT COUNT(*) FROM $selectNameTable")->fetchColumn();
                    $phone_number_found_Without404 = $connAuto->query("SELECT COUNT(*) FROM $selectNameTable WHERE crm_status != '404'")->fetchColumn();
                    $day_work = daysBetweenToday($dateLastUse);

                    if(!empty($lastIdCrm)){
                        $connCrm =  vicidialCrm();
                            $list_phoneNotCall = $connCrm->query(" SELECT COUNT(*) FROM vicidial_list WHERE list_id='$lastIdCrm' AND status ='NEW' AND called_count = 0")->fetchColumn();
							$list_info = $connCrm->query(" SELECT list_lastcalldate,active FROM vicidial_lists where list_id='$lastIdCrm' ");
								$rowList = $list_info->fetch(PDO::FETCH_ASSOC);
								$list_lastcalldate = $rowList['list_lastcalldate'];
							    $list_active = $rowList['active'];                                                            
                        // close connection
                        $connCrm = null; 
                        
                        // GET STATUTS FROM tb_bash_history 
                        //number_added_crm | number_promises | number_sda | number_ok | number_answer | number_404 | last_call_date
                        $reponseBatchHistory = $pdo->query("select * from tb_bash_history where id_crm='$lastIdCrm' order by created_at desc limit 1");
                            $rowBatchHistory = $reponseBatchHistory->fetch(PDO::FETCH_ASSOC);				                    
                                $number_added_crm = $rowBatchHistory['number_added_crm'];                                       
                                $number_promises = $rowBatchHistory['number_promises'];
                                $number_sda = $rowBatchHistory['number_sda'];
                                $number_ok = $rowBatchHistory['number_ok'];
                                $number_answer = $rowBatchHistory['number_answer'];
                                $number_404 = $rowBatchHistory['number_404'];
                                $last_call_date = $rowBatchHistory['last_call_date'];
                    }else{
                        $list_phoneNotCall = 0;
                        $list_lastcalldate = '';
                        $list_active = '';
                        $number_added_crm = 0;
                        $number_promises = 0;   
                        $number_sda = 0;
                        $number_ok = 0;
                        $number_answer = 0;
                        $number_404 = 0;    
                    }
                    //$day_work = daysBetweenToday($dateLastUse);
                    if(empty($list_lastcalldate)){ 
                        $day_work = daysBetweenToday($list_lastcalldate); 
                    }else{ 
                        $day_work = 0; 
                    }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                    <td><?= htmlspecialchars($name_file) ?></td>
                   
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($database_name) ?></td>
                    <td><?= htmlspecialchars($selectNameTable) ?></td>
                    <td><?= htmlspecialchars($row['phone_prefix']) ?></td>
                    <td><?= htmlspecialchars($row['phone_nombre']) ?></td>
                    <td><?= htmlspecialchars($phone_number_found) ?></td>
                    <td><?= htmlspecialchars($row['date_insertion']) ?></td>
                    <td><?= htmlspecialchars($row['date_modification']) ?></td>
                    <td><?= htmlspecialchars($number_uses) ?></td>
                    <td><?= htmlspecialchars($row['date_last_use']) ?></td>
                    <td><?= htmlspecialchars($row['last_id_crm']) ?></td>
                    <td><?= htmlspecialchars($list_active) ?></td>
                    <td><?= htmlspecialchars($list_phoneNotCall) ?></td>
                    <td><?= htmlspecialchars($number_404) ?></td>
                    <td><?= htmlspecialchars($number_promises) ?></td>  
                    <td><?= htmlspecialchars($number_sda) ?></td>
                    <td><?= htmlspecialchars($number_ok) ?></td>
                    <td><?= htmlspecialchars($list_lastcalldate) ?></td>                    
                    <td>
                        
                        <?php
                            $day_work = daysBetweenToday($dateLastUse);
                            $rutrnProd = 40 - $day_work;
                            
                            if ($day_work == 0 OR $day_work > 40) {
                            echo '<a class="button-link"> ACTIONS '.$day_work.' </a>';
                            echo '<div class="custom-action">';
                                $phone_code = htmlspecialchars($row['phone_prefix']);
                                $number_use = htmlspecialchars($row['phone_nombre']);
                                $id_lot = htmlspecialchars($row['id']);
                                $a_width = 200;
                                $a_height = 18;

                                echo '<table style="border-collapse: collapse;">';
                                    echo '<tr>';
                                        echo '<td style="border: none;"><B>'; echo $batch_name . ' <B></td>';                                       
                                    echo '</tr>';
                                    
                                    echo '<tr>';
                                        echo '<td style="border: none;">';                        
                                            echo '<a class="button-link" style="background-color: #3B82F6; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_addLotToVicidial('
                                                . '\'' . $database_name . '\','
                                                . '\'' . $selectNameTable . '\','
                                                . '\'' . $phone_code . '\','
                                                . '\'' . $number_use . '\','
                                                . '\'' . $id_lot . '\','
                                                . '\'' . $number_uses . '\','
                                                . '\'' . $idUserConnecte . '\''
                                            . ');"> ADD TO CRM </a>'; 
                                        echo '</td>';
                                    echo '</tr>';

                                    echo '<tr>';    
                                        echo '<td style="border: none;">';
                                            echo '<a class="button-link" style="background-color: #1a1d1fff; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_downloadAsText('
                                                . '\'' . $database_name . '\','
                                                . '\'' . $selectNameTable . '\','
                                                . '\'' . $phone_code . '\','
                                                . '\'' . $name_file . '\','
                                                . '\'' . $id_lot . '\''
                                            . ');"> DOWNLOAD IN TEXT </a>'; 
                                        echo '</td>';
                                    echo '</tr>';
                                    
                                    echo '<tr>';    
                                        echo '<td style="border: none;">';
                                            echo '<a class="button-link" style="background-color: #1a1d1fff; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_downloadAsCsv('
                                                . '\'' . $database_name . '\','
                                                . '\'' . $selectNameTable . '\','
                                                . '\'' . $phone_code . '\','
                                                . '\'' . $name_file . '\','
                                                . '\'' . $id_lot . '\''
                                            . ');"> DOWNLOAD IN CSV </a>'; 
                                        echo '</td>';
                                    echo '</tr>';

                                    echo '<tr>';    
                                        echo '<td style="border: none;">';
                                            echo '<a class="button-link" style="background-color: #8B5CF6; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_historyBatch('
                                                . '\'' . $id_lot . '\','
                                                . '\'' . $idUserConnecte . '\''
                                            . ');"> HISTORY </a>';
                                        echo '</td>';    
                                    echo '</tr>';
                                    
                                    echo '<tr>';    
                                        echo '<td style="border: none;">';
                                            echo '<a class="button-link" style="background-color: #3B82F6; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_addLotToVicidialWithout404('
                                                . '\'' . $database_name . '\','
                                                . '\'' . $selectNameTable . '\','
                                                . '\'' . $phone_code . '\','
                                                . '\'' . $number_use . '\','
                                                . '\'' . $id_lot . '\','
                                                . '\'' . $number_uses . '\','
                                                . '\'' . $idUserConnecte . '\''
                                            . ');"> Add Without 404 ('.$phone_number_found_Without404.') </a>';  
                                        echo '</td>';    
                                    echo '</tr>';

                                echo '</table>';  
                            echo '</div>';
                            } else {
                                echo '<span style="color: #dbc70dff; font-weight: bold;"> PAUSE : '.$rutrnProd.' D</span>';

                                echo '<div class="custom-action">';
                                    $id_lot = htmlspecialchars($row['id']);
                                    $a_width = 200;
                                    $a_height = 18;

                                    echo '<table style="border-collapse: collapse;">';
                                        echo '<tr>';
                                            echo '<td style="border: none;"><B>'; echo $table_id .' - '. $batch_name . ' <B></td>';                                       
                                        echo '</tr>';
                                        
                                        echo '<tr>';    
                                            echo '<td style="border: none;">';
                                                echo '<a class="button-link" style="background-color: #8B5CF6; width: '.$a_width.'; height: '.$a_height.';" href="javascript:open_historyBatch('
                                                    . '\'' . $id_lot . '\','
                                                    . '\'' . $idUserConnecte . '\''
                                                    . ');"> HISTORY </a>';
                                            echo '</td>';    
                                        echo '</tr>';
                                    echo '</table>'; 

                                echo '</div>';
                            }                          
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <script src="js/monitor_batch_management.js"></script>
</body>
</html>
