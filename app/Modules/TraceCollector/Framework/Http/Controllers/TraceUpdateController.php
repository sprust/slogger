<?php

namespace App\Modules\TraceCollector\Framework\Http\Controllers;

use App\Modules\Service\Domain\Actions\GetCurrentServiceAction;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateProfilingDataObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateProfilingObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateProfilingObjects;
use App\Modules\TraceCollector\Framework\Http\Requests\TraceUpdateRequest;
use App\Modules\TraceCollector\Framework\Http\Services\QueueDispatcher;
use SLoggerLaravel\SLoggerProcessor;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class TraceUpdateController
{
    public function __construct(
        private GetCurrentServiceAction $getCurrentServiceAction,
        private QueueDispatcher $queueDispatcher,
        private SLoggerProcessor $loggerProcessor
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(TraceUpdateRequest $request): void
    {
        $this->loggerProcessor->handleWithoutTracing(
            fn() => $this->handle($request)
        );
    }

    private function handle(TraceUpdateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->getCurrentServiceAction->handle()?->id;

        abort_if(!$serviceId, Response::HTTP_UNAUTHORIZED);

        $parametersList = new TraceUpdateParametersList();

        foreach ($validated['traces'] as $item) {
            $profiling = null;

            if ($profilingData = $item['profiling'] ?? null) {
                $profiling = new TraceUpdateProfilingObjects($profilingData['main_caller']);

                foreach ($profilingData['items'] ?? [] as $profilingItemData) {
                    $profiling->add(
                        new TraceUpdateProfilingObject(
                            raw: $profilingItemData['raw'],
                            calling: $profilingItemData['calling'],
                            callable: $profilingItemData['callable'],
                            data: array_map(
                                fn(array $profilingItemData) => new TraceUpdateProfilingDataObject(
                                    name: $profilingItemData['name'],
                                    value: $profilingItemData['value']
                                ),
                                $profilingItemData['data']
                            )
                        )
                    );
                }
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

        $this->queueDispatcher->updateMany($parametersList);
    }
}
