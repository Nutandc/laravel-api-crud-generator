<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Repositories;

use Illuminate\Database\Eloquent\Model;

final class CommonRepository extends BaseRepository
{
    /**
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);
    }
}
