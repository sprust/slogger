<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Domain\Transports\TraceDetailTransport;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceDetailAction
{
    public function __construct(
        private TraceRepositoryInterface $repository
    ) {
    }

    public function handle(string $traceId): ?TraceDetailObject
    {
        return TraceDetailTransport::toDetailObject(
            $this->repository->findOneByTraceId($traceId)
        );
    }
}
