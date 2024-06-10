<?php

namespace App\Modules\Dashboard\Domain\Actions\Interfaces;

use App\Modules\Dashboard\Domain\Entities\Objects\ServiceStatObject;

interface FindServiceStatActionInterface
{
    /**
     * @return ServiceStatObject[]
     */
    public function handle(): array;
}
