<?php

namespace App\Modules\Traces\Http\Controllers;

use App\Modules\Services\Adapters\ServicesHttpAdapter;
use App\Modules\Traces\Dto\Parameters\TraceCreateParameters;
use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Enums\TraceTypeEnum;
use App\Modules\Traces\Http\Requests\TraceCreateRequest;
use App\Modules\Traces\Services\TracesServiceQueueDispatcher;
use Illuminate\Support\Carbon;

readonly class TraceCreateController
{
    public function __construct(
        private ServicesHttpAdapter $servicesHttpAdapter,
        private TracesServiceQueueDispatcher $tracesServiceQueueDispatcher
    ) {
    }

    public function __invoke(TraceCreateRequest $request): void
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

        $this->tracesServiceQueueDispatcher->createMany($parametersList);
    }
}
