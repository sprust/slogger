<?php

namespace SLoggerLaravel\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use Throwable;

class LogSLoggerWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->app['events']->listen(MessageLogged::class, [$this, 'handle']);
    }

    public function handle(MessageLogged $event): void
    {
        if (!$this->processor->isActive()) {
            return;
        }

        $exception = $event->context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            $event->context['exception'] = [
                'message'   => $exception->getMessage(),
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => collect($exception->getTrace())->map(
                    fn($trace) => Arr::except($trace, ['args'])
                )->all(),
            ];
        }

        $data = [
            'level'   => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];

        $this->dispatchTrace(
            type: SLoggerTraceTypeEnum::Log,
            tags: [],
            data: $data,
            loggedAt: now()
        );
    }
}
