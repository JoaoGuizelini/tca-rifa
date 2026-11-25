<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class UserPolicy
{
    public function __construct(protected PermissionService $service) {}

    public function administer(User $user): bool
    {
        return $this->service->isAuthorized('admin.index', $user);
    }


    public function suspend(User $user): bool
    {
        return $this->service->isAuthorized('user.suspend', $user);
    }

}

