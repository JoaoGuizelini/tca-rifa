<?php

namespace App\Repositories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;

class ResourceRepository extends BaseRepository
{
    public function __construct(protected Resource $model) {}

    protected function getModel(): Model
    {
        return $this->model;
    }
}
