<?php
namespace App\Service;

use App\Repository\BatchTableRepository;
use PDO;

class BatchTableService
{
    private BatchTableRepository $repo;

    public function __construct(BatchTableRepository $repo)
    {
        $this->repo = $repo;
    }

    public function processFile(
        string $currentFile,
        string $databaseName,
        PDO $pdoTarget
    ): array {
        $batchName      = $this->extractDepNumber($currentFile);
        $batchSplitDate = $this->extractFirstPart($currentFile);
        $department     = $this->extractDepPart($currentFile);
        $tableName      = 'tb_batch_' . $this->extractLastNumber($currentFile);
        $phonePrefix    = $this->extractMiddleNumberRegex($currentFile);

        $this->repo->insertBatchManagement(
            $batchName,
            $batchSplitDate,
            $department,
            $databaseName,
            $tableName,
            $phonePrefix
        );

        $this->repo->createBatchTable($pdoTarget, $tableName);

        return [
            'batch_name'       => $batchName,
            'batch_split_date' => $batchSplitDate,
            'department'       => $department,
            'database_name'    => $databaseName,
            'table_name'       => $tableName,
            'phone_prefix'     => $phonePrefix,
        ];
    }

    public function getDepCode(string $filename): string
    {
        return ucfirst($this->extractDepPart($filename));
    }

    // ---------- Filename helpers ----------

    private function extractDepNumber(string $filename): string
    {
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('-', $nameWithoutExt);
        return str_replace('-', '', $parts[1] . $parts[2] . $parts[3]);
    }

    private function extractFirstPart(string $filename): string
    {
        return explode('-', $filename)[0];
    }

    private function extractDepPart(string $filename): string
    {
        return explode('-', $filename)[1];
    }

    private function extractLastNumber(string $filename): int
    {
        $parts = explode('-', $filename);
        return (int)str_replace('.txt', '', end($parts));
    }

    private function extractMiddleNumberRegex(string $filename): ?string
    {
        return preg_match('/-(\d{3})-/', $filename, $matches) ? $matches[1] : null;
    }
}