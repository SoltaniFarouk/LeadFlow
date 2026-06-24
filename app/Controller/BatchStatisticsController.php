<?php
namespace App\Controller;

use App\Service\BatchStatisticsService;

class BatchStatisticsController
{
    private BatchStatisticsService $service;

    public function __construct(BatchStatisticsService $service)
    {
        $this->service = $service;
    }

    public function run(): int
    {
        $batches      = $this->service->getAllBatchHistory();
        $totalUpdated = 0;

        if (empty($batches)) {
            echo "No batch found in tb_bash_history.\n";
            return 0;
        }

        foreach ($batches as $batch) {
            $tableId = $batch['id'];
            $batchId = $batch['id_bash'];
            $crmId   = $batch['id_crm'];

            echo " TABLE.ID : $tableId \n";
            echo " BATCH.ID : $batchId \n";
            echo " CRM.ID : $crmId \n";
            echo " TABLE LAST CALL DATE : {$batch['last_call_date']} \n";

            $result = $this->service->processBatch($batch);

            if ($result === null) {
                echo " last_call_date is already up-to-date for ID $tableId. \n";
                logMessage(" last_call_date is already up-to-date for ID $tableId.");
            } else {
                echo " Last Call Date : {$result['lastCallDate']} \n";
                echo " Number Added CRM : {$result['numberAddedCrm']} \n";
                echo " Number Promises : {$result['numberPromises']} \n";
                echo " Number 404 : {$result['number404']} \n";
                echo " Number Answer : {$result['numberAnswer']} \n";
                echo " Total OK SDA : {$result['totalOkSda']} \n";
                echo " Total OK Prive : {$result['totalOkPrive']} \n";

                if ($result['updated']) {
                    echo " ✅ last_call_date updated successfully for ID $tableId. \n";
                    logMessage(" last_call_date updated successfully for ID $tableId.");
                } else {
                    echo " ❌ Failed to update last_call_date for ID $tableId. \n";
                    logMessage(" Failed to update last_call_date for ID $tableId.");
                }

                $totalUpdated++;
            }

            echo " ******************************** \n";
            logMessage(" ******************************** ");
            flush();
        }

        return $totalUpdated;
    }
}