<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;

readonly class FindTraceDataRangeAction
{
    public function __construct(
        private PeriodicTraceService $periodicTraceService,
        private PeriodicTraceCollectionNameService $periodicTraceCollectionNameService
    ) {
    }

    public function handle(): TraceDataRangeObject
    {
        $collectionNames = $this->periodicTraceService->detectCollectionNames();

        if (count($collectionNames) === 0) {
            return new TraceDataRangeObject(firstHour: null, lastHour: null);
        }

        return new TraceDataRangeObject(
            firstHour: $this->periodicTraceCollectionNameService->makeHourStart($collectionNames[0]),
            lastHour: $this->periodicTraceCollectionNameService->makeHourStart(
                $collectionNames[count($collectionNames) - 1]
            )
        );
    }
}
