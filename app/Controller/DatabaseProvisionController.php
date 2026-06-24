<?php
namespace App\Controller;

use App\Service\DatabaseProvisionService;
use PDOException;

class DatabaseProvisionController
{
    private DatabaseProvisionService $service;

    public function __construct(DatabaseProvisionService $service)
    {
        $this->service = $service;
    }

    public function processDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            echo "❌ Invalid directory path: $directory\n";
            return;
        }

        $dh = opendir($directory);
        if (!$dh) {
            echo "❌ Could not open directory.\n";
            return;
        }

        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!is_dir($directory . $file)) {
                continue;
            }

            echo "Creating database for: $file\n";

            try {
                $databaseName = $this->service->provisionFromDirectoryName($file);
                echo "✅ Database '$databaseName' created successfully.\n";
            } catch (PDOException $e) {
                echo "❌ Error creating database for '$file': " . $e->getMessage() . "\n";
            }
        }

        closedir($dh);
    }
}