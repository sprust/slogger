<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SLoggerLaravel\Dispatcher\SLoggerTraceStopDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerJobWatcher extends AbstractSLoggerWatcher
{
    private array $jobs = [];

    public function register(): void
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return [
                'slogger_uuid'            => Str::uuid()->toString(),
                'slogger_parent_trace_id' => $this->traceIdContainer->getParentTraceId(),
            ];
        });

        $this->listenEvent(JobProcessing::class, [$this, 'handleJobProcessing']);
        $this->listenEvent(JobProcessed::class, [$this, 'handleJobProcessed']);
        $this->listenEvent(JobFailed::class, [$this, 'handleJobFailed']);
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        $payload = $event->job->payload();

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $parentTraceId = $payload['slogger_parent_trace_id'] ?? null;

        if (!$parentTraceId) {
            return;
        }

        if (!$this->processor->isActive()) {
            $this->traceIdContainer->setParentTraceId($parentTraceId);
        }

        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Job,
            customParentTraceId: $parentTraceId
        );

        $this->jobs[$uuid] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $jobData = $this->jobs[$uuid] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $data = [
            'connectionName' => $event->connectionName,
            'payload'        => $event->job->payload(),
            'duration'       => SLoggerTraceHelper::calcDuration($startedAt),
            'status'         => 'processed',
        ];

        $this->processor->stop(
            new SLoggerTraceStopDispatcherParameters(
                traceId: $traceId,
                tags: [],
                data: $data,
            )
        );

        unset($this->jobs[$uuid]);
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();

        $uuid = $payload['slogger_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $jobData = $this->jobs[$uuid] ?? null;

        if (!$jobData) {
            return;
        }

        $traceId = $jobData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $jobData['started_at'];

        $exception = $event->exception;

        $data = [
            'connectionName' => $event->connectionName,
            'payload'        => $event->job->payload(),
            'duration'       => SLoggerTraceHelper::calcDuration($startedAt),
            'status'         => 'failed',
            'exception'      => [
                'message'   => $exception->getMessage(),
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => collect($exception->getTrace())->map(
                    fn($trace) => Arr::except($trace, ['args'])
                )->all(),
            ],
        ];

        $this->processor->stop(
            new SLoggerTraceStopDispatcherParameters(
                traceId: $traceId,
                tags: [],
                data: $data,
            )
        );

        unset($this->jobs[$uuid]);
    }
}
