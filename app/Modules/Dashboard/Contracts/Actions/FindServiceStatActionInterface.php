<?php

namespace App\Modules\Dashboard\Contracts\Actions;

use App\Modules\Dashboard\Entities\ServiceStatObject;

interface FindServiceStatActionInterface
{
    /**
     * @return ServiceStatObject[]
     */
    public function handle(): array;
}
