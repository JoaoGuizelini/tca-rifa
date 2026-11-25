<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository
{
    public function __construct(protected User $model, protected RoleRepository $roles) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function store(array $data): Model
    {
        $role = $this->roles->findByName(Role::PARTICIPANT);
        if (! $role) throw new \RuntimeException('Papel de participante não configurado.');
        $user = $this->model->newInstance($data);
        $user->role()->associate($role);
        $user->save();

        return $user;
    }

    public function lock(int $id): ?User
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    public function isSuspended(int $id): bool
    {
        return (bool) $this->model->newQuery()->whereKey($id)->value('suspended');
    }

    public function listForAdministration()
    {
        return $this->model->newQuery()->toBase()->join('roles', 'roles.id', '=', 'users.role_id')
            ->get(['users.id', 'users.name', 'users.email', 'roles.name as role', 'users.suspended']);
    }

    public function approveOrganizer(int $id): void
    {
        $role = $this->roles->findByName(Role::ORGANIZER);
        if (! $role) throw new \RuntimeException('Papel de organizador não configurado.');
        $this->model->newQuery()->whereKey($id)->update(['role_id' => $role->id]);
    }

    public function suspend(int $id, bool $suspended): void
    {
        $this->model->newQuery()->whereKey($id)->update(['suspended' => $suspended]);
    }


}

