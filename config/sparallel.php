<?php

use SParallelLaravel\Events\TaskStartingEvent;
use SParallelLaravel\Events\TaskFailedEvent;
use SParallelLaravel\Listeners\LogSParallelTaskFailedListener;
use SParallelLaravel\Listeners\LogSParallelFlowFailedListener;
use SParallelLaravel\Events\TaskFinishedEvent;
use SParallelLaravel\Events\FlowStartingEvent;
use SParallelLaravel\Events\FlowFailedEvent;
use SParallelLaravel\Events\FlowFinishedEvent;
use SParallelLaravel\Events\ProcessCreatedEvent;
use SParallelLaravel\Listeners\InsertCreatedProcessToRepositoryListener;
use SParallelLaravel\Events\ProcessFinishedEvent;
use SParallelLaravel\Listeners\DeleteFinishedProcessFromRepositoryListener;

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
     * none - No process monitoring
     * redis - Use Redis for process monitoring
     */
    'workers_repository'   => env('SPARALLEL_WORKERS_REPOSITORY', 'redis'),

    /**
     * key - event class
     * value - listener classes
     */
    'listeners'            => [
        FlowStartingEvent::class    => [],
        FlowFailedEvent::class      => [
            LogSParallelFlowFailedListener::class,
        ],
        FlowFinishedEvent::class    => [],
        TaskStartingEvent::class    => [],
        TaskFailedEvent::class      => [
            LogSParallelTaskFailedListener::class,
        ],
        TaskFinishedEvent::class    => [],
        ProcessCreatedEvent::class  => [
            InsertCreatedProcessToRepositoryListener::class,
        ],
        ProcessFinishedEvent::class => [
            DeleteFinishedProcessFromRepositoryListener::class,
        ],
    ],
];
