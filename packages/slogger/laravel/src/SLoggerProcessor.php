<?php

namespace SLoggerLaravel;

use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\TraceDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Exceptions\TraceProcessingAlreadyStartedException;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;

class SLoggerProcessor
{
    private bool $started = false;

    public function __construct(
        private readonly TraceDispatcherInterface $traceDispatcher,
        private readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    /**
     * @throws TraceProcessingAlreadyStartedException
     */
    public function start(?string $parentTraceId): void
    {
        if ($this->isActive()) {
            throw new TraceProcessingAlreadyStartedException();
        }

        $traceId = TraceIdHelper::make();

        $this->traceDispatcher->put(
            new TraceDispatcherParameters(
                traceId: $traceId,
                parentTraceId: $parentTraceId,
                type: SLoggerTraceTypeEnum::Start,
                tags: [],
                data: [],
                loggedAt: now()
            )
        );

        $this->traceIdContainer->setParentTraceId($traceId);

        $this->started = true;
    }

    public function stop(): void
    {
        $this->traceDispatcher->stop();

        $this->started = false;
    }
}
