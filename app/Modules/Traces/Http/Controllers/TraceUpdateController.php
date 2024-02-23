<?php

namespace App\Modules\Traces\Http\Controllers;

use App\Modules\Traces\Adapters\TraceServicesHttpAdapter;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParameters;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\Traces\Dto\Parameters\TraceUpdateProfilingDataObject;
use App\Modules\Traces\Dto\Parameters\TraceUpdateProfilingObject;
use App\Modules\Traces\Dto\Parameters\TraceUpdateProfilingObjects;
use App\Modules\Traces\Http\Requests\TraceUpdateRequest;
use App\Modules\Traces\Services\TracesServiceQueueDispatcher;

readonly class TraceUpdateController
{
    public function __construct(
        private TraceServicesHttpAdapter $servicesHttpAdapter,
        private TracesServiceQueueDispatcher $tracesServiceQueueDispatcher
    ) {
    }

    public function __invoke(TraceUpdateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->servicesHttpAdapter->getService()->id;

        $parametersList = new TraceUpdateParametersList();

        foreach ($validated['traces'] as $item) {
            $profiling = new TraceUpdateProfilingObjects();

            foreach ($item['profiling'] ?? [] as $profilingItem) {
                $profilingData = $profilingItem['data'];

                $profiling->add(
                    new TraceUpdateProfilingObject(
                        raw: $profilingItem['raw'],
                        calling: $profilingItem['calling'],
                        callable: $profilingItem['callable'],
                        data: new TraceUpdateProfilingDataObject(
                            numberOfCalls: $profilingData['number_of_calls'],
                            waitTimeInMs: $profilingData['wait_time_in_ms'],
                            cpuTime: $profilingData['cpu_time'],
                            memoryUsageInBytes: $profilingData['memory_usage_in_bytes'],
                            peakMemoryUsageInMb: $profilingData['peak_memory_usage_in_mb'],
                        )
                    )
                );
            }

            $parameters = new TraceUpdateParameters(
                serviceId: $serviceId,
                traceId: $item['trace_id'],
                profiling: $profiling,
                tags: $item['tags'] ?? null,
                data: $item['data'] ?? null,
                duration: $item['duration'],
                memory: $item['memory'] ?? null,
                cpu: $item['cpu'] ?? null
            );

            if (!$parameters->tags
                && !$parameters->data
                && !count($profiling->getItems())
            ) {
                continue;
            }

            $parametersList->add($parameters);
        }

        if ($parametersList->count()) {
            $this->tracesServiceQueueDispatcher->updateMany($parametersList);
        }
    }
}
