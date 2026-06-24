<?php
namespace App\Repository;

use App\Model\BatchManagement;
use PDO;

class BatchManagementRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Create
    public function insert(BatchManagement $batch): bool {
        $sql = "INSERT INTO tb_batch_management 
                (batch_name, batch_file, batch_split_date, department, database_name, table_name, phone_prefix, phone_nombre, archiver)
                VALUES (:batch_name, :batch_file, :batch_split_date, :department, :database_name, :table_name, :phone_prefix, :phone_nombre, :archiver)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':batch_name'       => $batch->getBatchName(),
            ':batch_file'       => $batch->getBatchFile(),
            ':batch_split_date' => $batch->getBatchSplitDate(),
            ':department'       => $batch->getDepartment(),
            ':database_name'    => $batch->getDatabaseName(),
            ':table_name'       => $batch->getTableName(),
            ':phone_prefix'     => $batch->getPhonePrefix(),
            ':phone_nombre'     => $batch->getPhoneNombre(),
            ':archiver'         => $batch->getArchiver() ? 1 : 0
        ]);
    }

    // Read by ID
    public function findById(int $id): ?BatchManagement {
        $stmt = $this->pdo->prepare("SELECT * FROM tb_batch_management WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $batch = new BatchManagement();
        $batch->setId($row['id']);
        $batch->setBatchName($row['batch_name']);
        $batch->setBatchFile($row['batch_file']);
        $batch->setBatchSplitDate($row['batch_split_date']);
        $batch->setDepartment($row['department']);
        $batch->setDatabaseName($row['database_name']);
        $batch->setTableName($row['table_name']);
        $batch->setPhonePrefix($row['phone_prefix']);
        $batch->setPhoneNombre($row['phone_nombre']);
        $batch->setArchiver((bool)$row['archiver']);
        $batch->setDateInsertion($row['date_insertion']);
        $batch->setDateModification($row['date_modification']);
        return $batch;
    }

    // Update
    public function update(BatchManagement $batch): bool {
        $sql = "UPDATE tb_batch_management SET
                batch_name = :batch_name,
                batch_file = :batch_file,
                batch_split_date = :batch_split_date,
                department = :department,
                database_name = :database_name,
                table_name = :table_name,
                phone_prefix = :phone_prefix,
                phone_nombre = :phone_nombre,
                archiver = :archiver
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':batch_name'       => $batch->getBatchName(),
            ':batch_file'       => $batch->getBatchFile(),
            ':batch_split_date' => $batch->getBatchSplitDate(),
            ':department'       => $batch->getDepartment(),
            ':database_name'    => $batch->getDatabaseName(),
            ':table_name'       => $batch->getTableName(),
            ':phone_prefix'     => $batch->getPhonePrefix(),
            ':phone_nombre'     => $batch->getPhoneNombre(),
            ':archiver'         => $batch->getArchiver() ? 1 : 0,
            ':id'               => $batch->getId()
        ]);
    }

    // Delete
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM tb_batch_management WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // List all
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM tb_batch_management");
        $batches = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch = new BatchManagement();
            $batch->setId($row['id']);
            $batch->setBatchName($row['batch_name']);
            $batch->setBatchFile($row['batch_file']);
            $batch->setBatchSplitDate($row['batch_split_date']);
            $batch->setDepartment($row['department']);
            $batch->setDatabaseName($row['database_name']);
            $batch->setTableName($row['table_name']);
            $batch->setPhonePrefix($row['phone_prefix']);
            $batch->setPhoneNombre($row['phone_nombre']);
            $batch->setArchiver((bool)$row['archiver']);
            $batch->setDateInsertion($row['date_insertion']);
            $batch->setDateModification($row['date_modification']);
            $batches[] = $batch;
        }
        return $batches;
    }
}
?>
