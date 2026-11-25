<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\PermissionRepository;

class PermissionService extends BaseService
{
    public function __construct(protected PermissionRepository $repository) {}

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    public function getPermissions(int $role): array
    {
        $permissions = [];
        foreach ($this->repository->list(['resource'], ['field' => 'role_id', 'value' => $role], 'resource_id') as $item) {
            $permissions[$item->resource->name] = true;
        }

        return $permissions;
    }

    public function loadPermissions(int $role): array
    {
        // Refresh from the database; never reuse a session permission snapshot.
        return $this->getPermissions($role);
    }

    public function isAuthorized(string $resource, ?User $user): bool
    {
        return $user !== null && ! $user->suspended && $this->repository->isAllowed($resource, $user->id);
    }
}
