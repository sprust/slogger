<?php

namespace App\Modules\Traces\Http\Controllers;

use App\Modules\Services\Adapters\ServicesHttpAdapter;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParameters;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\Traces\Http\Requests\TraceUpdateRequest;
use App\Modules\Traces\Services\TracesServiceQueueDispatcher;

readonly class TraceUpdateController
{
    public function __construct(
        private ServicesHttpAdapter $servicesHttpAdapter,
        private TracesServiceQueueDispatcher $tracesServiceQueueDispatcher
    ) {
    }

    public function __invoke(TraceUpdateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->servicesHttpAdapter->getService()->id;

        $parametersList = new TraceUpdateParametersList();

        foreach ($validated['traces'] as $item) {
            $parameters = new TraceUpdateParameters(
                serviceId: $serviceId,
                traceId: $item['trace_id'],
                tags: $item['tags'] ?? null,
                data: $item['data'] ?? null
            );

            if (!$parameters->tags && !$parameters->data) {
                continue;
            }

            $parametersList->add($parameters);
        }

        if ($parametersList->count()) {
            $this->tracesServiceQueueDispatcher->updateMany($parametersList);
        }
    }
}
