<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexObject;

interface FindTraceDynamicIndexesActionInterface
{
    /**
     * @return TraceDynamicIndexObject[]
     */
    public function handle(): array;
}
