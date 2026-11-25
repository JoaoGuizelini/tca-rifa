<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends BaseRepository
{
    public function __construct(protected Category $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }

    public function findForUpdate(int|string $id): ?Category
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    public function hasCampaigns(Category $category): bool
    {
        return $category->campaigns()->exists();
    }
}
