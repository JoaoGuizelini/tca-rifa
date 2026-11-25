<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Repositories\CampaignRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\TicketRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CampaignService
{
    public function __construct(
        protected CampaignRepository $repository,
        protected ReservationService $reservations,
        protected ReservationRepository $participations,
        protected TicketRepository $tickets,
        protected PermissionService $permissions,
    ) {}

    public function all(array $filters = [])
    {
        $this->reservations->sweep();

        return $this->repository->catalogAll($filters);
    }

    public function find(int $id, User $user): object
    {
        $campaign = $this->repository->detail($id);
        abort_unless($campaign, 404);
        abort_unless(Gate::forUser($user)->allows('view', [Campaign::class, $campaign]), 404);

        return $campaign;
    }

    public function detail(int $id, User $user): array
    {
        $this->reservations->sweep();
        $campaign = $this->find($id, $user);

        return ['campaign' => $campaign, 'tickets' => $this->tickets->forCampaign($id),
            'open' => $this->reservations->open($campaign)];
    }

    public function store(array $data, User $user): array
    {
        Gate::forUser($user)->authorize('create', Campaign::class);

        return $this->save($data, null, $user);
    }

    public function update(array $data, int $id, User $user): array
    {
        return $this->save($data, $id, $user);
    }

    protected function save(array $data, ?int $id, User $user): array
    {
        abort_if(count($data['photos'] ?? []) > 1, 422, 'Envie somente uma foto por campanha.');
        $uploaded = [];
        try {
            return DB::transaction(function () use ($data, $id, $user, &$uploaded) {
                $campaign = $id ? $this->repository->lock($id, $user->id) : null;
                if ($id) {
                    abort_unless($campaign, 404);
                    Gate::forUser($user)->authorize('update', [Campaign::class, $campaign]);
                    abort_unless(in_array($campaign->status, ['rascunho', 'rejeitada'], true), 409, 'Edição permitida em rascunhos ou campanhas rejeitadas.');
                }
                $photos = array_slice($campaign?->photos ?? [], 0, 1);
                if (! empty($data['photos'])) {
                    $photos = [];
                    foreach ($data['photos'] as $file) {
                        $uploaded[] = $path = $file->store('campanhas', 'local');
                        $photos[] = '/api/fotos/'.basename($path);
                    }
                }
                $data['photos'] = $photos;
                $data['status'] = 'rascunho';
                $data['reason'] = null;
                $saved = $campaign ? $this->repository->saveChanges($campaign, $data) : $this->repository->storeDraft($data, $user->id);

                return ['id' => $saved->id];
            });
        } catch (\Throwable $error) {
            foreach ($uploaded as $path) \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function change(int $id, array $data, User $user): array
    {
        return DB::transaction(function () use ($id, $data, $user) {
            $campaign = $this->repository->lock($id, $user->id);
            abort_unless($campaign, 404);
            Gate::forUser($user)->authorize('change', [Campaign::class, $campaign]);
            $states = ['enviar' => [['rascunho', 'rejeitada'], 'pendente'], 
                'cancelar' => [['rascunho', 'pendente', 'rejeitada', 'ativa'], 'cancelada'], 'resultado' => [['ativa'], 'encerrada']];
            [$allowed, $target] = $states[$data['action']];
            abort_unless(in_array($campaign->status, $allowed, true), 409, 'Transição inválida.');
            $this->reservations->expireLocked($id);
            $updates = ['status' => $target];
            if ($data['action'] === 'cancelar') {
                abort_if($this->participations->hasActive($id), 409, 'Há participações ou reservas vigentes.');
            }
            if ($data['action'] === 'resultado') {
                abort_unless(now('America/Sao_Paulo')->toDateString() > $campaign->ends, 409, 'Aguarde o fim do período.');
                abort_unless($this->tickets->isConfirmed($id, (int) $data['winner']), 422, 'Escolha um número confirmado.');
                $updates['winner'] = (int) $data['winner'];
            }
            $this->repository->saveChanges($campaign, $updates);

            return ['status' => $target];
        });
    }

    public function owned(int $userId)
    {
        return $this->repository->owned($userId);
    }

    public function photo(string $name, User $user): string
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)$/i', $name), 404);
        abort_unless($this->repository->photoVisible($name, $user, $this->permissions->isAuthorized('campaign.viewPrivate', $user)), 404);

        return 'campanhas/'.$name;
    }
}
