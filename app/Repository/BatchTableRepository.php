<?php
namespace App\Repository;

use PDO;
use PDOException;

class BatchTableRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insertBatchManagement(
        string $batchName,
        string $batchSplitDate,
        string $department,
        string $databaseName,
        string $tableName,
        ?string $phonePrefix
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_batch_management 
                (batch_name, batch_split_date, department, database_name, table_name, phone_prefix) 
             VALUES 
                (:batch_name, :batch_split_date, :department, :database_name, :table_name, :phone_prefix)"
        );
        $stmt->execute([
            ':batch_name'       => $batchName,
            ':batch_split_date' => $batchSplitDate,
            ':department'       => $department,
            ':database_name'    => $databaseName,
            ':table_name'       => $tableName,
            ':phone_prefix'     => $phonePrefix,
        ]);
    }

    public function createBatchTable(PDO $pdoTarget, string $tableName): void
    {
        $pdoTarget->exec("CREATE TABLE IF NOT EXISTS `$tableName` (
            tel1               VARCHAR(15)  PRIMARY KEY NOT NULL,
            code_postal        VARCHAR(20)  NULL,
            ville              VARCHAR(255) NULL,
            adresse            VARCHAR(255) NULL,
            genre              VARCHAR(10)  NULL,
            nom                VARCHAR(255) NULL,
            prenom             VARCHAR(255) NULL,
            tel2               VARCHAR(50)  NULL,
            tel3               VARCHAR(50)  NULL,
            mobile             VARCHAR(50)  NULL,
            fax                VARCHAR(50)  NULL,
            habitat            VARCHAR(255) NULL,
            age_moyen          VARCHAR(50)  NULL,
            ethnie             VARCHAR(100) NULL,
            tel1_prospection   VARCHAR(50)  NULL,
            tel2_prospection   VARCHAR(50)  NULL,
            tel3_prospection   VARCHAR(50)  NULL,
            mobile_prospection VARCHAR(50)  NULL,
            fax_prospection    VARCHAR(50)  NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}