<?php
namespace App\Controller;

use App\Service\BatchTableService;
use PDO;
use Exception;
use PDOException;

class BatchTableController
{
    private BatchTableService $service;

    public function __construct(BatchTableService $service)
    {
        $this->service = $service;
    }

    /**
     * Process all TXT files inside one subdirectory.
     *
     * @param string   $subdirectory  Full path to subdirectory
     * @param string   $databaseName  e.g. dbx_dep01
     * @param callable $getConnection function(string $depCode): PDO
     */
    public function processDirectory(
        string $subdirectory,
        string $databaseName,
        callable $getConnection
    ): void {
        $txtFiles = glob($subdirectory . "*.txt");

        if (empty($txtFiles)) {
            echo "⚠️ No TXT files found.\n";
            return;
        }

        foreach ($txtFiles as $filePath) {
            $currentFile = basename($filePath);
            echo "\n📄 File: $currentFile\n";

            // Get target DB connection
            $depCode = $this->service->getDepCode($currentFile);
            try {
                $pdoTarget = $getConnection($depCode);
            } catch (Exception $e) {
                echo "❌ Could not connect to target DB: " . $e->getMessage() . "\n";
                continue;
            }

            // Process file
            try {
                $meta = $this->service->processFile($currentFile, $databaseName, $pdoTarget);

                echo " - batch_name: {$meta['batch_name']}\n";
                echo " - batch_split_date: {$meta['batch_split_date']}\n";
                echo " - department: {$meta['department']}\n";
                echo " - database_name: {$meta['database_name']}\n";
                echo " - table_name: {$meta['table_name']}\n";
                echo " - phone_prefix: {$meta['phone_prefix']}\n";
                echo "✅ Inserted batch into tb_batch_management\n";
                echo "✅ Table `{$meta['table_name']}` created in `{$meta['database_name']}`\n";

            } catch (PDOException $e) {
                echo "❌ Processing failed for $currentFile: " . $e->getMessage() . "\n";
            }
        }
    }
}