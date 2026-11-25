<?php

namespace App\Repositories;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Model;

class OrganizerRepository extends BaseRepository
{
    public function __construct(protected Organizer $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function profile(int $id)
    {
        return $this->model->newQuery()->toBase()->where('user_id', $id)->first(['public_name', 'bio', 'status', 'reason']);
    }

    public function approvedProfile(int $id)
    {
        return $this->model->newQuery()->toBase()->join('users', 'users.id', '=', 'organizers.user_id')
            ->where('user_id', $id)->where('suspended', false)->where('status', 'aprovado')
            ->first(['user_id', 'public_name', 'bio', 'status']);
    }

    public function submit(int $id, array $data): void
    {
        $this->model->newQuery()->toBase()->updateOrInsert(['user_id' => $id], $data + ['status' => 'pendente', 'reason' => null]);
    }

    public function decide(int $id, string $status, string $reason): int
    {
        return $this->model->newQuery()->where(['user_id' => $id, 'status' => 'pendente'])->update(compact('status', 'reason'));
    }

    public function document(int $id): ?string
    {
        return $this->model->newQuery()->where('user_id', $id)->value('document');
    }

    public function listForAdministration()
    {
        return $this->model->newQuery()->toBase()->get(['user_id', 'public_name', 'status', 'reason', 'bio']);
    }

}

