<?php

namespace App\Modules\Service\Contracts\Actions;

use App\Modules\Service\Entities\ServiceObject;

interface FindServicesActionInterface
{
    /**
     * @return ServiceObject[]
     */
    public function handle(): array;
}
