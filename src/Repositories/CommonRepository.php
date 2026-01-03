<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @extends BaseRepository<TModel>
 */
final class CommonRepository extends BaseRepository
{
    /**
     * @param TModel $model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);
    }
}
