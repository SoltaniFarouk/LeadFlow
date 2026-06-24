<?php
// /app/Controller/BatchController.php
namespace App\Controller;

use App\Repository\BatchRepository;
use App\Model\Batch;

class BatchController
{
    private $repository;

    public function __construct(BatchRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getByTel(string $tel): ?Batch
    {
        return $this->repository->findByTel($tel);
    }

    public function create(array $data): bool
    {
        $batch = new Batch($data);
        return $this->repository->insert($batch);
    }

    public function getPhoneWithout404(): array
    {
        return $this->repository->getPhoneWithout404();
    }
}
