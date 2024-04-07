<?php

namespace App\Modules\TraceCollector\Framework\Http\Controllers;

use App\Modules\TraceCollector\Adapters\Service\ServiceAdapter;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Framework\Http\Requests\TraceCreateRequest;
use App\Modules\TraceCollector\Framework\Http\Services\QueueDispatcher;
use Illuminate\Support\Carbon;

readonly class TraceCreateController
{
    public function __construct(
        private ServiceAdapter $serviceAdapter,
        private QueueDispatcher $tracesServiceQueueDispatcher
    ) {
    }

    public function __invoke(TraceCreateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->serviceAdapter->getService()->id;

        $parametersList = new TraceCreateParametersList();

        foreach ($validated['traces'] as $item) {
            $parametersList->add(
                new TraceCreateParameters(
                    serviceId: $serviceId,
                    traceId: $item['trace_id'],
                    parentTraceId: $item['parent_trace_id'] ?? null,
                    type: $item['type'],
                    status: $item['status'],
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
