<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceLogDispatcher;
use SLoggerLaravel\Watchers\EntryPoints\CommandSLoggerWatcher;
use SLoggerLaravel\Watchers\EntryPoints\RequestSLoggerWatcher;
use SLoggerLaravel\Watchers\Services\DatabaseSLoggerWatcher;
use SLoggerLaravel\Watchers\Services\LogSLoggerWatcher;
use SLoggerLaravel\Watchers\Services\ScheduleSLoggerWatcher;
use SLoggerLaravel\Watchers\EntryPoints\JobSLoggerWatcher;

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
        [
            'class'   => JobSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_JOBS_ENABLED', false),
        ],
    ],
];
