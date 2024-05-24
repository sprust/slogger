<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;

readonly class GetTraceTimestampPeriodsAction
{
    /**
     * @return TraceTimestampPeriodEnum[]
     */
    public function handle(): array
    {
        return TraceTimestampPeriodEnum::cases();
    }
}
