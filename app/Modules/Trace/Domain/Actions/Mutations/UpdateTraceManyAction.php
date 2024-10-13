<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingDataObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObject;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingItemDto;
use App\Modules\Trace\Repositories\Dto\TraceUpdateDto;

readonly class UpdateTraceManyAction implements UpdateTraceManyActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceUpdateParametersList $parametersList): int
    {
        return $this->traceRepository->updateMany(
            array_map(
                fn(TraceUpdateParameters $parameters) => new TraceUpdateDto(
                    serviceId: $parameters->serviceId,
                    traceId: $parameters->traceId,
                    status: $parameters->status,
                    profiling: is_null($parameters->profiling)
                        ? null
                        : new TraceProfilingDto(
                            mainCaller: $parameters->profiling->getMainCaller(),
                            items: array_map(
                                fn(TraceUpdateProfilingObject $profiling) => new TraceProfilingItemDto(
                                    raw: $profiling->raw,
                                    calling: $profiling->calling,
                                    callable: $profiling->callable,
                                    data: array_map(
                                        fn(TraceUpdateProfilingDataObject $data) => new TraceProfilingDataDto(
                                            name: $data->name,
                                            value: $data->value,
                                        ),
                                        $profiling->data
                                    ),
                                ),
                                $parameters->profiling->getItems()
                            )
                        ),
                    tags: $parameters->tags,
                    data: $parameters->data,
                    duration: $parameters->duration,
                    memory: $parameters->memory,
                    cpu: $parameters->cpu,
                ),
                $parametersList->getItems()
            )
        );
    }
}
