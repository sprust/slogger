<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;

readonly class UpdateTraceManyAction implements UpdateTraceManyActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceUpdateParametersList $parametersList): int
    {
        return $this->traceRepository->updateMany(
            $parametersList->getItems()
        );
    }
}
