<?php

use SLoggerLaravel\Dispatcher\SLoggerTraceLogDispatcher;
use SLoggerLaravel\Watchers\RequestSLoggerWatcher;

return [
    'watchers'   => array_filter([
        ...(env('SLOGGER_LOG_REQUESTS_ENABLED', false) ? [RequestSLoggerWatcher::class] : []),
    ]),
    'dispatcher' => SLoggerTraceLogDispatcher::class,
];
