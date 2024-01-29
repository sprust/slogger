<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceLogDispatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerCommandWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerRequestWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDatabaseWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerLogWatcher;
use SLoggerLaravel\Watchers\Services\ScheduleSLoggerWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerJobWatcher;
use SLoggerLaravel\Watchers\Services\ModelSLoggerWatcher;

return [
    // example
    'dispatcher' => SLoggerTraceLogDispatcher::class,
    'watchers'   => [
        [
            'class'   => SLoggerRequestWatcher::class,
            'enabled' => env('SLOGGER_LOG_REQUESTS_ENABLED', false),
        ],
        [
            'class'   => SLoggerCommandWatcher::class,
            'enabled' => env('SLOGGER_LOG_COMMANDS_ENABLED', false),
        ],
        [
            'class'   => SLoggerDatabaseWatcher::class,
            'enabled' => env('SLOGGER_LOG_DATABASE_ENABLED', false),
        ],
        [
            'class'   => SLoggerLogWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => ScheduleSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_LOG_ENABLED', false),
        ],
        [
            'class'   => SLoggerJobWatcher::class,
            'enabled' => env('SLOGGER_LOG_JOBS_ENABLED', false),
        ],
        [
            'class'   => ModelSLoggerWatcher::class,
            'enabled' => env('SLOGGER_LOG_MODEL_ENABLED', false),
        ],
    ],
];
