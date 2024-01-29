<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerCommandWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerJobWatcher;
use SLoggerLaravel\Watchers\EntryPoints\SLoggerRequestWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerEventWatcher;
use SLoggerLaravel\Watchers\Services\ScheduleSLoggerWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerDatabaseWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerGateWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerLogWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerMailWatcher;
use SLoggerLaravel\Watchers\Services\SLoggerModelWatcher;

return [
    // example
    'dispatcher' => SLoggerTraceDispatcher::class,
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
            'class'   => SLoggerModelWatcher::class,
            'enabled' => env('SLOGGER_LOG_MODEL_ENABLED', false),
        ],
        [
            'class'   => SLoggerGateWatcher::class,
            'enabled' => env('SLOGGER_LOG_GATE_ENABLED', false),
        ],
        [
            'class'   => SLoggerEventWatcher::class,
            'enabled' => env('SLOGGER_LOG_EVENT_ENABLED', false),
        ],
        [
            'class'   => SLoggerMailWatcher::class,
            'enabled' => env('SLOGGER_LOG_MAIL_ENABLED', false),
        ],
    ],
];
