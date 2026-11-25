<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;

class PermissionRepository extends BaseRepository
{
    public function __construct(protected Permission $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function isAllowed(string $resource, int $userId): bool
    {
        return $this->model->newQuery()
            ->join('resources', 'resources.id', '=', 'permissions.resource_id')
            ->join('users', 'users.role_id', '=', 'permissions.role_id')
            ->where('users.id', $userId)->where('users.suspended', false)
            ->where('resources.name', $resource)->exists();
    }
}
