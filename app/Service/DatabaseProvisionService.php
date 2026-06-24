<?php
namespace App\Service;

use App\Repository\DatabaseProvisionRepository;

class DatabaseProvisionService
{
    private DatabaseProvisionRepository $repo;

    public function __construct(DatabaseProvisionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function provisionFromDirectoryName(string $directoryName): string
    {
        $databaseName = 'dbx_' . strtolower($directoryName);
        $this->repo->createDatabase($databaseName);
        return $databaseName;
    }
}