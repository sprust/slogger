<?php

declare(strict_types=1);

namespace App\Services\SLogger;

use Illuminate\Support\Carbon;
use SConcur\Laravel\Tasks\Events\TaskTickFinished;
use SConcur\Laravel\Tasks\Events\TaskTickStarted;
use SConcur\Laravel\Tasks\TickResultEnum;
use SLoggerLaravel\Context\TraceContextInterface;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Helpers\TraceHelper;
use SLoggerLaravel\Processor;
use SLoggerLaravel\Watchers\WatcherInterface;

/**
 * @phpstan-type OpenTick array{trace_id: string, started_at: Carbon}
 */
class TaskWatcher implements WatcherInterface
{
    protected const string TRACE_TYPE = 'task';

    protected const string CONTEXT_KEY_TICKS = 'slogger.watcher.task.open';

    /**
     * @var string[]
     */
    protected array $exceptedTasks = [];

    public function __construct(
        protected readonly Processor $processor,
        protected readonly TraceContextInterface $context
    ) {
    }

    public function register(?array $config): void
    {
        $this->processor->onTraceInterrupted(
            function (string $traceId): void {
                $this->setOpenTicks(
                    array_filter(
                        $this->getOpenTicks(),
                        static fn(array $tick): bool => $tick['trace_id'] !== $traceId
                    )
                );
            }
        );

        if ($config !== null) {
            /** @var string[] $excepted */
            $excepted = $config['excepted'] ?? [];

            $this->exceptedTasks = $excepted;
        }

        $this->processor->registerEvent(TaskTickStarted::class, [$this, 'handleTickStarted']);
        $this->processor->registerEvent(TaskTickFinished::class, [$this, 'handleTickFinished']);
    }

    public function handleTickStarted(TaskTickStarted $event): void
    {
        if ($this->isExcepted($event->name)) {
            return;
        }

        $loggedAt = Carbon::now();

        $traceId = $this->processor->startAndGetTraceId(
            type: static::TRACE_TYPE,
            tags: [$event->name],
            data: [
                'task' => $event->name,
            ],
            loggedAt: $loggedAt,
            customParentTraceId: null
        );

        $ticks = $this->getOpenTicks();

        $ticks[$event->name] = [
            'trace_id'   => $traceId,
            'started_at' => $loggedAt,
        ];

        $this->setOpenTicks($ticks);
    }

    public function handleTickFinished(TaskTickFinished $event): void
    {
        if ($this->isExcepted($event->name)) {
            return;
        }

        $tick = $this->takeTick($event->name);

        if (is_null($tick)) {
            return;
        }

        $startedAt = $tick['started_at'];

        $data = [
            'task'   => $event->name,
            'result' => $this->makeResultView($event->result),
        ];

        if (!is_null($event->exception)) {
            $data['exception'] = [
                'class'   => $event->exception::class,
                'message' => $event->exception->getMessage(),
                'file'    => $event->exception->getFile(),
                'line'    => $event->exception->getLine(),
            ];
        }

        $this->processor->stop(
            traceId: $tick['trace_id'],
            status: is_null($event->exception)
                ? TraceStatusEnum::Success->value
                : TraceStatusEnum::Failed->value,
            tags: null,
            data: $data,
            duration: TraceHelper::calcDuration($startedAt),
            parentLoggedAt: $startedAt
        );
    }

    protected function isExcepted(string $name): bool
    {
        return in_array($name, $this->exceptedTasks, strict: true);
    }

    protected function makeResultView(TickResultEnum $result): string
    {
        return match ($result) {
            TickResultEnum::Worked => 'worked',
            TickResultEnum::Idle   => 'idle',
            TickResultEnum::Failed => 'failed',
        };
    }

    /**
     * @return OpenTick|null
     */
    protected function takeTick(string $name): ?array
    {
        $ticks = $this->getOpenTicks();

        $tick = $ticks[$name] ?? null;

        if (is_null($tick)) {
            return null;
        }

        unset($ticks[$name]);

        $this->setOpenTicks($ticks);

        return $tick;
    }

    /**
     * @return array<string, OpenTick>
     */
    protected function getOpenTicks(): array
    {
        /** @var array<string, OpenTick> $ticks */
        $ticks = $this->context->get(static::CONTEXT_KEY_TICKS, []);

        return $ticks;
    }

    /**
     * @param array<string, OpenTick> $ticks
     */
    protected function setOpenTicks(array $ticks): void
    {
        $this->context->set(static::CONTEXT_KEY_TICKS, $ticks);
    }
}
