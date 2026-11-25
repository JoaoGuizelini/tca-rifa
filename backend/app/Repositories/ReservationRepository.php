<?php

namespace App\Repositories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Model;

class ReservationRepository extends BaseRepository
{
    public function __construct(protected Reservation $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function expiredCampaignIds()
    {
        return $this->model->newQuery()->where('status', 'reservada')->where('expires', '<=', now())->distinct()->pluck('campaign_id');
    }

    public function expiredLocked(int $campaignId)
    {
        return $this->model->newQuery()->where('campaign_id', $campaignId)->where('status', 'reservada')->where('expires', '<=', now())->lockForUpdate()->get();
    }

    public function expire(Reservation $reservation): void
    {
        $reservation->forceFill(['status' => 'expirada'])->save();
    }

    public function storeForUser(int $userId, int $campaignId, array $numbers, int $total): Reservation
    {
        $reservation = $this->model->newInstance();
        $reservation->forceFill(['user_id' => $userId, 'campaign_id' => $campaignId, 'numbers' => $numbers,
            'total' => $total, 'expires' => now()->addMinutes(15)])->save();

        return $reservation;
    }

    public function owned(int $id, int $userId): ?Reservation
    {
        return $this->model->newQuery()->whereKey($id)->where('user_id', $userId)->first();
    }

    public function confirm(int $id): int
    {
        return $this->model->newQuery()->whereKey($id)->where('status', 'reservada')->where('expires', '>', now())->update(['status' => 'confirmada']);
    }

    public function hasActive(int $campaignId): bool
    {
        return $this->model->newQuery()->where('campaign_id', $campaignId)->whereIn('status', ['reservada', 'confirmada'])->exists();
    }

    public function mine(int $userId)
    {
        return $this->model->newQuery()->toBase()->from('reservations as b')->join('campaigns as c', 'c.id', '=', 'b.campaign_id')
            ->where('b.user_id', $userId)->orderByDesc('b.id')->get(['b.*', 'c.title', 'c.winner']);
    }

}

