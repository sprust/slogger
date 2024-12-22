<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
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
        $successCount = 0;

        foreach ($parametersList->getItems() as $trace) {
            $success = $this->traceLocker
                ->resolve(
                    traceId: $trace->traceId,
                    class: TraceRepositoryInterface::class
                )
                ->updateOne(
                    trace: $trace
                );

            if ($success) {
                $successCount++;
            }
        }

        return $successCount;
    }
}
