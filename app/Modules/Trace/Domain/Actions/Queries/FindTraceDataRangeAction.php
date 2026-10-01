<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindTraceDataRangeAction
{
    public function __construct(
        private TraceRepository $traceRepository,
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(): TraceDataRangeObject
    {
        return $this->traceRepository->findHourRange();
    }
}
