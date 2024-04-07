<?php

namespace App\Modules\Dashboard\Repositories\Interfaces;

use App\Modules\Dashboard\Repositories\Dto\DatabaseStatDto;

interface DatabaseStatRepositoryInterface
{
    /**
     * @return DatabaseStatDto[]
     */
    public function find(): array;
}
