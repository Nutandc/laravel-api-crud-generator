<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Nutandc\ApiCrud\Repositories\Contracts\RepositoryInterface;

/**
 * @template TModel of Model
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var TModel */
    protected Model $model;

    /**
     * @param TModel $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) config('api-crud-generator.pagination.per_page', 15);

        return $this->query()->paginate($perPage);
    }

    /**
     * @return TModel|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * @return TModel
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @return TModel
     */
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param TModel $model
     * @return TModel
     */
    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model;
    }

    /**
     * @param TModel $model
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
