<?php

namespace App\Modules\Dashboard\Repositories\Interfaces;

use App\Modules\Dashboard\Repositories\Dto\ServiceStatDto;

interface ServiceStatRepositoryInterface
{
    /**
     * @return ServiceStatDto[]
     */
    public function find(): array;
}
