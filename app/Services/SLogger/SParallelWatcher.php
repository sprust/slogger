<?php

namespace App\Services\SLogger;

use Illuminate\Support\Carbon;
use RuntimeException;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Helpers\DataFormatter;
use SLoggerLaravel\Helpers\TraceHelper;
use SLoggerLaravel\Watchers\AbstractWatcher;
use SParallelLaravel\Events\FlowFinishedEvent;
use SParallelLaravel\Events\FlowStartingEvent;
use SParallelLaravel\Events\TaskFailedEvent;
use SParallelLaravel\Events\TaskFinishedEvent;
use SParallelLaravel\Events\TaskStartingEvent;

class SParallelWatcher extends AbstractWatcher
{
    /** @var array<string, array<string, mixed>> */
    private array $flows = [];

    /** @var array<string, array<string, mixed>> */
    private array $tasks = [];

    private string $traceType = 'sparallel';

    private string $parentTraceIdContextKey = 'slogger-task-parent-trace-id';
    private string $flowParentTraceIdContextKey = 'slogger-flow-parent-trace-id';

    public function register(): void
    {
        $this->listenEvent(FlowStartingEvent::class, [$this, 'handleFlowStartingEvent']);
        $this->listenEvent(FlowFinishedEvent::class, [$this, 'handleFlowFinishedEvent']);

        $this->listenEvent(TaskStartingEvent::class, [$this, 'handleTaskStartingEvent']);
        $this->listenEvent(TaskFailedEvent::class, [$this, 'handleTaskFailedEvent']);
        $this->listenEvent(TaskFinishedEvent::class, [$this, 'handleTaskFinishedEvent']);
    }

    public function handleFlowStartingEvent(FlowStartingEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleFlowStartingEvent($event));
    }

    protected function onHandleFlowStartingEvent(FlowStartingEvent $event): void
    {
        $traceId = $this->processor->startAndGetTraceId(
            type: $this->traceType,
            tags: [
                '#flow',
            ],
            data: [
                'context' => $event->context->getValues(),
            ],
        );

        $event->context->addValue(
            key: $this->flowParentTraceIdContextKey,
            value: $traceId
        );

        $this->flows[$traceId] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleFlowFinishedEvent(FlowFinishedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleFlowFinishedEvent($event));
    }

    protected function onHandleFlowFinishedEvent(FlowFinishedEvent $event): void
    {
        $traceId = array_key_last($this->flows);

        $flowData = $this->flows[$traceId];

        /** @var Carbon $startedAt */
        $startedAt = $flowData['started_at'];

        $this->processor->stop(
            traceId: $traceId,
            status: TraceStatusEnum::Success->value,
            duration: TraceHelper::calcDuration($startedAt)
        );

        unset($this->flows[$traceId]);

        $event->context->deleteValue(
            key: $this->flowParentTraceIdContextKey,
        );
    }

    public function handleTaskStartingEvent(TaskStartingEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleTaskStartingEvent($event));
    }

    protected function onHandleTaskStartingEvent(TaskStartingEvent $event): void
    {
        $parentTraceId = $event->context->getValue($this->flowParentTraceIdContextKey);

        if (is_null($parentTraceId)) {
            $parentTraceId = $event->context->getValue($this->parentTraceIdContextKey);
        }

        /** @var string|null $parentTraceId */

        $context = array_diff_key(
            $event->context->getValues(),
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

    public function handleTaskFailedEvent(TaskFailedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleTaskFailedEvent($event));
    }

    protected function onHandleTaskFailedEvent(TaskFailedEvent $event): void
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

    public function handleTaskFinishedEvent(TaskFinishedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleTaskFinishedEvent($event));
    }

    protected function onHandleTaskFinishedEvent(TaskFinishedEvent $event): void
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
