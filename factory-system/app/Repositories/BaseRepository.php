<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Shared Eloquent data-access operations for concrete repositories.
 */
abstract class BaseRepository
{
    public function __construct(protected readonly Model $model) {}

    /**
     * @return Model|null
     */
    public function findById(int $id)
    {
        return $this->query()->find($id);
    }

    /**
     * @return Model
     */
    public function findByIdOrFail(int $id)
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Model
     */
    public function create(array $data)
    {
        return $this->query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Model
     */
    public function update(Model $model, array $data)
    {
        $model->fill($data);
        $model->save();

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    /**
     * @return Model
     */
    public function restore(int $id)
    {
        if (! $this->supportsSoftDeletes()) {
            throw new LogicException('Repository model does not support soft deletes.');
        }

        $model = $this->model->newQueryWithoutScopes()
            ->whereKey($id)
            ->firstOrFail();

        $model->restore();

        return $model->refresh();
    }

    public function paginate(Builder $query, int $perPage = 0): LengthAwarePaginator
    {
        $resolvedPerPage = $perPage > 0
            ? $perPage
            : (int) config('factory.pagination.per_page', 20);

        return $query->paginate($resolvedPerPage)->withQueryString();
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    protected function model(): Model
    {
        return $this->model;
    }

    private function supportsSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model::class), true);
    }
}
