<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class OrganizerPolicy
{
    public function __construct(protected PermissionService $service) {}

    public function create(User $user): bool
    {
        return $this->service->isAuthorized('organizer.store', $user);
    }

    public function decide(User $user): bool
    {
        return $this->service->isAuthorized('organizer.decide', $user);
    }

    public function document(User $user): bool
    {
        return $this->service->isAuthorized('organizer.document', $user);
    }
}

