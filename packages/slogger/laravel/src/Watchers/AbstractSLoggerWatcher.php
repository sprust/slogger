<?php

namespace SLoggerLaravel\Watchers;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\SLoggerProcessor;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Throwable;

abstract class AbstractSLoggerWatcher
{
    private static bool $mute = false;

    abstract public function register(): void;

    public function __construct(
        protected readonly Application $app,
        protected readonly SLoggerTraceDispatcherInterface $traceDispatcher,
        protected readonly SLoggerProcessor $processor,
        protected readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    protected function listenEvent(string $eventClass, array $function): void
    {
        $this->app['events']->listen($eventClass, $function);
    }

    protected function safeHandleWatching(Closure $callback): void
    {
        if (self::isMute()) {
            return;
        }

        try {
            $callback();
        } catch (Throwable $exception) {
            SLoggerTraceHelper::muteHandle(function () use ($exception) {
                $this->app['events']->dispatch(new SLoggerWatcherErrorEvent($exception));
            });
        }
    }

    public static function isMute(): bool
    {
        return self::$mute;
    }

    public static function toMute(): void
    {
        self::$mute = true;
    }

    public static function unMute(): void
    {
        self::$mute = false;
    }
}
