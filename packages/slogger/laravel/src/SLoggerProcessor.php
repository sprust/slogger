<?php

namespace SLoggerLaravel;

use Closure;
use Illuminate\Support\Carbon;
use LogicException;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerDataFormatter;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceStopObject;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
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


    /**
     * @throws Throwable
     */
    public function muteHandle(Closure $callback): mixed
    {
        AbstractSLoggerWatcher::toMute();

        $exception = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $result = null;
        }

        AbstractSLoggerWatcher::unMute();

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * @throws Throwable
     */
    public function handleCallback(
        Closure $callback,
        string $type,
        array $tags = [],
        array $data = [],
        ?Carbon $loggedAt = null,
        ?string $customParentTraceId = null,
    ): mixed {
        $traceId = $this->startAndGetTraceId(
            type: $type,
            tags: $tags,
            data: $data,
            loggedAt: $loggedAt,
            customParentTraceId: $customParentTraceId,
        );

        $exception = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $result = null;

            $this->push(
                type: SLoggerTraceTypeEnum::Exception->value,
                data: [
                    'exception' => SLoggerDataFormatter::exception($exception),
                ]
            );
        }

        $this->stop(
            new SLoggerTraceStopObject(
                traceId: $traceId,
                data: $data
            )
        );

        if ($exception) {
            throw $exception;
        }

        return $result;
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
            new SLoggerTraceObject(
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
            new SLoggerTraceObject(
                traceId: $traceId,
                parentTraceId: $parentTraceId,
                type: $type,
                tags: $tags,
                data: $data,
                loggedAt: ($loggedAt ?: now())->clone()->setTimezone('UTC')
            )
        );
    }

    public function stop(SLoggerTraceStopObject $parameters): void
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
