<?php
namespace App\Repository;

use PDO;
use PDOException;

class DatabaseProvisionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createDatabase(string $databaseName): void
    {
        $this->pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `$databaseName` 
             CHARACTER SET utf8mb4 
             COLLATE utf8mb4_unicode_ci"
        );
    }
}