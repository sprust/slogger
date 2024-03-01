<?php

namespace App\Modules\TracesCollector\Http\Controllers;

use App\Modules\TracesCollector\Adapters\TraceServicesHttpAdapter;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParameters;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateProfilingDataObject;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateProfilingObject;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateProfilingObjects;
use App\Modules\TracesCollector\Http\Requests\TraceUpdateRequest;
use App\Modules\TracesCollector\Services\TracesServiceQueueDispatcher;

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
                status: $item['status'],
                profiling: $profiling,
                tags: $item['tags'] ?? null,
                data: $item['data'] ?? null,
                duration: $item['duration'],
                memory: $item['memory'] ?? null,
                cpu: $item['cpu'] ?? null
            );

            $parametersList->add($parameters);
        }

        if ($parametersList->count()) {
            $this->tracesServiceQueueDispatcher->updateMany($parametersList);
        }
    }
}
