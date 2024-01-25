<?php

namespace App\Modules\Traces\Http\Controllers;

use App\Modules\Services\Adapters\ServicesHttpAdapter;
use App\Modules\Traces\Enums\TraceTypeEnum;
use App\Modules\Traces\Http\Requests\TraceCreateRequest;
use App\Modules\Traces\Repository\Parameters\TraceCreateParameters;
use App\Modules\Traces\Repository\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Repository\TracesRepository;
use Illuminate\Support\Carbon;

readonly class TraceController
{
    public function __construct(
        private ServicesHttpAdapter $servicesHttpAdapter,
        private TracesRepository $tracesRepository
    ) {
    }

    public function create(TraceCreateRequest $request): void
    {
        $validated = $request->validated();

        $parametersList = new TraceCreateParametersList();

        foreach ($validated['traces'] as $item) {
            $parametersList->add(
                new TraceCreateParameters(
                    serviceId: $this->servicesHttpAdapter->getService()->id,
                    traceId: $item['trace_id'],
                    parentTraceId: $item['parent_trace_id'] ?? null,
                    type: TraceTypeEnum::from($item['type']),
                    tags: $item['tags'] ?? [],
                    data: $item['data'],
                    loggedAt: new Carbon($item['logged_at']),
                )
            );
        }

        $this->tracesRepository->createMany($parametersList);
    }
}
