<?php
namespace App\Service;

use App\Repository\VicidialRepository;
use App\Repository\BashOrdreRepository;
use App\Controller\BatchController;

class VicidialService
{
    private VicidialRepository $vicidialRepo;
    private BashOrdreRepository $bashOrdreRepo;

    public function __construct(
        VicidialRepository $vicidialRepo,
        BashOrdreRepository $bashOrdreRepo
    ) {
        $this->vicidialRepo  = $vicidialRepo;
        $this->bashOrdreRepo = $bashOrdreRepo;
    }

    public function buildListMeta(string $selectDataBase, string $nameTable, string $phoneCode, mixed $extra): array
    {
        $dateAddVicidial = date('Ymd');
        $varPostalCode   = $this->getDepNumber($selectDataBase);

        if (empty($extra)) {
            $varNumberUse = '01';
        } elseif ($extra < 10) {
            $varNumberUse = '0' . $extra;
        } else {
            $varNumberUse = $extra;
        }

        $varIdLot = $this->getBatchNumber($nameTable);

        return [
            'listId'          => $dateAddVicidial . $phoneCode . $varPostalCode . $varNumberUse . $varIdLot,
            'listName'        => $dateAddVicidial . '_' . $phoneCode . '_DEP' . $varPostalCode . '_' . $varNumberUse . '_' . $varIdLot,
            'listDescription' => $varPostalCode,
        ];
    }

    public function ensureListExists(
        string $listId,
        string $listName,
        string $listDescription,
        string $campaignId,
        string $dateDateAndTime
    ): bool {
        if (!$this->vicidialRepo->listExists($listId)) {
            $this->vicidialRepo->createList($listId, $listName, $campaignId, $listDescription, $dateDateAndTime);
            return true; // created
        }
        return false; // already existed
    }

    public function importBatches(
        array $batches,
        string $listId,
        string $dateDateAndTime,
        int $chunkSize = 1000
    ): array {
        $inserted = 0;
        $skipped  = 0;
        $errors   = 0;
        $total    = count($batches);
        $idx      = 0;

        while ($idx < $total) {
            $this->vicidialRepo->beginTransaction();
            try {
                $upper = min($idx + $chunkSize, $total);
                for ($i = $idx; $i < $upper; $i++) {
                    $batch        = $batches[$i];
                    $phoneNumber  = (string)$batch->getTel1();

                    if ($phoneNumber === '') { $skipped++; continue; }
                    if ($this->vicidialRepo->phoneExists($listId, $phoneNumber)) { $skipped++; continue; }

                    try {
                        $this->vicidialRepo->insertPhone(
                            $dateDateAndTime,
                            $listId,
                            $phoneNumber,
                            trim($batch->getNom() . ' ' . $batch->getPrenom()),
                            (string)$batch->getAdresse(),
                            (string)$batch->getCodePostal(),
                            (string)$batch->getVille()
                        );
                        $inserted++;
                    } catch (\Throwable $e) {
                        $errors++;
                    }
                }
                $this->vicidialRepo->commit();
            } catch (\Throwable $e) {
                $this->vicidialRepo->rollBack();
                $errors += ($upper - $idx);
            }
            $idx = $upper;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function finalizeOrdre(
        int $idOrdre,
        int $idLot,
        int $idUserConnecte,
        string $listId,
        string $dateDateAndTime,
        int $extra
    ): bool {
        $this->bashOrdreRepo->updateBatchManagement($idLot, $extra + 1, $dateDateAndTime);
        $this->bashOrdreRepo->insertHistory($idLot, $idUserConnecte, $listId);
        $this->bashOrdreRepo->updateStatus($idOrdre, 1);
        return $this->bashOrdreRepo->updateLastIdCrm($idLot, $listId);
    }

    private function getDepNumber(string $str): string
    {
        if (preg_match('/dbx_dep(\d+)/i', $str, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }
        return '';
    }

    private function getBatchNumber(string $str): string
    {
        if (preg_match('/_(\d+)$/', $str, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }
        return '00';
    }
}