<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceLogDispatcher;
use SLoggerLaravel\Watchers\CommandSLoggerWatcher;
use SLoggerLaravel\Watchers\DatabaseSLoggerWatcher;
use SLoggerLaravel\Watchers\LogSLoggerWatcher;
use SLoggerLaravel\Watchers\RequestSLoggerWatcher;
use SLoggerLaravel\Watchers\ScheduleSLoggerWatcher;

return [
    // example
    'dispatcher' => SLoggerTraceLogDispatcher::class,
    'watchers'   => [
        [
            'class'   => RequestSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_REQUESTS_ENABLED', false),
        ],
        [
            'class'   => CommandSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_COMMANDS_ENABLED', false),
        ],
        [
            'class'   => DatabaseSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_DATABASE_ENABLED', false),
        ],
        [
            'class'   => LogSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => ScheduleSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
    ],
];
