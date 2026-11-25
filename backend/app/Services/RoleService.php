<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\RoleRepository;
use App\Models\Role;

class RoleService extends BaseService
{
    public function __construct(protected RoleRepository $repository) {}

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    public function findByName(string $name): ?Role
    {
        return $this->repository->findByName($name);
    }
}
