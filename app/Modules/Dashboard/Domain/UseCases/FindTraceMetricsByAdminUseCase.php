<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\UseCases;

use App\Modules\Dashboard\Entities\TraceMetricObject;
use Illuminate\Support\Carbon;
use App\Modules\Dashboard\Domain\Actions\FindTraceMetricsAction;

readonly class FindTraceMetricsByAdminUseCase
{
    public function __construct(
        private FindTraceMetricsAction $findTraceMetricsAction
    ) {
    }

    /**
     * @return TraceMetricObject[]
     */
    public function handle(int $serviceId, Carbon $now): array
    {
        return $this->findTraceMetricsAction->handle(
            serviceId: $serviceId,
            now: $now,
        );
    }
}
