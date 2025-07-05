<?php

use SParallel\Contracts\DriverInterface;
use SParallelLaravel\Events\FlowFailedEvent;
use SParallelLaravel\Events\FlowFinishedEvent;
use SParallelLaravel\Events\FlowStartingEvent;
use SParallelLaravel\Events\ServerGoneEvent;
use SParallelLaravel\Events\TaskFailedEvent;
use SParallelLaravel\Events\TaskFinishedEvent;
use SParallelLaravel\Events\TaskStartingEvent;
use SParallelLaravel\Listeners\ReportFlowFailedListener;
use SParallelLaravel\Listeners\ReportTaskFailedListener;
use SParallelLaravel\Listeners\ReportServerGoneListener;
use SParallel\Drivers\Sync\SyncDriver;
use SParallel\Drivers\Server\ServerDriver;

return [
    /**
     * sync - Use Sync driver
     * server - Use Server driver
     */
    'mode' => env('SPARALLEL_MODE', 'sync'),

    'log-channel' => env('SPARALLEL_LOG_CHANNEL', 'null'),

    'server'    => [
        'host'     => env('SPARALLEL_SERVER_HOST', 'localhost'),
        'port'     => (int) env('SPARALLEL_SERVER_PORT', 18077),
        'bin-path' => env('SPARALLEL_SERVER_BIN_PATH', storage_path('bin/sparallel/server')),
    ],

    /**
     * List of implementation
     * Key required in lower case
     *
     * @see DriverInterface
     */
    'drivers'   => [
        'sync'   => SyncDriver::class,
        'server' => ServerDriver::class,
    ],

    /**
     * key - event class
     * value - listener classes
     */
    'listeners' => [
        FlowStartingEvent::class => [],
        FlowFailedEvent::class   => [
            ReportFlowFailedListener::class,
        ],
        FlowFinishedEvent::class => [],
        TaskStartingEvent::class => [],
        TaskFailedEvent::class   => [
            ReportTaskFailedListener::class,
        ],
        TaskFinishedEvent::class => [],
        ServerGoneEvent::class   => [
            ReportServerGoneListener::class,
        ],
    ],
];
