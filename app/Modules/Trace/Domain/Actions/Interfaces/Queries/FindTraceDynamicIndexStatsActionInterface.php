<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexStatsObject;

interface FindTraceDynamicIndexStatsActionInterface
{
    public function handle(): TraceDynamicIndexStatsObject;
}
