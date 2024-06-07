<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;

class MakeMetricIndicatorsAction
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array
    {
        return TraceMetricFieldEnum::cases();
    }
}
