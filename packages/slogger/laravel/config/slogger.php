<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceLogDispatcher;
use SLoggerLaravel\Watchers\CommandSLoggerWatcher;
use SLoggerLaravel\Watchers\DatabaseSLoggerWatcher;
use SLoggerLaravel\Watchers\RequestSLoggerWatcher;

return [
    'watchers'   => array_filter([
        ...(env('SLOGGER_LOG_REQUESTS_ENABLED', false) ? [RequestSLoggerWatcher::class] : []),
        ...(env('SLOGGER_LOG_COMMANDS_ENABLED', false) ? [CommandSLoggerWatcher::class] : []),
        ...(env('SLOGGER_LOG_DATABASE_ENABLED', false) ? [DatabaseSLoggerWatcher::class] : []),
    ]),
    // example
    'dispatcher' => SLoggerTraceLogDispatcher::class,
];
