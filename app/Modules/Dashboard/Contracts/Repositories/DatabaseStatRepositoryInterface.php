<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Contracts\Repositories;

use App\Modules\Dashboard\Entities\DatabaseStatObject;

interface DatabaseStatRepositoryInterface
{
    /**
     * @return DatabaseStatObject[]
     */
    public function find(): array;
}
