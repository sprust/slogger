<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;

readonly class MakeTraceTimestampPeriodsByAdminUseCase
{
    public function __construct(private MakeTraceTimestampPeriodsAction $makeTraceTimestampPeriodsAction)
    {
    }

    /**
     * @return TraceTimestampPeriodObject[]
     */
    public function handle(): array
    {
        return $this->makeTraceTimestampPeriodsAction->handle();
    }
}
