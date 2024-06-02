<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\Trace\Domain\Entities\Transports\TraceDetailTransport;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

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
