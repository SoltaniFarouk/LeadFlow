<?php
namespace App\Repository;

use PDO;
use PDOException;

class PhoneUpdateRepository
{
    private PDO $pdoConfigLead;
    private PDO $pdoVicidial;

    public function __construct(PDO $pdoConfigLead, PDO $pdoVicidial)
    {
        $this->pdoConfigLead = $pdoConfigLead;
        $this->pdoVicidial   = $pdoVicidial;
    }

    public function getPendingBatchHistory(string $threeDaysAgo): array
    {
        $stmt = $this->pdoConfigLead->prepare(
            "SELECT * FROM tb_bash_history 
             WHERE update_phone = '0' 
               AND last_call_date < :threeDaysAgo 
             ORDER BY id"
        );
        $stmt->execute([':threeDaysAgo' => $threeDaysAgo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBatchManagement(int $batchId): ?array
    {
        $stmt = $this->pdoConfigLead->prepare(
            "SELECT * FROM tb_batch_management WHERE id = :id"
        );
        $stmt->execute([':id' => $batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAllPhonesFromTable(PDO $pdoAuto, string $tableName): array
    {
        $stmt = $pdoAuto->prepare("SELECT * FROM `$tableName`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVicidialPhoneInfo(string $tel1): ?array
    {
        $stmt = $this->pdoVicidial->prepare(
            "SELECT phone_number, status, last_local_call_time, called_count
             FROM vicidial_list
             WHERE phone_number = :tel1
             LIMIT 1"
        );
        $stmt->execute([':tel1' => $tel1]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updatePhoneCallInfo(
        PDO    $pdoAuto,
        string $tableName,
        string $tel1,
        ?string $status,
        ?string $lastLocalCallTime,
        int    $updateNumber,
        string $columnStatus,
        string $columnDate
    ): array {
        try {
            $updateNumber++;
            $stmt = $pdoAuto->prepare(
                "UPDATE `$tableName`
                 SET `$columnStatus`  = :status,
                     `$columnDate`    = :last_local_call_time,
                     crm_status       = :status,
                     crm_date_call    = :last_local_call_time,
                     update_number    = :update_number
                 WHERE tel1 = :tel1"
            );
            $stmt->bindParam(':status',               $status);
            $stmt->bindParam(':last_local_call_time', $lastLocalCallTime);
            $stmt->bindParam(':update_number',        $updateNumber, PDO::PARAM_INT);
            $stmt->bindParam(':tel1',                 $tel1);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => true,  'message' => "✅ tel1=$tel1 updated successfully", 'update_number' => $updateNumber];
            }
            return ['success' => false, 'message' => "⚠️ No rows updated for tel1=$tel1",       'update_number' => $updateNumber];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "❌ Error updating tel1=$tel1: " . $e->getMessage(), 'update_number' => $updateNumber];
        }
    }

    public function markBatchHistoryAsUpdated(int $tableId): bool
    {
        $stmt = $this->pdoConfigLead->prepare(
            "UPDATE tb_bash_history SET update_phone = '1' WHERE id = :tableId"
        );
        $stmt->bindParam(':tableId', $tableId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}