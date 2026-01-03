<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = null): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function find(int|string $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int|string $id): Model;

    /**
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param TModel $model
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * @param TModel $model
     */
    public function delete(Model $model): bool;
}
