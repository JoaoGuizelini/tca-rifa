<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class RoleRepository extends BaseRepository
{
    public function __construct(protected Role $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function findByName(string $name): ?Role
    {
        return $this->model->newQuery()->where('name', $name)->first();
    }
}
