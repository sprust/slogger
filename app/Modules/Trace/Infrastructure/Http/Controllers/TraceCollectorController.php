<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Service\Infrastructure\Services\ServiceContainer;
use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceCreateRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceUpdateRequest;
use App\Modules\Trace\Infrastructure\Http\Services\QueueDispatcher;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingDataObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObjects;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Configs\GeneralConfig;
use SLoggerLaravel\Processor;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TraceCollectorController
{
    private readonly bool $sloggerEnabled;

    private ?Processor $processor = null;

    public function __construct(
        GeneralConfig $config,
        private readonly ServiceContainer $serviceContainer,
        private readonly QueueDispatcher $queueDispatcher,
        private readonly MakeTraceTimestampsActionInterface $makeTraceTimestampsAction,
    ) {
        $this->sloggerEnabled = $config->isEnabled();
    }

    /**
     * @throws Throwable
     */
    public function create(TraceCreateRequest $request): void
    {
        if ($this->sloggerEnabled) {
            $this->getProcessor()->handleWithoutTracing(
                fn() => $this->onCreate($request)
            );
        } else {
            $this->onCreate($request);
        }
    }

    private function onCreate(TraceCreateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->serviceContainer->getService()?->id;

        abort_if(!$serviceId, Response::HTTP_UNAUTHORIZED);

        $parametersList = new TraceCreateParametersList();

        foreach ($validated['traces'] ?? [] as $item) {
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
                    // TODO: required after release
                    isParent: ArrayValueGetter::boolNull($item, 'is_parent') ?? false,
                    loggedAt: $loggedAt,
                )
            );
        }

        if ($parametersList->count() === 0) {
            return;
        }

        $this->queueDispatcher->createMany($parametersList);
    }

    /**
     * @throws Throwable
     */
    public function update(TraceUpdateRequest $request): void
    {
        if ($this->sloggerEnabled) {
            $this->getProcessor()->handleWithoutTracing(
                fn() => $this->onUpdate($request)
            );
        } else {
            $this->onUpdate($request);
        }
    }

    private function onUpdate(TraceUpdateRequest $request): void
    {
        $validated = $request->validated();

        $serviceId = $this->serviceContainer->getService()?->id;

        abort_if(!$serviceId, Response::HTTP_UNAUTHORIZED);

        $parametersList = new TraceUpdateParametersList();

        foreach ($validated['traces'] ?? [] as $item) {
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

        if ($parametersList->count() === 0) {
            return;
        }

        $this->queueDispatcher->updateMany($parametersList);
    }

    private function getProcessor(): Processor
    {
        return $this->processor ??= app(Processor::class);
    }
}
