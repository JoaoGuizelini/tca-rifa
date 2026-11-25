<?php

namespace App\Services;

use App\Models\Organizer;
use App\Models\User;
use App\Repositories\CampaignRepository;
use App\Repositories\OrganizerRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class OrganizerService
{
    public function __construct(
        protected OrganizerRepository $repository,
        protected UserRepository $users,
        protected CampaignRepository $campaigns,
    ) {}

    public function find(int $id): array
    {
        $organizer = $this->repository->approvedProfile($id);
        abort_unless($organizer, 404);

        return ['organizer' => $organizer, 'campaigns' => $this->campaigns->byOrganizer($id)];
    }

    public function mine(int $userId)
    {
        return $this->repository->profile($userId);
    }

    public function store(array $data, User $user): void
    {
        Gate::forUser($user)->authorize('create', Organizer::class);
        $path = null;
        try {
            DB::transaction(function () use ($data, $user, &$path) {
                $this->users->lock($user->id);
                $old = $this->repository->find($user->id);
                abort_if($old && $old->status !== 'rejeitado', 409, 'Já existe uma solicitação em análise ou aprovada.');
                $data['document'] = $path = $data['document']->store('identidade', 'local');
                $this->repository->submit($user->id, $data);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function decide(int $id, array $data, User $user): array
    {
        Gate::forUser($user)->authorize('decide', Organizer::class);

        return DB::transaction(function () use ($id, $data, $user) {
            $status = (bool) $data['approve'] ? 'aprovado' : 'rejeitado';
            abort_unless($this->repository->decide($id, $status, $data['reason']), 409);
            if ($data['approve']) $this->users->approveOrganizer($id);

            return ['status' => $status];
        });
    }

    public function document(int $id, User $user): string
    {
        Gate::forUser($user)->authorize('document', Organizer::class);
        $path = $this->repository->document($id);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return $path;
    }
}
