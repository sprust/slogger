<?php

namespace App\Services\SLogger;

use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Helpers\DataFormatter;
use SLoggerLaravel\Helpers\TraceHelper;
use SLoggerLaravel\Traces\TraceIdContainer;
use SLoggerLaravel\Watchers\AbstractWatcher;
use SParallel\Objects\Context;
use SParallelLaravel\Events\SParallelFlowFinishedEvent;
use SParallelLaravel\Events\SParallelFlowStartingEvent;
use SParallelLaravel\Events\SParallelTaskFailedEvent;
use SParallelLaravel\Events\SParallelTaskFinishedEvent;
use SParallelLaravel\Events\SParallelTaskStartingEvent;

class SParallelJobWatcher extends AbstractWatcher
{
    /** @var array<string, array<string, mixed>> */
    private array $tasks = [];

    private string $traceType = 'sparallel';

    private string $parentTraceIdContextKey = 'slogger-task-parent-trace-id';
    private string $flowParentTraceIdContextKey = 'slogger-flow-parent-trace-id';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(): void
    {
        $this->app->get(Context::class)
            ->add(
                key: $this->parentTraceIdContextKey,
                value: static fn() => app(TraceIdContainer::class)->getParentTraceId()
            );

        $this->listenEvent(SParallelFlowStartingEvent::class, [$this, 'handleSParallelFlowStartingEvent']);
        $this->listenEvent(SParallelFlowFinishedEvent::class, [$this, 'handleSParallelFlowFinishedEvent']);

        $this->listenEvent(SParallelTaskStartingEvent::class, [$this, 'handleSParallelTaskStartingEvent']);
        $this->listenEvent(SParallelTaskFailedEvent::class, [$this, 'handleSParallelTaskFailedEvent']);
        $this->listenEvent(SParallelTaskFinishedEvent::class, [$this, 'handleSParallelTaskFinishedEvent']);
    }

    public function handleSParallelFlowStartingEvent(SParallelFlowStartingEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleSParallelFlowStartingEvent($event));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function onHandleSParallelFlowStartingEvent(SParallelFlowStartingEvent $event): void
    {
        $this->app->get(Context::class)
            ->add(
                key: $this->flowParentTraceIdContextKey,
                value: app(TraceIdContainer::class)->getParentTraceId()
            );
    }

    public function handleSParallelFlowFinishedEvent(SParallelFlowFinishedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleSParallelFlowFinishedEvent($event));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function onHandleSParallelFlowFinishedEvent(SParallelFlowFinishedEvent $event): void
    {
        $this->app->get(Context::class)->delete(key: $this->flowParentTraceIdContextKey);
    }

    public function handleSParallelTaskStartingEvent(SParallelTaskStartingEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleSParallelTaskStartingEvent($event));
    }

    protected function onHandleSParallelTaskStartingEvent(SParallelTaskStartingEvent $event): void
    {
        if ($event->context?->has($this->flowParentTraceIdContextKey)) {
            $parentTraceId = $event->context->get($this->flowParentTraceIdContextKey);

            $event->context->delete($this->flowParentTraceIdContextKey);
        } else {
            $parentTraceId = $event->context?->get($this->parentTraceIdContextKey);
        }

        /** @var string|null $parentTraceId */

        $context = is_null($event->context)
            ? null
            : array_diff_key(
                $event->context->all(),
                array_flip([
                    $this->parentTraceIdContextKey,
                    $this->flowParentTraceIdContextKey,
                ])
            );

        $traceId = $this->processor->startAndGetTraceId(
            type: $this->traceType,
            tags: [
                $event->driverName,
            ],
            data: [
                'driver'  => $event->driverName,
                'context' => $context,
            ],
            customParentTraceId: $parentTraceId
        );

        $this->tasks[$traceId] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
            'context'    => $context,
        ];
    }

    public function handleSParallelTaskFailedEvent(SParallelTaskFailedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleSParallelTaskFailedEvent($event));
    }

    protected function onHandleSParallelTaskFailedEvent(SParallelTaskFailedEvent $event): void
    {
        if (!count($this->tasks)) {
            throw new RuntimeException(
                "No tasks found to handle failure for $event->driverName.\n"
                . "Context:\n" . json_encode($event->context ?? []) . "\n"
                . "Got exception: $event->exception"
            );
        }

        $traceId = array_key_last($this->tasks);

        $this->tasks[$traceId]['exception'] = DataFormatter::exception($event->exception);
    }

    public function handleSParallelTaskFinishedEvent(SParallelTaskFinishedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleSParallelTaskFinishedEvent($event));
    }

    protected function onHandleSParallelTaskFinishedEvent(SParallelTaskFinishedEvent $event): void
    {
        if (!count($this->tasks)) {
            throw new RuntimeException(
                "No tasks found to handle failure for $event->driverName.\n"
                . "Context:\n" . json_encode($event->context ?? [])
            );
        }

        $traceId = array_key_last($this->tasks);

        $taskData = $this->tasks[$traceId];

        /** @var Carbon $startedAt */
        $startedAt = $taskData['started_at'];


        if (!array_key_exists('exception', $taskData)) {
            $data = null;

            $status = TraceStatusEnum::Success->value;
        } else {
            $data = [
                'driver'    => $event->driverName,
                'context'   => $taskData['context'],
                'exception' => $taskData['exception'],
            ];

            $status = TraceStatusEnum::Failed->value;
        }

        $this->processor->stop(
            traceId: $traceId,
            status: $status,
            data: $data,
            duration: TraceHelper::calcDuration($startedAt)
        );

        unset($this->tasks[$traceId]);
    }
}
