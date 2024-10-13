<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Transports\TraceDetailTransport;

readonly class FindTraceDetailAction implements FindTraceDetailActionInterface
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
