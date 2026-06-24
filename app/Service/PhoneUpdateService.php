<?php
namespace App\Service;

use App\Repository\PhoneUpdateRepository;
use PDO;

class PhoneUpdateService
{
    private PhoneUpdateRepository $repo;

    public function __construct(PhoneUpdateRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getPendingBatches(string $threeDaysAgo): array
    {
        return $this->repo->getPendingBatchHistory($threeDaysAgo);
    }

    public function getBatchMeta(int $batchId): ?array
    {
        return $this->repo->getBatchManagement($batchId);
    }

    public function getPhonesFromTable(PDO $pdoAuto, string $tableName): array
    {
        return $this->repo->getAllPhonesFromTable($pdoAuto, $tableName);
    }

    /**
     * Get Vicidial info for a phone number.
     * Normalizes NEW + called_count=1 → 404.
     */
    public function getVicidialInfo(string $tel1): array
    {
        $row = $this->repo->getVicidialPhoneInfo($tel1);
        if (!$row) {
            return ['found' => false, 'status' => null, 'last_local_call_time' => null, 'called_count' => null];
        }

        $status      = $row['status']              ?? null;
        $calledCount = $row['called_count']         ?? null;
        $lastCall    = $row['last_local_call_time'] ?? null;

        if ($status === 'NEW' && (int)$calledCount === 1) {
            $status = '404';
        }

        return ['found' => true, 'status' => $status, 'last_local_call_time' => $lastCall, 'called_count' => $calledCount];
    }

    /**
     * Decide which column slot to update (crm_status_1/2/3)
     * and execute the update.
     */
    public function updatePhoneSlot(
        PDO    $pdoAuto,
        string $tableName,
        array  $phone,
        ?string $status,
        ?string $lastLocalCallTime
    ): array {
        $tel1         = $phone['tel1'];
        $updateNumber = (int)$phone['update_number'];

        // Find first empty slot
        $slots = [
            ['status' => 'crm_status_1', 'date' => 'crm_date_call_1', 'value' => $phone['crm_status_1']],
            ['status' => 'crm_status_2', 'date' => 'crm_date_call_2', 'value' => $phone['crm_status_2']],
            ['status' => 'crm_status_3', 'date' => 'crm_date_call_3', 'value' => $phone['crm_status_3']],
        ];

        foreach ($slots as $slot) {
            if (empty($slot['value'])) {
                return $this->repo->updatePhoneCallInfo(
                    $pdoAuto, $tableName, $tel1,
                    $status, $lastLocalCallTime,
                    $updateNumber,
                    $slot['status'], $slot['date']
                );
            }
        }

        // All slots filled — overwrite the one with the oldest call date
        $dates = [
            $phone['crm_date_call_1'],
            $phone['crm_date_call_2'],
            $phone['crm_date_call_3'],
        ];
        $oldestIndex = $this->getLowestDateIndex($dates);
        $slot        = $slots[$oldestIndex];

        return $this->repo->updatePhoneCallInfo(
            $pdoAuto, $tableName, $tel1,
            $status, $lastLocalCallTime,
            $updateNumber,
            $slot['status'], $slot['date']
        );
    }

    public function markAsUpdated(int $tableId): bool
    {
        return $this->repo->markBatchHistoryAsUpdated($tableId);
    }

    private function getLowestDateIndex(array $dates): int
    {
        $filtered = array_filter($dates, fn($d) => !empty($d));
        if (empty($filtered)) {
            return 0;
        }
        asort($filtered);
        return (int)array_key_first($filtered);
    }
}