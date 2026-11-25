<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    abstract protected function getModel(): Model;

    protected function query(array $arrWith = [], array $where = []): Builder
    {
        $query = $this->getModel()->newQuery()->with($arrWith);
        if (isset($where['field']) && array_key_exists('value', $where)) {
            $query->where($where['field'], $where['value']);
        }

        return $query;
    }

    public function list(array $arrWith = [], array $where = [], string $orderBy = 'id')
    {
        return $this->query($arrWith, $where)->orderBy($orderBy)->get();
    }

    public function listPaginate(array $arrWith = [], array $where = [], string $orderBy = 'id', int $limit = 6)
    {
        return $this->query($arrWith, $where)->orderBy($orderBy)->paginate($limit);
    }

    public function find(int|string $id, array $arrWith = []): ?Model
    {
        return $this->query($arrWith)->find($id);
    }

    public function store(array $data): Model
    {
        return $this->getModel()->newQuery()->create($data);
    }

    public function update(array $data, int|string $id): Model
    {
        $row = $this->getModel()->newQuery()->findOrFail($id);
        $row->update($data);

        return $row;
    }

    public function remove(int|string $id): bool
    {
        return (bool) $this->getModel()->newQuery()->findOrFail($id)->delete();
    }
}
