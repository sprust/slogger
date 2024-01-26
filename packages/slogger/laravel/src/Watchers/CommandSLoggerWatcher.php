<?php

namespace SLoggerLaravel\Watchers;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Exceptions\TraceProcessingAlreadyStartedException;
use SLoggerLaravel\Helpers\TraceIdHelper;

class CommandSLoggerWatcher extends AbstractSLoggerWatcher
{
    private bool $localProcessor = false;
    private array $commands = [];

    public function register(): void
    {
        $this->app['events']->listen(ArtisanStarting::class, [$this, 'handleArtisanStarting']);
        $this->app['events']->listen(CommandStarting::class, [$this, 'handleCommandStarting']);
        $this->app['events']->listen(CommandFinished::class, [$this, 'handleCommandFinished']);
    }

    public function handleArtisanStarting(ArtisanStarting $event): void
    {
        if (!$this->processor->isActive()) {
            try {
                $this->processor->start('artisan', null);

                $this->localProcessor = true;
            } catch (TraceProcessingAlreadyStartedException $exception) {
                // TODO: fire an event
                report($exception);
            }
        }
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        if (!$this->processor->isActive()) {
            return;
        }

        $this->commands[] = [
            'started_at' => now(),
        ];
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        if (!$this->processor->isActive()) {
            return;
        }

        $commandData = array_pop($this->commands);

        /** @var Carbon $startedAt */
        $startedAt = $commandData['started_at'];

        $data = [
            'command'   => $event->command ?? $event->input->getArguments()['command'] ?? 'default',
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options'   => $event->input->getOptions(),
            'duration'  => TraceIdHelper::calcDuration($startedAt),
        ];

        $this->dispatchTrace(
            type: SLoggerTraceTypeEnum::Command,
            tags: [],
            data: $data,
            loggedAt: $startedAt
        );

        if ($this->localProcessor && count($this->commands) <= 0) {
            $this->processor->stop();
        }
    }
}
