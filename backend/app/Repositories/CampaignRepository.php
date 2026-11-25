<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket;
use App\Models\User;
class CampaignRepository extends BaseRepository
{
    public function __construct(protected Campaign $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public const PUBLIC_STATES = ['ativa', 'encerrada', 'cancelada'];

    protected function catalog()
    {
        return $this->model->newQuery()->toBase()->from('campaigns as c')
            ->join('organizers as o', 'o.user_id', '=', 'c.owner_id')
            ->join('users as u', 'u.id', '=', 'c.owner_id')->join('categories as k', 'k.id', '=', 'c.category_id')
            ->select('c.*', 'o.public_name', 'k.name as category', 'u.suspended')
            ->selectSub(Ticket::query()->selectRaw('count(*)')->whereColumn('campaign_id', 'c.id')->whereNotNull('reservation_id'), 'occupied');
    }

    public function catalogAll(array $filters = [])
    {
        $query = $this->catalog()->whereIn('c.status', self::PUBLIC_STATES)->where('u.suspended', false);
        if (($filters['q'] ?? '') !== '') $query->where('c.title', 'like', '%'.$filters['q'].'%');
        if (($filters['category'] ?? '') !== '') $query->where('c.category_id', (int) $filters['category']);
        if (($filters['sort'] ?? '') === 'popular') $query->orderByDesc('occupied');

        return $query->orderByDesc('c.created_at')->orderByDesc('c.id')->get();
    }

    public function detail(int $id)
    {
        return $this->catalog()->where('c.id', $id)->first();
    }

    public function owned(int $userId)
    {
        return $this->catalog()->where('c.owner_id', $userId)->orderByDesc('c.id')->get();
    }

    public function byOrganizer(int $userId)
    {
        return $this->catalog()->where('c.owner_id', $userId)->whereIn('c.status', self::PUBLIC_STATES)->get();
    }

    public function listForAdministration()
    {
        return $this->catalog()->orderByDesc('c.id')->get();
    }

    public function lock(int $id, ?int $ownerId = null): ?Campaign
    {
        $query = $this->model->newQuery()->whereKey($id);
        if ($ownerId !== null) $query->where('owner_id', $ownerId);

        return $query->lockForUpdate()->first();
    }

    public function storeDraft(array $data, int $ownerId): Campaign
    {
        $campaign = $this->model->newInstance();
        $campaign->forceFill($data + ['owner_id' => $ownerId])->save();

        return $campaign;
    }

    public function saveChanges(Campaign $campaign, array $data): Campaign
    {
        $campaign->forceFill($data)->save();

        return $campaign;
    }

    public function photoVisible(string $name, User $user, bool $viewPrivate = false): bool
    {
        return $this->catalog()->whereJsonContains('c.photos', '/api/fotos/'.$name)
            ->where(function ($query) use ($user, $viewPrivate) {
                if ($viewPrivate) return;
                $query->where('c.owner_id', $user->id)->orWhere(fn ($q) => $q->whereIn('c.status', self::PUBLIC_STATES)->where('u.suspended', false));
            })->exists();
    }

}

