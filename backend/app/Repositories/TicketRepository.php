<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;

class TicketRepository extends BaseRepository
{
    public function __construct(protected Ticket $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function forCampaign(int $campaignId)
    {
        return $this->model->newQuery()->toBase()->from('tickets as t')->leftJoin('reservations as b', 't.reservation_id', '=', 'b.id')
            ->where('t.campaign_id', $campaignId)->orderBy('number')->get(['number', 'b.status']);
    }

    public function release(int $reservationId): void
    {
        $this->model->newQuery()->where('reservation_id', $reservationId)->update(['reservation_id' => null]);
    }

    protected function available(int $campaignId, array $numbers)
    {
        return $this->model->newQuery()->where('campaign_id', $campaignId)->whereIn('number', $numbers)->whereNull('reservation_id');
    }

    public function availableCount(int $campaignId, array $numbers): int
    {
        return $this->available($campaignId, $numbers)->count();
    }

    public function assign(int $campaignId, array $numbers, int $reservationId): void
    {
        $this->available($campaignId, $numbers)->update(['reservation_id' => $reservationId]);
    }

    public function generate(int $campaignId, int $quantity): void
    {
        $rows = [];
        for ($number = 1; $number <= $quantity; $number++) $rows[] = ['campaign_id' => $campaignId, 'number' => $number];
        foreach (array_chunk($rows, 200) as $batch) $this->model->newQuery()->insert($batch);
    }

    public function isConfirmed(int $campaignId, int $number): bool
    {
        return $this->model->newQuery()->from('tickets as t')->join('reservations as b', 'b.id', '=', 't.reservation_id')
            ->where(['t.campaign_id' => $campaignId, 'number' => $number, 'b.status' => 'confirmada'])->exists();
    }

}

