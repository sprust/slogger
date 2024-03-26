<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Dto\Objects\ServiceStat\ServiceStatDto;

interface ServiceStatRepositoryInterface
{
    /**
     * @return ServiceStatDto[]
     */
    public function find(): array;
}
