<?php

namespace SLoggerLaravel\Dispatcher;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use SLoggerLaravel\HttpClient\SLoggerHttpClient;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceStopObject;

class SLoggerTraceDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    public function __construct(
        protected readonly Application $app,
        protected readonly SLoggerHttpClient $httpClient,
    ) {
    }

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;
    }

    public function stop(SLoggerTraceStopObject $parameters): void
    {
        if (!$this->traces) {
            return;
        }

        $filtered = array_filter(
            $this->traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->parentTraceId === $parameters->traceId
                || $traceItem->traceId === $parameters->traceId
        );

        $traces = Arr::sort(
            $filtered,
            fn(SLoggerTraceObject $traceItem) => $traceItem->loggedAt->getTimestampMs()
        );

        /** @var SLoggerTraceObject $parentTrace */
        $parentTrace = Arr::first(
            $traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->traceId === $parameters->traceId
        );

        if (!is_null($parameters->data)) {
            $parentTrace->data = $parameters->data;
        }


        if (!is_null($parameters->tags)) {
            $parentTrace->tags = $parameters->tags;
        }

        $this->sendTraces($parentTrace, $traces);

        $this->traces = array_filter(
            $this->traces,
            fn(SLoggerTraceObject $traceItem) => $traceItem->parentTraceId !== $parameters
        );
    }

    /**
     * @param SLoggerTraceObject[] $traces
     *
     * @throws GuzzleException
     */
    protected function sendTraces(SLoggerTraceObject $parentTrace, array $traces): void
    {
        $traceObjects = new SLoggerTraceObjects();

        foreach ($traces as $trace) {
            $traceObjects->add($trace);
        }

        $this->httpClient->sendTraces($traceObjects);
    }
}
