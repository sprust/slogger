<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Service\Infrastructure\Services\ServiceContainer;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceUpdateRequest;
use App\Modules\Trace\Infrastructure\Http\Services\QueueDispatcher;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingDataObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObjects;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
use SLoggerLaravel\Processor;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class TraceUpdateController
{
    public function __construct(
        private ServiceContainer $serviceContainer,
        private QueueDispatcher $queueDispatcher,
        private Processor $processor
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(TraceUpdateRequest $request): void
    {
        $this->processor->handleWithoutTracing(
            fn() => $this->handle($request)
        );
    }

    private function handle(TraceUpdateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->serviceContainer->getService()?->id;

        abort_if(!$serviceId, Response::HTTP_UNAUTHORIZED);

        $parametersList = new TraceUpdateParametersList();

        foreach ($validated['traces'] as $item) {
            $profiling = null;

            if ($profilingData = $item['profiling'] ?? null) {
                $profiling = new TraceUpdateProfilingObjects($profilingData['main_caller']);

                foreach ($profilingData['items'] ?? [] as $profilingItemData) {
                    $profiling->add(
                        new TraceUpdateProfilingObject(
                            raw: ArrayValueGetter::string($profilingItemData, 'raw'),
                            calling: ArrayValueGetter::string($profilingItemData, 'calling'),
                            callable: ArrayValueGetter::string($profilingItemData, 'callable'),
                            data: array_map(
                                fn(array $profilingItemData) => new TraceUpdateProfilingDataObject(
                                    name: ArrayValueGetter::string($profilingItemData, 'name'),
                                    value: ArrayValueGetter::intFloat($profilingItemData, 'value')
                                ),
                                $profilingItemData['data']
                            )
                        )
                    );
                }
            }

            $parameters = new TraceUpdateParameters(
                serviceId: $serviceId,
                traceId: ArrayValueGetter::string($item, 'trace_id'),
                status: ArrayValueGetter::string($item, 'status'),
                profiling: $profiling,
                tags: ArrayValueGetter::arrayStringNull($item, 'tags'),
                data: ArrayValueGetter::stringNull($item, 'data'),
                duration: ArrayValueGetter::float($item, 'duration'),
                memory: ArrayValueGetter::floatNull($item, 'memory'),
                cpu: ArrayValueGetter::floatNull($item, 'cpu')
            );

            $parametersList->add($parameters);
        }

        $this->queueDispatcher->updateMany($parametersList);
    }
}
