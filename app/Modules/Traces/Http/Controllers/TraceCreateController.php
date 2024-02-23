<?php

namespace App\Modules\Traces\Http\Controllers;

use App\Modules\Traces\Adapters\TraceServicesHttpAdapter;
use App\Modules\Traces\Dto\Parameters\TraceCreateParameters;
use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Http\Requests\TraceCreateRequest;
use App\Modules\Traces\Services\TracesServiceQueueDispatcher;
use Illuminate\Support\Carbon;

readonly class TraceCreateController
{
    public function __construct(
        private TraceServicesHttpAdapter $servicesHttpAdapter,
        private TracesServiceQueueDispatcher $tracesServiceQueueDispatcher
    ) {
    }

    public function __invoke(TraceCreateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->servicesHttpAdapter->getService()->id;

        $parametersList = new TraceCreateParametersList();

        foreach ($validated['traces'] as $item) {
            $parametersList->add(
                new TraceCreateParameters(
                    serviceId: $serviceId,
                    traceId: $item['trace_id'],
                    parentTraceId: $item['parent_trace_id'] ?? null,
                    type: $item['type'],
                    tags: $item['tags'] ?? [],
                    data: $item['data'],
                    duration: $item['duration'],
                    memory: $item['memory'] ?? null,
                    cpu: $item['cpu'] ?? null,
                    loggedAt: new Carbon($item['logged_at']),
                )
            );
        }

        $this->tracesServiceQueueDispatcher->createMany($parametersList);
    }
}
