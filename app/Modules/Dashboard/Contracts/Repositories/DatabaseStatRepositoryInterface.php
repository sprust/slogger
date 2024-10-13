<?php

namespace App\Modules\Dashboard\Contracts\Repositories;

use App\Modules\Dashboard\Entities\DatabaseStatObject;

interface DatabaseStatRepositoryInterface
{
    /**
     * @return DatabaseStatObject[]
     */
    public function find(): array;
}
