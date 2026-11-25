<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class CategoryPolicy
{
    public function __construct(protected PermissionService $service) {}

    public function viewAny(User $user): bool
    {
        return $this->service->isAuthorized('category.index', $user);
    }

    public function create(User $user): bool
    {
        return $this->service->isAuthorized('category.store', $user);
    }

    public function update(User $user, \App\Models\Category $category): bool
    {
        return $this->service->isAuthorized('category.update', $user);
    }

    public function delete(User $user): bool
    {
        return $this->service->isAuthorized('category.destroy', $user);
    }
}

