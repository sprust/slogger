<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;

interface FindTraceDynamicIndexStatsActionInterface
{
    public function handle(): TraceDynamicIndexStatsObject;
}
