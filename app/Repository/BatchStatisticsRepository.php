<?php
namespace App\Repository;

use PDO;
use PDOException;

class BatchStatisticsRepository
{
    private PDO $pdoVicidial;
    private PDO $pdoConfigLead;
    private PDO $pdoReception;

    public function __construct(PDO $pdoVicidial, PDO $pdoConfigLead, PDO $pdoReception)
    {
        $this->pdoVicidial   = $pdoVicidial;
        $this->pdoConfigLead = $pdoConfigLead;
        $this->pdoReception  = $pdoReception;
    }

    public function getAllBatchHistory(): array
    {
        $stmt = $this->pdoConfigLead->prepare(
            "SELECT * FROM tb_bash_history ORDER BY updated_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getListLastCallDate(string $crmId): ?string
    {
        $stmt = $this->pdoVicidial->prepare(
            "SELECT list_lastcalldate FROM vicidial_lists WHERE list_id = :list_id"
        );
        $stmt->execute([':list_id' => $crmId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    public function getVicidialStats(string $crmId): array
    {
        $stmt = $this->pdoVicidial->prepare(
            "SELECT 
                COUNT(*) AS number_added_crm,
                SUM(CASE WHEN status = 'SALE' THEN 1 ELSE 0 END) AS number_promises,
                SUM(CASE WHEN status = 'NEW' AND called_count = 1 THEN 1 ELSE 0 END) AS number_404
             FROM vicidial_list
             WHERE list_id = :list_id"
        );
        $stmt->execute([':list_id' => $crmId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSalePhoneNumbers(string $crmId): array
    {
        $stmt = $this->pdoVicidial->prepare(
            "SELECT phone_number FROM vicidial_list 
             WHERE list_id = :list_id AND status = 'SALE'"
        );
        $stmt->execute([':list_id' => $crmId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getReceptionStats(array $phoneNumbers): array
    {
        if (empty($phoneNumbers)) {
            return ['total_oksda' => 0, 'total_okprive' => 0];
        }

        // Build phone list with 33 prefix
        $phonesStr = '33' . implode(',33', $phoneNumbers);

        $stmt = $this->pdoReception->prepare(
            "SELECT 
                SUM(CASE WHEN oksda = 1 THEN 1 ELSE 0 END) AS total_oksda,
                SUM(CASE WHEN okprive = 1 THEN 1 ELSE 0 END) AS total_okprive
             FROM prospection
             WHERE callerid IN ($phonesStr)"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateBatchHistory(
        int    $tableId,
        int    $numberAddedCrm,
        int    $numberPromises,
        int    $number404,
        int    $numberAnswer,
        int    $numberSda,
        int    $numberOk,
        string $lastCallDate
    ): bool {
        $stmt = $this->pdoConfigLead->prepare(
            "UPDATE tb_bash_history
             SET number_added_CRM = :number_added_crm,
                 number_promises  = :number_promises,
                 number_404       = :number_404,
                 number_answer    = :number_answer,
                 number_sda       = :number_sda,
                 number_ok        = :number_ok,
                 last_call_date   = :last_call_date
             WHERE id = :id"
        );
        $stmt->execute([
            ':number_added_crm' => $numberAddedCrm,
            ':number_promises'  => $numberPromises,
            ':number_404'       => $number404,
            ':number_answer'    => $numberAnswer,
            ':number_sda'       => $numberSda,
            ':number_ok'        => $numberOk,
            ':last_call_date'   => $lastCallDate,
            ':id'               => $tableId,
        ]);
        return $stmt->rowCount() > 0;
    }
}