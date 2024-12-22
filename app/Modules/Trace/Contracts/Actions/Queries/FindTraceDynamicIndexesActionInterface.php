<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;

interface FindTraceDynamicIndexesActionInterface
{
    /**
     * @return TraceDynamicIndexObject[]
     */
    public function handle(): array;
}
