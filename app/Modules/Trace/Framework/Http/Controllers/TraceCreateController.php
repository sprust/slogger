<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Service\Infrastructure\Services\ServiceContainer;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Framework\Http\Requests\TraceCreateRequest;
use App\Modules\Trace\Framework\Http\Services\QueueDispatcher;
use Illuminate\Support\Carbon;
use SLoggerLaravel\SLoggerProcessor;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class TraceCreateController
{
    public function __construct(
        private ServiceContainer $serviceContainer,
        private QueueDispatcher $tracesServiceQueueDispatcher,
        private MakeTraceTimestampsActionInterface $makeTraceTimestampsAction,
        private SLoggerProcessor $loggerProcessor
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(TraceCreateRequest $request): void
    {
        $this->loggerProcessor->handleWithoutTracing(
            fn() => $this->handle($request)
        );
    }

    private function handle(TraceCreateRequest $request)
    {
        $validated = $request->validated();

        $serviceId = $this->serviceContainer->getService()?->id;

        abort_if(!$serviceId, Response::HTTP_UNAUTHORIZED);

        $parametersList = new TraceCreateParametersList();

        foreach ($validated['traces'] as $item) {
            $loggedAt = new Carbon($item['logged_at']);

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
                    timestamps: $this->makeTraceTimestampsAction->handle(
                        date: $loggedAt
                    ),
                    loggedAt: $loggedAt,
                )
            );
        }

        $this->tracesServiceQueueDispatcher->createMany($parametersList);
    }
}
