<?php
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

function connBackupVicidialIse(): ?PDO {
    return createPdoConnection(
        "192.168.1.131",
        "asterisk",
        "admin_dev",
        "wC5UKG664eAwc2d"
    );
}

function connConfigLeadFr(): ?PDO {
    return createPdoConnection(
        "localhost",
        "dbx_config_lead_fr",
        "root",
        "123456fF$$"
    );
}
    function conBdReceptionIse(){          
        $retries = 3;
        while ($retries > 0){
            try{
                $pdo = new PDO('mysql:host=192.168.1.130;port=3306;dbname=db_call_center_reception_cloud;charset=utf8mb4', 'root', '123456fF$$', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);             
                $pdo->query('SET SQL_BIG_SELECTS=1');
                return $pdo;
            }catch (PDOException $e){
                $retries--;
                usleep(500); 
            }        
			if ($retries == 1 ) { die('Erreur 132 - db : reception_callcenter_ise '.$e->getMessage());   }
        }
    }
    
    function logMessage(string $message): void {
        $logFile = '/var/www/html/phone_extractor/logs/getBatchStatistics.log';
        $dateTime = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$dateTime] $message\n", FILE_APPEND);
    }

    function sendHtmlMessageToTelegramGroup(string $message, string $chat_id, string $bot_token): string { 
            $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

            // DO NOT escape HTML if you're using parse_mode = HTML
            $data = [
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            $ch = curl_init();
            if (!$ch) { return "Error: Failed to initialize cURL."; }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
        
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                return "Error: cURL execution failed - $error";
            }
        
            curl_close($ch);
        
            if ($http_code !== 200) {
                return "Error: Telegram API returned HTTP $http_code. Response: $response";
            }
        
            return 1;
        }