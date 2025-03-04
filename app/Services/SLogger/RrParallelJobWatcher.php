<?php

namespace App\Services\SLogger;

use Illuminate\Support\Carbon;
use RrMonitor\Events\MonitorWorkersCountSetEvent;
use RrParallel\Events\JobHandledEvent;
use RrParallel\Events\JobHandlingErrorEvent;
use RrParallel\Events\JobReceivedEvent;
use RrParallel\Services\Drivers\Roadrunner\HeadersResolver;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Helpers\TraceHelper;
use SLoggerLaravel\Traces\TraceIdContainer;
use SLoggerLaravel\Watchers\AbstractWatcher;
use Throwable;

class RrParallelJobWatcher extends AbstractWatcher
{
    /** @var array<string, array<string, mixed>> */
    private array $jobs = [];

    private string $jobType = 'rr-parallel-job';
    private string $monitorType = 'rr-parallel-monitor';

    private string $parentTraceIdHeaderName = 'x-parent-trace-id';

    public function register(): void
    {
        $this->app->singleton(
            HeadersResolver::class,
            function () {
                return (new HeadersResolver())
                    ->add(
                        name: $this->parentTraceIdHeaderName,
                        header: fn(TraceIdContainer $traceIdContainer) => $traceIdContainer->getParentTraceId()
                    );
            }
        );

        // jobs
        $this->listenEvent(JobReceivedEvent::class, [$this, 'handleJobReceivedEvent']);
        $this->listenEvent(JobHandledEvent::class, [$this, 'handleJobHandledEvent']);
        $this->listenEvent(JobHandlingErrorEvent::class, [$this, 'handleJobHandlingErrorEvent']);

        // monitor
        $this->listenEvent(MonitorWorkersCountSetEvent::class, [$this, 'handleMonitorWorkersAddedEvent']);
    }

    public function handleJobReceivedEvent(JobReceivedEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobReceivedEvent($event));
    }

    protected function onHandleJobReceivedEvent(JobReceivedEvent $event): void
    {
        /** @var string|null $parentTraceId */
        $parentTraceId = $event->task->getHeader($this->parentTraceIdHeaderName)[0] ?? null;

        $traceId = $this->processor->startAndGetTraceId(
            type: $this->jobType,
            data: [
                'payload' => $event->task->getPayload(),
            ],
            customParentTraceId: $parentTraceId
        );

        $this->jobs[$event->task->getId()] = [
            'trace_id' => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleJobHandledEvent(JobHandledEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobHandledEvent($event));
    }

    protected function onHandleJobHandledEvent(JobHandledEvent $event): void
    {
        $taskId = $event->task->getId();

        $jobData = $this->jobs[$taskId] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $this->processor->stop(
            traceId: $traceId,
            status: TraceStatusEnum::Success->value,
            duration: TraceHelper::calcDuration($startedAt)
        );

        unset($this->jobs[$taskId]);
    }

    public function handleJobHandlingErrorEvent(JobHandlingErrorEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleJobHandlingErrorEvent($event));
    }

    protected function onHandleJobHandlingErrorEvent(JobHandlingErrorEvent $event): void
    {
        $taskId = $event->task->getId();

        $jobData = $this->jobs[$taskId] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $this->processor->stop(
            traceId: $traceId,
            status: TraceStatusEnum::Failed->value,
            duration: TraceHelper::calcDuration($startedAt)
        );

        unset($this->jobs[$taskId]);
    }

    public function handleMonitorWorkersAddedEvent(MonitorWorkersCountSetEvent $event): void
    {
        $this->safeHandleWatching(fn() => $this->onHandleMonitorWorkersAddedEvent($event));
    }

    /**
     * @throws Throwable
     */
    protected function onHandleMonitorWorkersAddedEvent(MonitorWorkersCountSetEvent $event): void
    {
        $this->processor->push(
            type: $this->monitorType,
            status: TraceStatusEnum::Success->value,
            tags: [
                $event->pluginName,
                $event->operationName,
            ],
            data: (array) $event,
            canBeOrphan: true,
        );
    }
}
