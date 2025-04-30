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
     * false - Use sync driver
     * true - Use async driver via Fork or/and Process
     */
    'async'                   => (bool) env('SPARALLEL_ASYNC', true),

    /**
     * false - Use Process driver if running not in console
     * true - Use many forks inside one process instead many processes
     */
    'use_fork_inside_process' => (bool) env('SPARALLEL_USE_FORK_INSIDE_PROCESS', true),

    /**
     * key - event class
     * value - listener classes
     */
    'listeners'               => [
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
