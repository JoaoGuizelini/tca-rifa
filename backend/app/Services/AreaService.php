<?php

namespace App\Services;

class AreaService
{
    public function __construct(
        protected ReservationService $reservations,
        protected CampaignService $campaigns,
        protected OrganizerService $organizers,
    ) {}

    public function mine(int $userId): array
    {
        return ['reservations' => $this->reservations->mine($userId)];
    }

    public function studio(int $userId): array
    {
        return ['organizer' => $this->organizers->mine($userId), 'campaigns' => $this->campaigns->owned($userId)];
    }
}
