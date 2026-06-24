<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/var/www/html/phone_extractor/config/autoload.php';

use App\Repository\BatchRepository;
use App\Controller\BatchController;

// 1. Get params
$selectDataBase = $_GET['var1'] ?? '';    
$nameTable      = $_GET['var2'] ?? '';
$phoneCode      = $_GET['var3'] ?? '';
$numberUse      = $_GET['var4'] ?? '';
$idLot          = $_GET['var5'] ?? '';
$extra          = $_GET['var6'] ?? '';
/*
echo "- Data Base: $selectDataBase<br>";
echo "- Table: $nameTable<br>";
echo "- Phone Code: $phoneCode<br>";
echo "- Number Use: $numberUse<br>";
echo "- ID Lot: $idLot<br>";
echo "- Extra: $extra<br>";
*/

// Build filename: dbx_dep01_tb_batch_1.txt
$filename = $selectDataBase . "_" . $nameTable . ".txt";

// Force download headers
header("Content-Type: text/plain; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// 2. Dynamically choose DB connection class
try {
    $namespace = 'App\\Connection\\';

    if (preg_match('/dbx_dep(\d+)/i', $selectDataBase, $m)) {
        $depNumber = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $className = $namespace . "Dep{$depNumber}Database";

        if (!class_exists($className)) {
            throw new Exception("Database connection class not found: $className");
        }

        $db = new $className();
        $pdo = $db->connect();
    } else {
        throw new Exception("Invalid database parameter: $selectDataBase");
    }

} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// 3. Load Repository + Controller
$repo = new BatchRepository($pdo, $selectDataBase, $nameTable);
$controller = new BatchController($repo);

// 4. Get data
try {
    $batches = $controller->getAll();

    // First line: header
    echo "Tel1  CodePostal  Ville  Adresse  Genre  Nom  Prenom  Tel2  Tel3  Mobile  Fax  Habitat  AgeMoyen  Ethnie  Tel1_Prospection  Tel2_Prospection  Tel3_Prospection  Mobile_Prospection  Fax_Prospection\n";

    // Data rows
    foreach ($batches as $batch) {
        echo $batch->getTel1() . "  "
           . $batch->getCodePostal() . "  "
           . $batch->getVille() . "  "
           . $batch->getAdresse() . "  "
           . $batch->getGenre() . "  "
           . $batch->getNom() . "  "
           . $batch->getPrenom() . "  "
           . $batch->getTel2() . "  "
           . $batch->getTel3() . "  "
           . $batch->getMobile() . "  "
           . $batch->getFax() . ""
           . $batch->getHabitat() . "  "
           . $batch->getAgeMoyen() . ""
           . $batch->getEthnie() . ""
           . $batch->getTel1Prospection() . "  "
           . $batch->getTel2Prospection() . "  "
           . $batch->getTel3Prospection() . "  "
           . $batch->getMobileProspection() . "  "
           . $batch->getFaxProspection()
           . "\n";
    }

} catch (Exception $e) {
    echo "Error fetching batches: " . $e->getMessage();
}

?>
