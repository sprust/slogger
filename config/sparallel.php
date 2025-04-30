<?php

use SParallelLaravel\Events\SParallelTaskStartingEvent;
use SParallelLaravel\Events\SParallelTaskFailedEvent;
use SParallelLaravel\Listeners\LogSParallelTaskFailedListener;
use SParallelLaravel\Listeners\LogSParallelFlowFailedListener;
use SParallelLaravel\Events\SParallelTaskFinishedEvent;
use SParallelLaravel\Events\SParallelFlowStartingEvent;
use SParallelLaravel\Events\SParallelFlowFailedEvent;
use SParallelLaravel\Events\SParallelFlowFinishedEvent;
use SParallelLaravel\Listeners\DBReconnectAtTaskStartingListener;

return [
    /**
     * sync - Use Sync driver
     * process - Use Process driver always
     * hybrid - Use Hybrid driver always
     * process_fork - Use Fork driver if console else Process driver
     * hybrid_fork - Use Fork driver if console else Hybrid driver
     */
    'mode' => env('SPARALLEL_MODE', 'sync'),

    'task_memory_limit_mb' => (int) env('SPARALLEL_TASK_MEMORY_LIMIT_MB', 128),

    /**
     * key - event class
     * value - listener classes
     */
    'listeners'            => [
        SParallelFlowStartingEvent::class => [],
        SParallelFlowFailedEvent::class   => [
            LogSParallelFlowFailedListener::class,
        ],
        SParallelFlowFinishedEvent::class => [],
        SParallelTaskStartingEvent::class => [
            DBReconnectAtTaskStartingListener::class,
        ],
        SParallelTaskFailedEvent::class   => [
            LogSParallelTaskFailedListener::class,
        ],
        SParallelTaskFinishedEvent::class => [],
    ],
];
