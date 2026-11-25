<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class ReservationPolicy
{
    public function __construct(protected PermissionService $service) {}

    public function create(User $user): bool
    {
        return $this->service->isAuthorized('reservation.store', $user);
    }

    public function confirm(User $user, \App\Models\Reservation $reservation): bool
    {
        return $this->service->isAuthorized('reservation.confirm', $user) && $user->id === (int) $reservation->user_id;
    }
}

