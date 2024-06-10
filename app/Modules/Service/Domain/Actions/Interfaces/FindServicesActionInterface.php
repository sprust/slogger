<?php

namespace App\Modules\Service\Domain\Actions\Interfaces;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;

interface FindServicesActionInterface
{
    /**
     * @return ServiceObject[]
     */
    public function handle(): array;
}
