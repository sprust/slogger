<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\CreateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Parameters\TraceCreateParametersList;

readonly class CreateTraceManyAction implements CreateTraceManyActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceCreateParametersList $parametersList): void
    {
        $this->traceRepository->createMany(
            $parametersList->getItems()
        );
    }
}
