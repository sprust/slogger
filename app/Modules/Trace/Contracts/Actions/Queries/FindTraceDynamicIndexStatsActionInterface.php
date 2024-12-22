<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;

interface FindTraceDynamicIndexStatsActionInterface
{
    public function handle(): TraceDynamicIndexStatsObject;
}
