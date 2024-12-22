<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Grpc;

use App\Modules\Service\Contracts\Actions\FindServiceByTokenActionInterface;
use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Infrastructure\Http\Services\QueueDispatcher;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingDataObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObject;
use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObjects;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
use Illuminate\Support\Carbon;
use SLoggerGrpcDto\TraceCollector\TraceCollectorInterface;
use SLoggerGrpcDto\TraceCollector\TraceCollectorResponse;
use SLoggerGrpcDto\TraceCollector\TraceCreateObject;
use SLoggerGrpcDto\TraceCollector\TraceCreateRequest;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemDataItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemsObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateRequest;
use Spiral\RoadRunner\GRPC;
use Throwable;

readonly class TraceCollectorService implements TraceCollectorInterface
{
    public function __construct(
        private FindServiceByTokenActionInterface $findServiceByTokenAction,
        private MakeTraceTimestampsActionInterface $makeTraceTimestampsAction,
        private QueueDispatcher $tracesServiceQueueDispatcher,
    ) {
    }

    public function Create(GRPC\ContextInterface $ctx, TraceCreateRequest $in): TraceCollectorResponse
    {
        $serviceId = $this->detectServiceIdByCtx($ctx);

        if (!$serviceId) {
            return new TraceCollectorResponse([
                'status_code' => 401,
                'message'     => 'Service not found',
            ]);
        }

        try {
            $this->onCreate(
                serviceId: $serviceId,
                in: $in
            );
        } catch (Throwable $exception) {
            report($exception);

            return new TraceCollectorResponse([
                'status_code' => 500,
                'message'     => $exception->getMessage(),
            ]);
        }

        return new TraceCollectorResponse([
            'status_code' => 200,
            'message'     => 'Ok',
        ]);
    }

    public function Update(GRPC\ContextInterface $ctx, TraceUpdateRequest $in): TraceCollectorResponse
    {
        $serviceId = $this->detectServiceIdByCtx($ctx);

        if (!$serviceId) {
            return new TraceCollectorResponse([
                'status_code' => 401,
                'message'     => 'Service not found',
            ]);
        }

        try {
            $this->onUpdate(
                serviceId: $serviceId,
                in: $in
            );
        } catch (Throwable $exception) {
            report($exception);

            return new TraceCollectorResponse([
                'status_code' => 500,
                'message'     => $exception->getMessage(),
            ]);
        }

        return new TraceCollectorResponse([
            'status_code' => 200,
            'message'     => 'Ok',
        ]);
    }

    private function detectServiceIdByCtx(GRPC\ContextInterface $ctx): ?int
    {
        $headers = $ctx->getValue('authorization');

        if (!is_array($headers)) {
            return null;
        }

        $header = $headers[0] ?? null;

        if (!$header) {
            return null;
        }

        $position = strrpos($header, 'Bearer ');

        if ($position === false) {
            return null;
        }

        $slicedHeader = substr($header, $position + 7);

        $bearer = str_contains($slicedHeader, ',') ? strstr($slicedHeader, ',', true) : $slicedHeader;

        return $this->findServiceByTokenAction->handle($bearer)?->id;
    }

    private function onCreate(int $serviceId, TraceCreateRequest $in): void
    {
        $parameters = new TraceCreateParametersList();

        $traces = $in->getTraces();

        for ($index = 0; $index < $traces->count(); $index++) {
            /** @var TraceCreateObject $trace */
            $trace = $traces[$index];

            $loggedAt = new Carbon($trace->getLoggedAt()->toDateTime());

            $traceParameters = new TraceCreateParameters(
                serviceId: $serviceId,
                traceId: $trace->getTraceId(),
                parentTraceId: $trace->getParentTraceId()?->getValue() ?: null,
                type: $trace->getType(),
                status: $trace->getStatus(),
                tags: collect($trace->getTags())->toArray(),
                data: $trace->getData(),
                duration: $trace->getDuration()?->getValue(),
                memory: $trace->getMemory()?->getValue(),
                cpu: $trace->getCpu()?->getValue(),
                timestamps: $this->makeTraceTimestampsAction->handle(
                    date: $loggedAt
                ),
                loggedAt: $loggedAt,
            );

            $parameters->add($traceParameters);
        }

        $this->tracesServiceQueueDispatcher->createMany($parameters);
    }

    private function onUpdate(int $serviceId, TraceUpdateRequest $in): void
    {
        $parameters = new TraceUpdateParametersList();

        $traces = $in->getTraces();

        for ($index = 0; $index < $traces->count(); $index++) {
            /** @var TraceUpdateObject $trace */
            $trace = $traces[$index];

            $tagsValue = $trace->getTags()?->getItems();

            /**
             * @phpstan-ignore-next-line
             * Call to function is_null() with Google\Protobuf\Internal\RepeatedField will always evaluate to false.
             * 💡 Because the type is coming from a PHPDoc, you can turn off this
             *      check by setting treatPhpDocTypesAsCertain: false in your phpstan.neon.
             */
            if (is_null($tagsValue)) {
                $tags = null;
            } else {
                $tags = [];

                for ($tagsIndex = 0; $tagsIndex < $tagsValue->count(); $tagsIndex++) {
                    $tags[] = $tagsValue[$tagsIndex];
                }
            }

            $traceParameters = new TraceUpdateParameters(
                serviceId: $serviceId,
                traceId: $trace->getTraceId(),
                status: $trace->getStatus(),
                profiling: $this->makeProfiling(
                    $trace->getProfiling()
                ),
                tags: $tags,
                data: $trace->getData()?->getValue(),
                duration: $trace->getDuration()?->getValue(),
                memory: $trace->getMemory()?->getValue(),
                cpu: $trace->getCpu()?->getValue(),
            );

            $parameters->add($traceParameters);
        }

        $this->tracesServiceQueueDispatcher->updateMany($parameters);
    }

    private function makeProfiling(?TraceProfilingItemsObject $object): ?TraceUpdateProfilingObjects
    {
        if (!$object?->getMainCaller()) {
            return null;
        }

        $result = new TraceUpdateProfilingObjects(
            mainCaller: $object->getMainCaller()
        );

        $items = $object->getItems();

        for ($index = 0; $index < $items->count(); $index++) {
            /** @var TraceProfilingItemObject $item */
            $item = $items[$index];

            $result->add(
                new TraceUpdateProfilingObject(
                    raw: $item->getRaw(),
                    calling: $item->getCalling(),
                    callable: $item->getCallable(),
                    data: $this->makeProfilingItemData($item)
                )
            );
        }

        return $result;
    }

    /**
     * @return TraceUpdateProfilingDataObject[]
     */
    private function makeProfilingItemData(TraceProfilingItemObject $item): array
    {
        $result = [];

        $dataList = $item->getData();

        for ($index = 0; $index < $dataList->count(); $index++) {
            /** @var TraceProfilingItemDataItemObject $data */
            $data = $dataList[$index];

            $value = $data->getValue();

            $result[] = new TraceUpdateProfilingDataObject(
                name: $data->getName(),
                /**
                 * @phpstan-ignore-next-line
                 * Expression on left side of ?? is not nullable.
                 */
                value: $value->getInt()?->getValue() ?? $value->getDouble()?->getValue() ?? -1
            );
        }

        return $result;
    }
}
