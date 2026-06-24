<?php
namespace App\Service;

use App\Repository\BatchStatisticsRepository;

class BatchStatisticsService
{
    private BatchStatisticsRepository $repo;

    public function __construct(BatchStatisticsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAllBatchHistory(): array
    {
        return $this->repo->getAllBatchHistory();
    }

    /**
     * Process one batch row.
     * Returns array with stats + updated flag, or null if already up-to-date.
     */
    public function processBatch(array $batch): ?array
    {
        $crmId             = $batch['id_crm'];
        $tableLastCallDate = $batch['last_call_date'];

        $lastCallDate = $this->repo->getListLastCallDate($crmId);

        if ($lastCallDate === $tableLastCallDate) {
            return null; // already up-to-date
        }

        // Vicidial stats
        $raw             = $this->repo->getVicidialStats($crmId);
        $numberAddedCrm  = (int)$raw['number_added_crm'];
        $numberPromises  = (int)$raw['number_promises'];
        $number404       = (int)$raw['number_404'];
        $numberAnswer    = $numberAddedCrm - $number404;

        // Reception stats
        $phoneNumbers    = $this->repo->getSalePhoneNumbers($crmId);
        $reception       = $this->repo->getReceptionStats($phoneNumbers);
        $totalOkSda      = (int)$reception['total_oksda'];
        $totalOkPrive    = (int)$reception['total_okprive'];

        // Persist
        $updated = $this->repo->updateBatchHistory(
            (int)$batch['id'],
            $numberAddedCrm,
            $numberPromises,
            $number404,
            $numberAnswer,
            $totalOkSda,
            $totalOkPrive,
            $lastCallDate
        );

        return [
            'tableId'        => $batch['id'],
            'crmId'          => $crmId,
            'lastCallDate'   => $lastCallDate,
            'numberAddedCrm' => $numberAddedCrm,
            'numberPromises' => $numberPromises,
            'number404'      => $number404,
            'numberAnswer'   => $numberAnswer,
            'totalOkSda'     => $totalOkSda,
            'totalOkPrive'   => $totalOkPrive,
            'updated'        => $updated,
        ];
    }
}