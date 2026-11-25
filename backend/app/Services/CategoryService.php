<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;

class CategoryService extends BaseService
{
    public function __construct(
        protected CategoryRepository $repository,
    ) {}

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    public function remove(int|string $id)
    {
        return DB::transaction(function () use ($id) {
            $category = $this->repository->findForUpdate($id);
            if ($category) {
                abort_if($this->repository->hasCampaigns($category), 409, 'Categoria em uso.');
                $this->repository->remove($id);
            }
            return $category !== null;
        });
    }
}
