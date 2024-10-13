<?php

namespace App\Modules\Trace\Contracts\Actions;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;

interface MakeMetricIndicatorsActionInterface
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array;
}
