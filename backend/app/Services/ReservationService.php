<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use App\Repositories\CampaignRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReservationService
{
    public function __construct(
        protected ReservationRepository $repository,
        protected CampaignRepository $campaigns,
        protected TicketRepository $tickets,
        protected UserRepository $users,
    ) {}

    // Always lock campaign before reservations and tickets, including expiration.
    public function expireLocked(int $campaignId): void
    {
        foreach ($this->repository->expiredLocked($campaignId) as $reservation) {
            $this->tickets->release($reservation->id);
            $this->repository->expire($reservation);
        }
    }

    public function sweep(): void
    {
        foreach ($this->repository->expiredCampaignIds() as $id) {
            DB::transaction(function () use ($id) {
                $this->campaigns->lock($id);
                $this->expireLocked($id);
            }, 3);
        }
    }

    public function open(object $campaign): bool
    {
        $today = now('America/Sao_Paulo')->toDateString();

        return $campaign->status === 'ativa' && $today >= $campaign->starts && $today <= $campaign->ends
            && ! $this->users->isSuspended($campaign->owner_id);
    }

    public function store(int $campaignId, array $numbers, User $user): int
    {
        Gate::forUser($user)->authorize('create', Reservation::class);
        $this->sweep();

        return DB::transaction(function () use ($campaignId, $numbers, $user) {
            $campaign = $this->campaigns->lock($campaignId);
            abort_unless($campaign, 404);
            abort_unless($this->open($campaign) && (int) $campaign->owner_id !== $user->id, 409, 'Campanha indisponível para reserva.');
            $this->expireLocked($campaignId);
            abort_unless($this->tickets->availableCount($campaignId, $numbers) === count($numbers), 409, 'Um dos números ficou indisponível. Atualize sua seleção.');
            $reservation = $this->repository->storeForUser($user->id, $campaignId, $numbers, $campaign->price * count($numbers));
            $this->tickets->assign($campaignId, $numbers, $reservation->id);

            return $reservation->id;
        }, 3);
    }

    public function confirm(int $id, User $user): void
    {
        $this->sweep();
        $reservation = $this->repository->owned($id, $user->id);
        abort_unless($reservation, 404);
        Gate::forUser($user)->authorize('confirm', $reservation);
        DB::transaction(function () use ($reservation, $user) {
            $campaign = $this->campaigns->lock($reservation->campaign_id);
            abort_unless($campaign && $this->open($campaign), 409, 'Campanha indisponível.');
            abort_unless($this->repository->confirm($reservation->id), 409, 'Reserva vencida ou já confirmada.');
        }, 3);
    }

    public function mine(int $userId)
    {
        $this->sweep();

        return $this->repository->mine($userId);
    }
}
