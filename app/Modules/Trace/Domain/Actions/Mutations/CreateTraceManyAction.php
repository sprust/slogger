<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Services\Locker\TraceLocker;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Repositories\TraceBufferRepository;

readonly class CreateTraceManyAction
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
                    class: TraceBufferRepository::class
                )
                ->create(
                    trace: $trace
                );
        }
    }
}
