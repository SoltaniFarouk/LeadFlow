<?php
namespace App\Controller;

use App\Service\VicidialService;
use App\Controller\BatchController;

class VicidialController
{
    private VicidialService $service;
    private BatchController $batchController;
    private string $campaignId;

    public function __construct(
        VicidialService $service,
        BatchController $batchController,
        string $campaignId = '20250101'
    ) {
        $this->service         = $service;
        $this->batchController = $batchController;
        $this->campaignId      = $campaignId;
    }

    public function handle(array $ordre, string $dateDateAndTime, string &$str_msg): array
    {
        $selectDataBase = $ordre['selectDataBase'];
        $nameTable      = $ordre['nameTable'];
        $phoneCode      = $ordre['phone_code'];
        $extra          = $ordre['extra'];
        $type_add       = $ordre['type_add'];

        // Load batches
        if ($type_add == 1) {
            $str_msg .= "📦 <B> Add Lot To Vicidial Without 404 </B>📦 \n";
            $str_msg .= " 🛢️ <B>DataBase : </B>$selectDataBase\n";
            $str_msg .= " 🧮 <B>Table : </B>$nameTable\n";
            $batches = $this->batchController->getPhoneWithout404();
        } else {
            $str_msg .= "📦 <B> Add Lot To Vicidial ALL Numbers </B>📦 \n";
            $str_msg .= " 🛢️ <B>DataBase : </B>$selectDataBase\n";
            $str_msg .= " 🧮 <B>Table : </B>$nameTable\n";
            $batches = $this->batchController->getAll();
        }

        // Build list meta
        $meta = $this->service->buildListMeta($selectDataBase, $nameTable, $phoneCode, $extra);

        // Ensure list exists in Vicidial
        $created = $this->service->ensureListExists(
            $meta['listId'],
            $meta['listName'],
            $meta['listDescription'],
            $this->campaignId,
            $dateDateAndTime
        );

        // Import
        $stats = $this->service->importBatches($batches, $meta['listId'], $dateDateAndTime);

        return [
            'batches'  => $batches,
            'meta'     => $meta,
            'created'  => $created,
            'stats'    => $stats,
        ];
    }
}