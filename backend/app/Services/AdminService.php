<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Role;
use App\Repositories\CampaignRepository;
use App\Repositories\OrganizerRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminService
{
    public function __construct(
        protected UserRepository $users,
        protected OrganizerRepository $organizers,
        protected CampaignRepository $campaigns,
        protected TicketRepository $tickets,
    ) {}

    public function all(User $user): array
    {
        Gate::forUser($user)->authorize('administer', User::class);

        return ['users' => $this->users->listForAdministration(),
            'organizers' => $this->organizers->listForAdministration(),
            'campaigns' => $this->campaigns->listForAdministration()];
    }

    public function campaign(int $id, array $data, User $user): array
    {
        Gate::forUser($user)->authorize('decide', Campaign::class);

        return DB::transaction(function () use ($id, $data, $user) {
            $campaign = $this->campaigns->lock($id);
            abort_unless($campaign && $campaign->status === 'pendente', 409, 'Campanha fora da fila.');
            $status = (bool) $data['approve'] ? 'ativa' : 'rejeitada';
            if ($status === 'ativa') {
                abort_if($campaign->ends < now('America/Sao_Paulo')->toDateString(), 422, 'O período já terminou.');
                $this->tickets->generate($id, $campaign->quantity);
            }
            $this->campaigns->saveChanges($campaign, ['status' => $status, 'reason' => $data['reason']]);

            return ['status' => $status];
        });
    }

    public function suspend(int $id, bool $suspended, User $user): void
    {
        Gate::forUser($user)->authorize('suspend', User::class);
        DB::transaction(function () use ($id, $suspended, $user) {
            $target = $this->users->find($id);
            abort_unless($target, 404);
            abort_if($target->hasRole(Role::ADMINISTRATOR), 403, 'Administrador protegido.');
            $this->users->suspend($id, $suspended);
        });
    }

}
