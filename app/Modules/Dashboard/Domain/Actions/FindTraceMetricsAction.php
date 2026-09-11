<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Entities\TraceMetricObject;
use App\Modules\Dashboard\Repositories\TraceMetricRepository;
use Illuminate\Support\Carbon;

readonly class FindTraceMetricsAction
{
    public const int SLOT_MINUTES = 15;
    public const int SLOTS        = 96;

    public function __construct(
        private TraceMetricRepository $traceMetricRepository
    ) {
    }

    /**
     * @return TraceMetricObject[]
     */
    public function handle(int $serviceId, Carbon $now): array
    {
        $currentSlot = $now->clone()->utc()->startOfMinute();

        $currentSlot->setTime(
            $currentSlot->hour,
            intdiv($currentSlot->minute, self::SLOT_MINUTES) * self::SLOT_MINUTES
        );

        return $this->traceMetricRepository->find(
            serviceId: $serviceId,
            from: $currentSlot->subMinutes((self::SLOTS - 1) * self::SLOT_MINUTES)
        );
    }
}
