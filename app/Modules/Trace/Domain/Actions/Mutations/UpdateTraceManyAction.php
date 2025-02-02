<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Domain\Services\Locker\TraceLocker;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;

readonly class UpdateTraceManyAction implements UpdateTraceManyActionInterface
{
    public function __construct(
        private TraceLocker $traceLocker,
    ) {
    }

    public function handle(TraceUpdateParametersList $parametersList): int
    {
        foreach ($parametersList->getItems() as $trace) {
            $this->traceLocker
                ->resolve(
                    traceId: $trace->traceId,
                    class: TraceBufferRepositoryInterface::class
                )
                ->update(
                    trace: $trace
                );
        }

        return $parametersList->count(); // TODO: delete after release
    }
}
