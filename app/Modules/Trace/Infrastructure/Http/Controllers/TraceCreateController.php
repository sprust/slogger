<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Service\Infrastructure\Services\ServiceContainer;
use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceCreateRequest;
use App\Modules\Trace\Infrastructure\Http\Services\QueueDispatcher;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
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

    private function handle(TraceCreateRequest $request): void
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
                    traceId: ArrayValueGetter::string($item, 'trace_id'),
                    parentTraceId: $item['parent_trace_id'] ?? null,
                    type: ArrayValueGetter::string($item, 'type'),
                    status: ArrayValueGetter::string($item, 'status'),
                    tags: ArrayValueGetter::arrayStringNull($item, 'tags') ?? [],
                    data: ArrayValueGetter::string($item, 'data'),
                    duration: ArrayValueGetter::float($item, 'duration'),
                    memory: ArrayValueGetter::floatNull($item, 'memory'),
                    cpu: ArrayValueGetter::floatNull($item, 'cpu'),
                    timestamps: $this->makeTraceTimestampsAction->handle(date: $loggedAt),
                    loggedAt: $loggedAt,
                )
            );
        }

        $this->tracesServiceQueueDispatcher->createMany($parametersList);
    }
}
