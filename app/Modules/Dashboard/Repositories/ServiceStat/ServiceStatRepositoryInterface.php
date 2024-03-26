<?php

namespace App\Modules\Dashboard\Repositories\ServiceStat;

use App\Modules\Dashboard\Repositories\ServiceStat\Dto\ServiceStatDto;

interface ServiceStatRepositoryInterface
{
    /**
     * @return ServiceStatDto[]
     */
    public function find(): array;
}
