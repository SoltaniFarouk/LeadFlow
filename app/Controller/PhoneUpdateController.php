<?php
namespace App\Controller;

use App\Service\PhoneUpdateService;
use PDO;

class PhoneUpdateController
{
    private PhoneUpdateService $service;
    private \Closure $connectionFactory;

    /**
     * @param \Closure $connectionFactory  fn(string $dbName): ?PDO
     */
    public function __construct(PhoneUpdateService $service, \Closure $connectionFactory)
    {
        $this->service           = $service;
        $this->connectionFactory = $connectionFactory;
    }

    public function run(string $threeDaysAgo): int
    {
        $batches      = $this->service->getPendingBatches($threeDaysAgo);
        $totalUpdated = 0;

        if (empty($batches)) {
            echo "No batch found in tb_bash_history. \n";
            return 0;
        }

        foreach ($batches as $batch) {
            $tableId            = $batch['id'];
            $batchId            = $batch['id_bash'];
            $crmId              = $batch['id_crm'];
            $tableLastCallDate  = $batch['last_call_date'];

            echo " TABLE.ID : $tableId \n";            logMessage(" Processing TABLE.ID : $tableId");
            echo " BATCH.ID : $batchId \n";            logMessage(" Processing BATCH.ID : $batchId");
            echo " CRM.ID : $crmId \n";                logMessage(" Processing CRM.ID : $crmId");
            echo " TABLE LAST CALL DATE : $tableLastCallDate \n";

            // Get batch meta
            $batchMeta = $this->service->getBatchMeta((int)$batchId);
            if (!$batchMeta) {
                echo "❌ Batch meta not found for batchId $batchId\n";
                logMessage(" Batch meta not found for batchId $batchId");
                continue;
            }

            $department   = $batchMeta['department'];
            $tableName    = $batchMeta['table_name'];
            $databaseName = $batchMeta['database_name'];

            echo " DEPARTMENT : $department \n";       logMessage(" Processing DEPARTMENT : $department");
            echo " TABLE NAME : $tableName \n";        logMessage(" Processing TABLE NAME : $tableName");
            echo " DATABASE : $databaseName \n";       logMessage(" Processing DATABASE : $databaseName");

            // Connect to dep database
            $pdoAuto = ($this->connectionFactory)($databaseName);
            if ($pdoAuto === null) {
                echo "❌ Failed to connect to database: $databaseName\n";
                logMessage(" Failed to connect to database: $databaseName");
                continue;
            }

            // Get phones
            $phones = $this->service->getPhonesFromTable($pdoAuto, $tableName);
            if (empty($phones)) {
                echo "No phones found in table: $tableName\n";
                logMessage(" No phones found in table: $tableName");
                continue;
            }

            echo " Nombre de phones a traiter : " . count($phones) . "\n";
            logMessage(" Nombre de phones a traiter : " . count($phones));

            // Process each phone
            foreach ($phones as $phone) {
                $tel1 = $phone['tel1'];
                echo " tel1 : $tel1 \n";
                echo " crm_status_1 : {$phone['crm_status_1']} \n";
                echo " crm_date_call_1 : {$phone['crm_date_call_1']} \n";
                echo " crm_status_2 : {$phone['crm_status_2']} \n";
                echo " crm_date_call_2 : {$phone['crm_date_call_2']} \n";
                echo " crm_status_3 : {$phone['crm_status_3']} \n";
                echo " crm_date_call_3 : {$phone['crm_date_call_3']} \n";
                echo " update_number : {$phone['update_number']} \n";
                echo " ------------------------- \n";

                // Get Vicidial info
                $vicidialInfo = $this->service->getVicidialInfo($tel1);
                if (!$vicidialInfo['found']) {
                    echo " No record found in vicidial_list for phone $tel1\n";
                    logMessage(" No record found in vicidial_list for phone $tel1");
                } else {
                    echo " status : {$vicidialInfo['status']} \n";                              logMessage(" status : {$vicidialInfo['status']}");
                    echo " called_count : {$vicidialInfo['called_count']} \n";                  logMessage(" called_count : {$vicidialInfo['called_count']}");
                    echo " last_local_call_time : {$vicidialInfo['last_local_call_time']} \n";  logMessage(" last_local_call_time : {$vicidialInfo['last_local_call_time']}");
                }

                // Update slot
                $result = $this->service->updatePhoneSlot(
                    $pdoAuto,
                    $tableName,
                    $phone,
                    $vicidialInfo['status'],
                    $vicidialInfo['last_local_call_time']
                );
                echo $result['message'] . "\n";
                logMessage($result['message']);

                echo " ========================== \n";
            }

            // Mark batch as processed
            if ($this->service->markAsUpdated((int)$tableId)) {
                echo "✅ tb_bash_history ID $tableId marked as updated.\n";
                logMessage(" tb_bash_history ID $tableId marked as updated.");
            } else {
                echo "⚠️ No rows updated in tb_bash_history for ID $tableId.\n";
                logMessage(" No rows updated in tb_bash_history for ID $tableId.");
            }

            $totalUpdated++;
            echo " ******************************** \n";
            logMessage(" ******************************** ");
            flush();
        }

        return $totalUpdated;
    }
}