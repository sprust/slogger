<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;

interface MakeMetricIndicatorsActionInterface
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array;
}
