<?php

namespace App\Modules\Dashboard\Contracts\Repositories;

use App\Modules\Dashboard\Entities\ServiceStatRawObject;

interface ServiceStatRepositoryInterface
{
    /**
     * @return ServiceStatRawObject[]
     */
    public function find(): array;
}
