<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

class SLoggerLogWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent(MessageLogged::class, [$this, 'handle']);
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

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Log,
            data: $data
        );
    }
}
