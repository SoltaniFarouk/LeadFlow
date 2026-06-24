<?php
namespace App\Controller;

use App\Repository\BatchManagementRepository;
use App\Model\BatchManagement;
use App\Connection\ConfigLeadFrDatabase;

class BatchManagementController {
    private BatchManagementRepository $repository;

    public function __construct() {
        $pdo = (new ConfigLeadFrDatabase())->connect();
        $this->repository = new BatchManagementRepository($pdo);
    }

    // Create a new batch
    public function createBatch(array $data): bool {
        $batch = new BatchManagement();
        $batch->setBatchName($data['batch_name'] ?? null);
        $batch->setBatchFile($data['batch_file'] ?? null);
        $batch->setBatchSplitDate($data['batch_split_date'] ?? null);
        $batch->setDepartment($data['department'] ?? null);
        $batch->setDatabaseName($data['database_name'] ?? null);
        $batch->setTableName($data['table_name'] ?? null);
        $batch->setPhonePrefix($data['phone_prefix'] ?? null);
        $batch->setPhoneNombre($data['phone_nombre'] ?? 0);
        $batch->setArchiver($data['archiver'] ?? false);

        return $this->repository->insert($batch);
    }

    // Get batch by ID
    public function getBatch(int $id): ?BatchManagement {
        return $this->repository->findById($id);
    }

    // Update batch
    public function updateBatch(int $id, array $data): bool {
        $batch = $this->repository->findById($id);
        if (!$batch) return false;

        $batch->setBatchName($data['batch_name'] ?? $batch->getBatchName());
        $batch->setBatchFile($data['batch_file'] ?? $batch->getBatchFile());
        $batch->setBatchSplitDate($data['batch_split_date'] ?? $batch->getBatchSplitDate());
        $batch->setDepartment($data['department'] ?? $batch->getDepartment());
        $batch->setDatabaseName($data['database_name'] ?? $batch->getDatabaseName());
        $batch->setTableName($data['table_name'] ?? $batch->getTableName());
        $batch->setPhonePrefix($data['phone_prefix'] ?? $batch->getPhonePrefix());
        $batch->setPhoneNombre($data['phone_nombre'] ?? $batch->getPhoneNombre());
        $batch->setArchiver($data['archiver'] ?? $batch->getArchiver());

        return $this->repository->update($batch);
    }

    // Delete batch
    public function deleteBatch(int $id): bool {
        return $this->repository->delete($id);
    }

    // List all batches
    public function listBatches(): array {
        return $this->repository->findAll();
    }
}
?>
