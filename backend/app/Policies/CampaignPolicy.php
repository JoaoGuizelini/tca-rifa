<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class CampaignPolicy
{
    public function __construct(protected PermissionService $service) {}

    public function view(User $user, object $campaign): bool
    {
        return $this->service->isAuthorized('campaign.show', $user)
            && ($this->service->isAuthorized('campaign.viewPrivate', $user) || $user->id === (int) $campaign->owner_id
                || (in_array($campaign->status, \App\Repositories\CampaignRepository::PUBLIC_STATES, true) && ! $campaign->suspended));
    }

    public function create(User $user): bool
    {
        return $this->service->isAuthorized('campaign.store', $user);
    }

    public function update(User $user, object $campaign): bool
    {
        return $this->service->isAuthorized('campaign.update', $user) && $user->id === (int) $campaign->owner_id;
    }

    public function change(User $user, object $campaign): bool
    {
        return $this->service->isAuthorized('campaign.change', $user) && $user->id === (int) $campaign->owner_id;
    }

    public function decide(User $user): bool
    {
        return $this->service->isAuthorized('campaign.decide', $user);
    }
}

