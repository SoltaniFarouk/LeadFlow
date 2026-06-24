    <style>
    td {
        position: relative; 
    }

    .custom-tooltip {
  visibility: hidden;
  opacity: 0;
  background-color: #a93226;
  color: #fff;
  text-align: left;
  padding: 10px;
  border-radius: 6px;
  position: absolute;
  z-index: 1;
  top: 100%;
  left: 0;                      /* changed */
  transform: translateX(-100%); /* changed */
  transition: opacity 0.3s;
  font-size: 13px;
  white-space: nowrap;
}

.custom-action {
  visibility: hidden;
  opacity: 0;
  background-color: #2c3e50;
  color: #fff;
  text-align: left;
  padding: 10px;
  border-radius: 6px;
  position: absolute;
  z-index: 1;
  top: 50%;                         /* center vertically */
  left: 0;                          /* stick to left */
  transform: translate(-100%, -50%);/* shift left and center vertically */
  transition: opacity 0.3s;
  font-size: 13px;
  white-space: nowrap;
  width: 400px;
  height: 200px;
}
	
    td:hover .custom-tooltip,
    td:hover .custom-action {
      visibility: visible;
      opacity: 1;
    }

    .custom-action input[type='text'] {
      margin: 3px 0;
    }

    .custom-action input[type='submit'] {
      margin-top: 5px;
      cursor: pointer;
    }
    </style>
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

    $reponseUserConnected = $pdo_prod1->query("select id,username,userlevel from tb_user where email='$token'");
		$rowUserConnected = $reponseUserConnected->fetch(PDO::FETCH_ASSOC);				                    
			$idUserConnecte = $rowUserConnected['id'];       
			$UserConnecte = $rowUserConnected['username'];
			$userLevel = $rowUserConnected['userlevel'];
    // Debugging output
    echo "User ID: $idUserConnecte, Username: $UserConnecte, User Level: $userLevel <br>";       
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suivi Batch Management </title>
    <link rel="icon"  type="image/png" href="images/number1.png">  <!-- loglog.png  --->
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
        <br>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>File</th>
            <th>Split Date</th>
            <th>Department</th>
            <th>Database</th>
            <th>Table</th>
            <th>Prefix</th>
            <th>Count</th>
            <th>Count.BD</th>            
            <th>Date Insertion</th>
            <th>Date Modification</th>
            <th>USES</th>
            <th>LAST USE</th>
            <th>Actions</th>
        </tr>

        <?php if (empty($rows)): ?>
            <tr><td colspan="12">No data found</td></tr>
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

                    $database_name = htmlspecialchars($row['database_name']);
                    $selectNameTable = htmlspecialchars($row['table_name']);
                    $number_uses = $row['number_uses'];
                    $name_file = $row['batch_file'];
                    $connAuto = conn_auto_db($database_name);
                        $phone_number_found = $connAuto->query("SELECT COUNT(*) FROM $selectNameTable")->fetchColumn();
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                    <td><?= htmlspecialchars($name_file) ?></td>
                    <td><?= htmlspecialchars($row['batch_split_date']) ?></td>
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
                    <td>
                        <a class="button-link"> ACTIONS </a>
                        <?php
                            echo '<div class="custom-action">';
                                $phone_code = htmlspecialchars($row['phone_prefix']);
                                $number_use = htmlspecialchars($row['phone_nombre']);
                                $id_lot = htmlspecialchars($row['id']);
                                $a_width = 200;
                                $a_height = 18;

                                echo '<table style="border-collapse: collapse;">';
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
                                                . '\'' . $id_lot . '\''
                                            . ');"> HISTORY </a>';
                                        echo '</td>';    
                                    echo '</tr>';
                                echo '</table>';  
                            echo '</div>';

                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <script src="js/monitor_batch_management.js"></script>
</body>
</html>
