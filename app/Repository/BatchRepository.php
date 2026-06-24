<?php
// /app/Repository/BatchRepository.php
namespace App\Repository;

use PDO;
use App\Model\Batch;

class BatchRepository
{
    private $pdo;
    private $database;
    private $table;

    public function __construct(PDO $pdo, string $database, string $table)
    {
        $this->pdo = $pdo;
        $this->database = $database;
        $this->table = $table;
    }

    private function getFullTableName(): string
    {
        return "`{$this->database}`.`{$this->table}`";
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM " . $this->getFullTableName());
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => new Batch($row), $rows);
    }

    public function findByTel(string $tel): ?Batch
    {
        $stmt = $this->pdo->prepare("SELECT * FROM " . $this->getFullTableName() . " WHERE tel1 = :tel1 LIMIT 1");
        $stmt->execute(['tel1' => $tel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new Batch($row) : null;
    }

    public function insert(Batch $batch): bool
    {
        $data = $batch->toArray();
        $fields = array_keys($data);
        $fieldsList = implode(",", $fields);
        $placeholders = ":" . implode(", :", $fields);

        $sql = "INSERT INTO " . $this->getFullTableName() . " ($fieldsList) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }

    public function getAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $batches = [];
        foreach ($rows as $row) {
            $batches[] = new \App\Model\Batch($row);
        }
        return $batches;
    }
    
    public function getPhoneWithout404(): array{
        $sql = "SELECT * FROM " . $this->getFullTableName() . " WHERE crm_status != :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['status' => '404']);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Batch($row), $rows);
    }

}
