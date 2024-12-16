<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\CreateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\Locker\TraceLocker;
use App\Modules\Trace\Parameters\TraceCreateParametersList;

readonly class CreateTraceManyAction implements CreateTraceManyActionInterface
{
    public function __construct(
        private TraceLocker $traceLocker,
    ) {
    }

    public function handle(TraceCreateParametersList $parametersList): void
    {
        foreach ($parametersList->getItems() as $trace) {
            $this->traceLocker
                ->resolve(
                    traceId: $trace->traceId,
                    class: TraceRepositoryInterface::class
                )
                ->createOne(
                    trace: $trace
                );
        }
    }
}
