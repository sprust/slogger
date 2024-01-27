<?php

namespace SLoggerLaravel;

use Illuminate\Support\Carbon;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\TraceDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Exceptions\TraceProcessingNotActiveException;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;

class SLoggerProcessor
{
    private bool $started = false;

    private array $preParentIdsStack = [];

    public function __construct(
        private readonly TraceDispatcherInterface $traceDispatcher,
        private readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    public function start(string $name, ?Carbon $loggedAt = null): void
    {
        $traceId = TraceIdHelper::make();

        $parentTraceId = $this->traceIdContainer->getParentTraceId();

        $this->traceDispatcher->put(
            new TraceDispatcherParameters(
                traceId: $traceId,
                parentTraceId: $parentTraceId,
                type: SLoggerTraceTypeEnum::Start,
                tags: [$name],
                data: [],
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );

        $this->preParentIdsStack[] = $parentTraceId;

        $this->traceIdContainer->setParentTraceId($traceId);

        $this->started = true;
    }

    public function stop(): void
    {
        if (!$this->isActive()) {
            // TODO: fire an event
            report(new TraceProcessingNotActiveException());

            return;
        }

        $preParentTraceId = array_pop($this->preParentIdsStack);

        $this->traceIdContainer->setParentTraceId(
            $preParentTraceId
        );

        if (count($this->preParentIdsStack) == 0) {
            $this->started = false;

            $this->traceIdContainer->setParentTraceId(null);

            $this->traceDispatcher->stop();
        }
    }
}
