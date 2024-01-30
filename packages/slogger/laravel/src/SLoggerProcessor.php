<?php

namespace SLoggerLaravel;

use Closure;
use Illuminate\Support\Carbon;
use LogicException;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\SLoggerTracePushDispatcherParameters;
use SLoggerLaravel\Dispatcher\SLoggerTraceStopDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Throwable;

class SLoggerProcessor
{
    private bool $started = false;

    private array $preParentIdsStack = [];

    public function __construct(
        private readonly SLoggerTraceDispatcherInterface $traceDispatcher,
        private readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    public function handleCallback(
        Closure $callback,
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null,
        ?string $customParentTraceId = null,
    ): void {
        $traceId = $this->startAndGetTraceId(
            type: $type,
            tags: $tags,
            data: $data,
            loggedAt: $loggedAt,
            customParentTraceId: $customParentTraceId,
        );

        try {
            $callback();
        } catch (Throwable $exception) {
            $this->push(
                type: SLoggerTraceTypeEnum::Exception->value,
                data: [
                    'exception' => SLoggerDataFormatter::exception($exception),
                ]
            );
        }

        $this->stop(
            new SLoggerTraceStopDispatcherParameters(
                traceId: $traceId,
                data: $data
            )
        );
    }

    public function startAndGetTraceId(
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null,
        ?string $customParentTraceId = null
    ): string {
        $traceId = SLoggerTraceHelper::make();

        $parentTraceId = $this->traceIdContainer->getParentTraceId();

        $this->traceDispatcher->push(
            new SLoggerTracePushDispatcherParameters(
                traceId: $traceId,
                parentTraceId: $customParentTraceId ?? $parentTraceId,
                type: $type,
                tags: $tags,
                data: $data,
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );

        $this->preParentIdsStack[] = $parentTraceId;

        $this->traceIdContainer->setParentTraceId($traceId);

        $this->started = true;

        return $traceId;
    }

    public function push(
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $traceId = SLoggerTraceHelper::make();

        $parentTraceId = $this->traceIdContainer->getParentTraceId();

        if (!$parentTraceId) {
            throw new LogicException("Parent trace id has not found for $type.");
        }

        $this->traceDispatcher->push(
            new SLoggerTracePushDispatcherParameters(
                traceId: $traceId,
                parentTraceId: $parentTraceId,
                type: $type,
                tags: $tags,
                data: $data,
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );
    }

    public function stop(SLoggerTraceStopDispatcherParameters $parameters): void
    {
        if (!$this->isActive()) {
            throw new LogicException('Tracing process isn\'t active.');
        }

        $currentParentTraceId = $this->traceIdContainer->getParentTraceId();

        if ($parameters->traceId !== $currentParentTraceId) {
            throw new LogicException(
                "Current parent trace id [$currentParentTraceId] isn't same that stopping [$parameters->traceId]."
            );
        }

        $preParentTraceId = array_pop($this->preParentIdsStack);

        $this->traceIdContainer->setParentTraceId(
            $preParentTraceId
        );

        $this->traceDispatcher->stop($parameters);

        if (count($this->preParentIdsStack) == 0) {
            $this->started = false;

            $this->traceIdContainer->setParentTraceId(null);
        }
    }
}
