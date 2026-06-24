<?php
namespace App\Connection;

use PDO;
use PDOException;

class ConfigLeadFrDatabase {
    private string $host;
    private int    $port;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset = 'utf8mb4';

    public function __construct() {
        $this->host     = $this->requireEnv('DB_CONFIG_LEAD_FR_HOST');
        $this->port     = (int) $this->requireEnv('DB_CONFIG_LEAD_FR_PORT');
        $this->dbname   = $this->requireEnv('DB_CONFIG_LEAD_FR_NAME');
        $this->username = $this->requireEnv('DB_CONFIG_LEAD_FR_USER');
        $this->password = $this->requireEnv('DB_CONFIG_LEAD_FR_PASS');
    }

    private function requireEnv(string $key): string {
        $value = $_ENV[$key] ?? null;
        if ($value === null) {
            throw new \RuntimeException("Missing environment variable: {$key}");
        }
        return $value;
    }

    public function connect(): PDO {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            return new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
}