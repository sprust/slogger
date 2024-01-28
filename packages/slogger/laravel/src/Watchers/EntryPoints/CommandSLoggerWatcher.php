<?php

namespace SLoggerLaravel\Watchers\EntryPoints;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Dispatcher\TraceStopDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Symfony\Component\Console\Input\InputInterface;

class CommandSLoggerWatcher extends AbstractSLoggerWatcher
{
    private array $commands = [];

    public function register(): void
    {
        $this->listenEvent(CommandStarting::class, [$this, 'handleCommandStarting']);
        $this->listenEvent(CommandFinished::class, [$this, 'handleCommandFinished']);
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        $traceId = $this->processor->startAndGetTraceId(
            type: SLoggerTraceTypeEnum::Command
        );

        $this->commands[] = [
            'trace_id'   => $traceId,
            'started_at' => now(),
        ];
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        $commandData = array_pop($this->commands);

        if (!$commandData) {
            return;
        }

        $traceId = $commandData['trace_id'];

        /** @var Carbon $startedAt */
        $startedAt = $commandData['started_at'];

        $data = [
            'command'   => $this->makeCommandView($event->command, $event->input),
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options'   => $event->input->getOptions(),
            'duration'  => TraceIdHelper::calcDuration($startedAt),
        ];

        $this->processor->stop(
            new TraceStopDispatcherParameters(
                traceId: $traceId,
                tags: [],
                data: $data,
            )
        );
    }

    private function makeCommandView(?string $command, InputInterface $input): string
    {
        return $command ?? $input->getArguments()['command'] ?? 'default';
    }
}
